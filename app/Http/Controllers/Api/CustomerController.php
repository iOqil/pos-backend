<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CustomerController extends Controller
{
    #[OA\Get(path: "/api/customers", summary: "Get customers", security: [["sanctum" => []]], tags: ["Customers"])]
    #[OA\Response(response: 200, description: "Customers retrieved")]
    public function index() { return response()->json(Customer::all()); }

    #[OA\Post(path: "/api/customers", summary: "Create customer", security: [["sanctum" => []]], tags: ["Customers"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name", "phone"]))]
    #[OA\Response(response: 201, description: "Created")]
    public function store(Request $request) {
        $data = $request->validate(['name' => 'required', 'phone' => 'required|unique:customers']);
        return response()->json(Customer::create($data), 201);
    }

    #[OA\Get(path: "/api/customers/{id}", summary: "Get customer", security: [["sanctum" => []]], tags: ["Customers"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Customer retrieved")]
    public function show($id) { return response()->json(Customer::findOrFail($id)); }

    #[OA\Put(path: "/api/customers/{id}", summary: "Update customer", security: [["sanctum" => []]], tags: ["Customers"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 200, description: "Updated")]
    public function update(Request $request, $id) {
        $c = Customer::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string', 'email' => 'nullable|email', 'address' => 'nullable|string']);
        $c->update($request->only(['name', 'phone', 'email', 'address']));
        return response()->json($c);
    }

    #[OA\Get(path: "/api/customers/search", summary: "Search customers", security: [["sanctum" => []]], tags: ["Customers"])]
    #[OA\QueryParameter(name: "phone", required: true, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Customers found")]
    public function search(Request $request) {
        $query = $request->get('phone');
        return response()->json(Customer::where('phone', 'like', "%{$query}%")->get());
    }
}
