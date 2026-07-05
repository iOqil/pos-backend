<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ServiceController extends Controller
{
    #[OA\Get(path: "/api/services", summary: "Get services", tags: ["Services"])]
    #[OA\Response(response: 200, description: "Services retrieved")]
    public function index(Request $request) { 
        $query = Service::query();
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
        }
        $perPage = $request->per_page ?? 15;
        return response()->json($query->latest()->paginate($perPage)); 
    }

    #[OA\Post(path: "/api/services", summary: "Create a service", security: [["sanctum" => []]], tags: ["Services"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name", "slug"]))]
    #[OA\Response(response: 201, description: "Service created")]
    public function store(Request $request) {
        $data = $request->validate(['name' => 'required', 'slug' => 'required|unique:services', 'type' => 'required']);
        return response()->json(Service::create($data), 201);
    }
    
    #[OA\Put(path: "/api/services/{id}", summary: "Update service", security: [["sanctum" => []]], tags: ["Services"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 200, description: "Service updated")]
    public function update(Request $request, $id) {
        $s = Service::findOrFail($id); $s->update($request->all()); return response()->json($s);
    }

    #[OA\Delete(path: "/api/services/{id}", summary: "Delete service", security: [["sanctum" => []]], tags: ["Services"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 204, description: "Deleted")]
    public function destroy($id) { Service::destroy($id); return response()->json(null, 204); }
}
