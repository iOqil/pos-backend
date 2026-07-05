<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SaleController extends Controller
{
    #[OA\Get(path: "/api/sales", summary: "Get sales", security: [["sanctum" => []]], tags: ["Sales"])]
    #[OA\Response(response: 200, description: "Sales retrieved")]
    public function index(Request $request) { 
        $query = Sale::with(['customer', 'cashier', 'payments', 'items']);
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('sale_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('cashier', function($q2) use ($request) {
                      $q2->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $perPage = $request->per_page ?? 15;
        return response()->json($query->latest()->paginate($perPage)); 
    }
    #[OA\Post(path: "/api/sales", summary: "Create sale", security: [["sanctum" => []]], tags: ["Sales"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 201, description: "Sale created")]
    public function store(Request $request) {
        $request->validate([
            'payment_method' => 'required|string',
            'amount_paid' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric'
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $saleNumber = 'SALE-' . strtoupper(uniqid());
            
            $subtotal = 0;
            $itemsData = [];
            foreach ($request->items as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                $itemDiscount = $item['discount'] ?? 0;
                $itemTotal = ($item['unit_price'] * $item['quantity']) - $itemDiscount;
                $subtotal += $itemTotal;
                
                $itemsData[] = new \App\Models\SaleItem([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_barcode' => $product->barcode,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'total' => $itemTotal
                ]);

                // Deduct stock
                $product->decrement('stock_quantity', $item['quantity']);
            }

            $discountAmount = $request->discount_amount ?? 0;
            $total = max(0, $subtotal - $discountAmount);

            $sale = Sale::create([
                'sale_number' => $saleNumber,
                'customer_id' => $request->customer_id ?? null,
                'cashier_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'status' => 'completed'
            ]);

            $sale->items()->saveMany($itemsData);

            if ($request->payment_method === 'mix') {
                if ($request->cash_amount > 0) {
                    \App\Models\Payment::create([
                        'sale_id' => $sale->id,
                        'method' => 'cash',
                        'amount' => $request->cash_amount,
                        'change_amount' => max(0, $request->cash_amount + $request->card_amount - $total),
                    ]);
                }
                if ($request->card_amount > 0) {
                    \App\Models\Payment::create([
                        'sale_id' => $sale->id,
                        'method' => 'card',
                        'amount' => $request->card_amount,
                        'change_amount' => 0,
                    ]);
                }
            } else {
                \App\Models\Payment::create([
                    'sale_id' => $sale->id,
                    'method' => $request->payment_method,
                    'amount' => $request->amount_paid,
                    'change_amount' => max(0, $request->amount_paid - $total),
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Sale processed successfully',
                'sale' => $sale->load('items')
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Error processing sale: ' . $e->getMessage()], 500);
        }
    }

    #[OA\Get(path: "/api/sales/{id}", summary: "Get sale details", security: [["sanctum" => []]], tags: ["Sales"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Sale retrieved")]
    public function show($id) { return response()->json(Sale::with(['items', 'payments', 'customer'])->findOrFail($id)); }

    #[OA\Post(path: "/api/sales/{id}/refund", summary: "Refund a sale", security: [["sanctum" => []]], tags: ["Sales"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Refunded")]
    public function refund($id) {
        $sale = Sale::findOrFail($id);
        $sale->update(['status' => 'refunded']);
        return response()->json(['message' => 'Refund processed']);
    }
}
