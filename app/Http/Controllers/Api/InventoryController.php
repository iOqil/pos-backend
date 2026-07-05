<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Get recent stock movements.
     */
    public function index(Request $request)
    {
        $query = StockMovement::with(['product']);
        if ($request->search) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('barcode', 'like', '%' . $request->search . '%');
            });
        }
        $perPage = $request->per_page ?? 20;
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    /**
     * Record a new stock movement and update product stock.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:purchase,return,adjustment,write_off',
            'quantity' => 'required|integer|not_in:0',
            'note' => 'nullable|string'
        ]);

        $product = Product::findOrFail($data['product_id']);

        // Check if write_off or adjustment makes stock negative
        if ($data['quantity'] < 0 && $product->stock_quantity + $data['quantity'] < 0) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        DB::beginTransaction();
        try {
            // Create movement
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id() ?? null
            ]);

            // Update product stock
            $product->stock_quantity += $data['quantity'];
            $product->save();

            DB::commit();

            return response()->json($movement, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing inventory: ' . $e->getMessage()], 500);
        }
    }
}
