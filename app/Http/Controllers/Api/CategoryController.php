<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(path: "/api/categories", summary: "Get categories", tags: ["Categories"])]
    #[OA\Response(response: 200, description: "Categories retrieved")]
    public function index(Request $request) { 
        $query = Category::with('children')->whereNull('parent_id');
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->all) {
            return response()->json($query->latest()->get());
        }

        $perPage = $request->per_page ?? 15;
        return response()->json($query->latest()->paginate($perPage)); 
    }

    #[OA\Post(path: "/api/categories", summary: "Create category", security: [["sanctum" => []]], tags: ["Categories"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name", "slug"]))]
    #[OA\Response(response: 201, description: "Category created")]
    public function store(Request $request) {
        $data = $request->validate(['name' => 'required', 'slug' => 'required|unique:categories', 'parent_id' => 'nullable|exists:categories,id']);
        return response()->json(Category::create($data), 201);
    }

    #[OA\Get(path: "/api/categories/{id}", summary: "Get a category", tags: ["Categories"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Category retrieved")]
    public function show($id) { return response()->json(Category::findOrFail($id)); }

    #[OA\Put(path: "/api/categories/{id}", summary: "Update category", security: [["sanctum" => []]], tags: ["Categories"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 200, description: "Category updated")]
    public function update(Request $request, $id) {
        $cat = Category::findOrFail($id);
        $cat->update($request->all());
        return response()->json($cat);
    }

    #[OA\Delete(path: "/api/categories/{id}", summary: "Delete category", security: [["sanctum" => []]], tags: ["Categories"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 204, description: "Deleted")]
    public function destroy($id) { Category::destroy($id); return response()->json(null, 204); }
}
