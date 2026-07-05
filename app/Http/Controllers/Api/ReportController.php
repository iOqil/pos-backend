<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    #[OA\Get(path: "/api/reports/daily", summary: "Daily sales report", security: [["sanctum" => []]], tags: ["Reports"])]
    #[OA\Response(response: 200, description: "Report generated")]
    public function daily(Request $request) {
        $date = $request->query('date', date('Y-m-d'));
        $salesCount = Sale::whereDate('created_at', $date)->where('status', 'completed')->count();
        $total = Sale::whereDate('created_at', $date)->where('status', 'completed')->sum('total');
        return response()->json(['date' => $date, 'total_revenue' => $total, 'sales_count' => $salesCount]);
    }
    #[OA\Get(path: "/api/reports/products", summary: "Detailed product sales report", security: [["sanctum" => []]], tags: ["Reports"])]
    #[OA\Response(response: 200, description: "Report generated")]
    public function productSales(Request $request) {
        $query = \App\Models\SaleItem::with(['sale' => function($q) {
            $q->select('id', 'sale_number', 'created_at', 'status', 'cashier_id');
        }])
        ->whereHas('sale', function($q) {
            $q->where('status', 'completed');
        });

        if ($request->search) {
            $query->where('product_name', 'like', '%' . $request->search . '%')
                  ->orWhere('product_barcode', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->per_page ?? 20;
        
        $items = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($items);
    }
}
