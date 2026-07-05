<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(path: "/api/users", summary: "Get all users", security: [["sanctum" => []]], tags: ["Users"])]
    #[OA\Response(response: 200, description: "List of users")]
    public function index(Request $request) { 
        $query = User::query();
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('role', 'like', '%' . $request->search . '%');
        }
        $perPage = $request->per_page ?? 15;
        return response()->json($query->latest()->paginate($perPage)); 
    }

    #[OA\Post(path: "/api/users", summary: "Create a user", security: [["sanctum" => []]], tags: ["Users"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["name", "email", "password", "role"]))]
    #[OA\Response(response: 201, description: "User created")]
    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:6', 'role' => 'required|string', 'phone' => 'nullable|string'
        ]);
        $data['password'] = Hash::make($data['password']);
        return response()->json(User::create($data), 201);
    }

    #[OA\Get(path: "/api/users/{id}", summary: "Get a user", security: [["sanctum" => []]], tags: ["Users"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "User found")]
    public function show($id) { return response()->json(User::findOrFail($id)); }

    #[OA\Put(path: "/api/users/{id}", summary: "Update a user", security: [["sanctum" => []]], tags: ["Users"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent())]
    #[OA\Response(response: 200, description: "User updated")]
    public function update(Request $request, $id) {
        $user = User::findOrFail($id);
        $data = $request->all();
        if(isset($data['password'])) { $data['password'] = Hash::make($data['password']); }
        $user->update($data);
        return response()->json($user);
    }

    #[OA\Delete(path: "/api/users/{id}", summary: "Delete a user", security: [["sanctum" => []]], tags: ["Users"])]
    #[OA\PathParameter(name: "id", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 204, description: "User deleted")]
    public function destroy($id) { User::destroy($id); return response()->json(null, 204); }
}
