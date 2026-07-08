<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(path: "/api/products", summary: "Get products", tags: ["Products"])]
    #[OA\Response(response: 200, description: "Products retrieved")]
    public function index(Request $request) { 
        $query = Product::with(['category', 'brand', 'variants']);
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('barcode', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $perPage = $request->per_page ?? 15;
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage)); 
    }
    #[OA\Post(path: "/api/products", summary: "Create product", security: [["sanctum" => []]], tags: ["Products"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name", "slug", "sku", "price"]))]
    #[OA\Response(response: 201, description: "Product created")]
    public function store(Request $request) {
        if (!$request->slug && $request->name) {
            $request->merge(['slug' => \Illuminate\Support\Str::slug($request->name) . '-' . uniqid()]);
        }
        $data = $request->validate([
            'name' => 'required', 'slug' => 'required|unique:products', 'sku' => 'required|unique:products',
            'price' => 'required|numeric', 'barcode' => 'nullable|string', 'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id', 'stock_quantity' => 'nullable|integer', 'cost_price' => 'nullable|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        return response()->json(Product::create($data), 201);
    }

    #[OA\Get(path: "/api/products/{id}", summary: "Get a product", tags: ["Products"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Product retrieved")]
    public function show($id) { return response()->json(Product::with(['category', 'brand', 'variants'])->findOrFail($id)); }

    #[OA\Put(path: "/api/products/{id}", summary: "Update product", security: [["sanctum" => []]], tags: ["Products"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 200, description: "Product updated")]
    public function update(Request $request, $id) {
        $product = Product::findOrFail($id);
        
        // When using FormData with PUT, PHP often has issues parsing files. 
        // We use POST with _method=PUT to handle file uploads in update.
        $data = $request->except(['image']);
        
        if ($request->hasFile('image')) {
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        return response()->json($product);
    }

    #[OA\Delete(path: "/api/products/{id}", summary: "Delete product", security: [["sanctum" => []]], tags: ["Products"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 204, description: "Deleted")]
    public function destroy($id) { Product::destroy($id); return response()->json(null, 204); }

    #[OA\Get(path: "/api/products/barcode/{barcode}", summary: "Find by barcode", tags: ["Products"])]
    #[OA\PathParameter(name: "barcode", required: true, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Found")]
    public function getByBarcode($barcode) {
        $product = Product::with(['category', 'brand', 'variants'])->where('barcode', $barcode)->first();
        if (!$product) { return response()->json(['message' => 'Product not found'], 404); }
        return response()->json($product);
    }
}
