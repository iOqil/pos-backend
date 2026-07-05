<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTransaction;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ServiceTransactionController extends Controller
{
    #[OA\Get(path: "/api/service-transactions", summary: "Get transactions", security: [["sanctum" => []]], tags: ["Services"])]
    #[OA\Response(response: 200, description: "Transactions retrieved")]
    public function index() { return response()->json(ServiceTransaction::with(['service', 'customer', 'cashier'])->latest()->paginate(15)); }

    #[OA\Post(path: "/api/service-transactions", summary: "Create transaction", security: [["sanctum" => []]], tags: ["Services"])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(required: ["service_id", "amount"]))]
    #[OA\Response(response: 201, description: "Created")]
    public function store(Request $request) {
        $data = $request->validate(['service_id' => 'required|exists:services,id', 'amount' => 'required|numeric']);
        $data['cashier_id'] = $request->user()->id;
        return response()->json(ServiceTransaction::create($data), 201);
    }
}
