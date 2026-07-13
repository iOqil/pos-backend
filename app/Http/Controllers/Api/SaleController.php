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
            'amount_paid' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'due_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.discount' => 'nullable|numeric'
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            do {
                $saleNumber = (string)random_int(100000, 999999);
            } while (\App\Models\Sale::where('sale_number', $saleNumber)->exists());
            
            $subtotal = 0;
            $itemsData = [];
            foreach ($request->items as $item) {
                $product = \App\Models\Product::lockForUpdate()->find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Mahsulot topilmadi: ID {$item['product_id']}");
                }
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("'{$product->name}' omborda yetarli emas. Qoldiq: {$product->stock_quantity}");
                }
                $serverPrice = $product->price;
                $itemDiscount = $item['discount'] ?? 0;
                $itemTotal = ($serverPrice * $item['quantity']) - $itemDiscount;
                $subtotal += $itemTotal;
                
                $itemsData[] = new \App\Models\SaleItem([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_barcode' => $product->barcode,
                    'quantity' => $item['quantity'],
                    'unit_price' => $serverPrice,
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
                        'change_amount' => max(0, $request->cash_amount + ($request->card_amount ?? 0) - $total),
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
            } elseif ($request->payment_method === 'debt') {
                // Nasiyaga berish
                if (!$request->customer_id) {
                    throw new \Exception('Nasiyaga berish uchun mijoz tanlash shart');
                }
                \App\Models\Payment::create([
                    'sale_id' => $sale->id,
                    'method' => 'debt',
                    'amount' => $total,
                    'change_amount' => 0,
                ]);
                \App\Models\Debt::create([
                    'customer_id' => $request->customer_id,
                    'sale_id' => $sale->id,
                    'amount' => $total,
                    'note' => 'Sotuv: ' . $saleNumber,
                    'due_date' => $request->due_date,
                ]);
            } else {
                \App\Models\Payment::create([
                    'sale_id' => $sale->id,
                    'method' => $request->payment_method,
                    'amount' => $request->amount_paid ?? $total,
                    'change_amount' => max(0, ($request->amount_paid ?? $total) - $total),
                ]);
            }

            // Mijoz total_spent yangilash (nasiya bo'lmasa)
            if ($request->customer_id && $request->payment_method !== 'debt') {
                $customer = \App\Models\Customer::find($request->customer_id);
                if ($customer) {
                    $customer->increment('total_spent', $total);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            // Telegram bildirishnoma (tranzaksiyadan tashqarida)
            try {
                $sale->load(['items', 'customer', 'cashier', 'payments']);
                $telegram = new \App\Services\TelegramService();
                $telegram->sendSaleNotification($sale);

                // Kam qolgan tovarlarni tekshirish
                $threshold = \App\Models\Setting::get('low_stock_threshold', 5);
                $lowStock = \App\Models\Product::where('stock_quantity', '<=', $threshold)
                    ->where('stock_quantity', '>', 0)->get();
                if ($lowStock->isNotEmpty()) {
                    $telegram->sendLowStockAlert($lowStock);
                }
            } catch (\Exception $e) {
                // Telegram xatosi sotuv jarayonini to'xtatmasligi kerak
                \Log::warning('Telegram notification failed: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Sale processed successfully',
                'sale' => $sale->load('items')
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('Sale processing error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
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
        $sale = Sale::with('items')->findOrFail($id);
        if ($sale->status === 'refunded') {
            return response()->json(['message' => 'Bu savdo allaqachon qaytarilgan'], 422);
        }
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($sale->items as $item) {
                \App\Models\Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
            }
            $sale->update(['status' => 'refunded']);
            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['message' => 'Qaytarish amalga oshirildi, tovarlar omborga qaytarildi']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Log::error('Refund error: ' . $e->getMessage());
            return response()->json(['message' => 'Xatolik yuz berdi'], 500);
        }
    }
}
