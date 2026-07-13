<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    /**
     * Barcha nasiyalar ro'yxati
     */
    public function index(Request $request)
    {
        $query = Debt::with(['customer', 'sale']);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        $perPage = $request->per_page ?? 20;
        $debts = $query->latest()->paginate($perPage);

        // Umumiy statistika
        $stats = [
            'total_debt' => Debt::where('status', 'active')->sum(DB::raw('amount - paid_amount')),
            'total_active' => Debt::where('status', 'active')->count(),
            'total_paid' => Debt::where('status', 'paid')->count(),
        ];

        return response()->json([
            'debts' => $debts,
            'stats' => $stats
        ]);
    }

    /**
     * Nasiyani qisman yoki to'liq to'lash
     */
    public function pay(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string'
        ]);

        $debt = Debt::findOrFail($id);

        if ($debt->status === 'paid') {
            return response()->json(['message' => 'Bu nasiya allaqachon to\'langan'], 422);
        }

        $remaining = $debt->amount - $debt->paid_amount;

        if ($request->amount > $remaining) {
            return response()->json(['message' => 'To\'lov summasi qoldiqdan ko\'p. Qoldiq: ' . $remaining], 422);
        }

        DB::beginTransaction();
        try {
            $debt->paid_amount += $request->amount;
            
            if ($debt->paid_amount >= $debt->amount) {
                $debt->status = 'paid';
            }

            if ($request->note) {
                $debt->note = ($debt->note ? $debt->note . "\n" : '') . date('d.m.Y') . ': ' . $request->note;
            }

            $debt->save();

            // Customer total_spent yangilash
            $customer = $debt->customer;
            $customer->total_spent += $request->amount;
            $customer->save();

            DB::commit();

            return response()->json([
                'message' => $debt->status === 'paid' ? 'Nasiya to\'liq to\'landi!' : 'To\'lov qabul qilindi',
                'debt' => $debt->load('customer')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Debt payment error: ' . $e->getMessage());
            return response()->json(['message' => 'Xatolik yuz berdi'], 500);
        }
    }

    /**
     * Nasiya yaratish (sotuvdan tashqari)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string'
        ]);

        $debt = Debt::create($data);
        return response()->json($debt->load('customer'), 201);
    }

    /**
     * Nasiyani o'chirish
     */
    public function destroy($id)
    {
        $debt = Debt::findOrFail($id);
        if ($debt->paid_amount > 0) {
            return response()->json(['message' => 'Qisman to\'langan nasiyani o\'chirish mumkin emas'], 422);
        }
        $debt->delete();
        return response()->json(['message' => 'Nasiya o\'chirildi']);
    }
}
