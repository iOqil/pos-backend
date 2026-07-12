<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BrandController extends Controller
{
    #[OA\Get(path: "/api/brands", summary: "Get brands", tags: ["Brands"])]
    #[OA\Response(response: 200, description: "Brands retrieved")]
    public function index() { return response()->json(Brand::all()); }

    #[OA\Post(path: "/api/brands", summary: "Create brand", security: [["sanctum" => []]], tags: ["Brands"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name"]))]
    #[OA\Response(response: 201, description: "Brand created")]
    public function store(Request $request) {
        $data = $request->validate(['name' => 'required', 'type' => 'nullable']);
        return response()->json(Brand::create($data), 201);
    }

    #[OA\Get(path: "/api/brands/{id}", summary: "Get a brand", tags: ["Brands"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Brand retrieved")]
    public function show($id) { return response()->json(Brand::findOrFail($id)); }

    #[OA\Put(path: "/api/brands/{id}", summary: "Update brand", security: [["sanctum" => []]], tags: ["Brands"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 200, description: "Brand updated")]
    public function update(Request $request, $id) {
        $brand = Brand::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255']);
        $brand->update($request->only(['name']));
        return response()->json($brand);
    }

    #[OA\Delete(path: "/api/brands/{id}", summary: "Delete brand", security: [["sanctum" => []]], tags: ["Brands"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 204, description: "Deleted")]
    public function destroy($id) { Brand::destroy($id); return response()->json(null, 204); }
}
