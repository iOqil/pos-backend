<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request) 
    {
        $query = Customer::query();
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = $request->per_page ?? 20;
        $customers = $query->withCount('sales')
            ->withSum(['debts as active_debt' => function($q) { $q->where('status', 'active'); }], \DB::raw('amount - paid_amount'))
            ->orderBy('name')
            ->paginate($perPage);
        
        return response()->json($customers);
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date'
        ]);
        
        return response()->json(Customer::create($data), 201);
    }

    public function show($id) 
    { 
        $customer = Customer::with(['sales' => function($q) {
            $q->with(['items', 'payments', 'cashier'])->latest()->take(20);
        }, 'debts' => function($q) {
            $q->latest();
        }])
        ->withCount('sales')
        ->withSum(['debts as active_debt' => function($q) { $q->where('status', 'active'); }], \DB::raw('amount - paid_amount'))
        ->findOrFail($id);

        // Statistikalar
        $stats = [
            'total_sales' => $customer->sales_count,
            'total_spent' => $customer->total_spent,
            'active_debt' => $customer->active_debt ?? 0,
            'last_purchase' => $customer->sales()->latest()->value('created_at'),
        ];

        return response()->json([
            'customer' => $customer,
            'stats' => $stats
        ]);
    }

    public function update(Request $request, $id) 
    {
        $c = Customer::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255', 
            'phone' => 'required|string|unique:customers,phone,' . $id, 
            'email' => 'nullable|email', 
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date'
        ]);
        $c->update($request->only(['name', 'phone', 'email', 'address', 'birth_date']));
        return response()->json($c);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        
        // Aktiv nasiyasi bor mijozni o'chirish mumkin emas
        $activeDebt = $customer->debts()->where('status', 'active')->exists();
        if ($activeDebt) {
            return response()->json(['message' => 'Aktiv nasiyasi bor mijozni o\'chirish mumkin emas'], 422);
        }

        $customer->delete();
        return response()->json(['message' => 'Mijoz o\'chirildi']);
    }

    public function search(Request $request) 
    {
        $query = $request->get('q') ?? $request->get('phone');
        return response()->json(
            Customer::where('phone', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%")
                ->limit(10)
                ->get()
        );
    }
}
