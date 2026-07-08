<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    #[OA\Get(path: "/api/reports/overview", summary: "Dynamic overview report", security: [["sanctum" => []]], tags: ["Reports"])]
    public function overview(Request $request) {
        $period = $request->query('period', 'day');
        $now = \Carbon\Carbon::now();
        
        switch($period) {
            case 'week':
                $startDate = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                $endDate = $now->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                $dateFormat = 'Y-m-d';
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $dateFormat = 'Y-m-d';
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $dateFormat = 'Y-m';
                break;
            case 'day':
            default:
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $dateFormat = 'H:00';
                break;
        }

        $sales = Sale::whereBetween('created_at', [$startDate, $endDate])->where('status', 'completed')->get();
        
        $totalRevenue = $sales->sum('total');
        $salesCount = $sales->count();
        $avgReceipt = $salesCount > 0 ? $totalRevenue / $salesCount : 0;
        
        $chartData = [];
        $groupedSales = $sales->groupBy(function($sale) use ($dateFormat) {
            return \Carbon\Carbon::parse($sale->created_at)->format($dateFormat);
        });
        
        if ($period == 'week') {
            $days = ['Dush', 'Sesh', 'Chor', 'Pay', 'Juma', 'Shan', 'Yak'];
            for ($i=0; $i<7; $i++) {
                $d = $startDate->copy()->addDays($i)->format('Y-m-d');
                $chartData[] = [
                    'label' => $days[$i],
                    'value' => isset($groupedSales[$d]) ? $groupedSales[$d]->sum('total') : 0
                ];
            }
        } elseif ($period == 'day') {
            for ($i=0; $i<=23; $i+=2) {
                $d = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $dNext = str_pad($i+1, 2, '0', STR_PAD_LEFT) . ':00';
                $val1 = isset($groupedSales[$d]) ? $groupedSales[$d]->sum('total') : 0;
                $val2 = isset($groupedSales[$dNext]) ? $groupedSales[$dNext]->sum('total') : 0;
                $chartData[] = [
                    'label' => $d,
                    'value' => $val1 + $val2
                ];
            }
        } elseif ($period == 'month') {
            $daysInMonth = $now->daysInMonth;
            for ($i=1; $i<=$daysInMonth; $i+=3) { // group every 3 days to fit
                $val = 0;
                for ($j=0; $j<3; $j++) {
                    if ($i+$j > $daysInMonth) break;
                    $d = $startDate->copy()->addDays($i+$j-1)->format('Y-m-d');
                    $val += isset($groupedSales[$d]) ? $groupedSales[$d]->sum('total') : 0;
                }
                $chartData[] = [
                    'label' => $i,
                    'value' => $val
                ];
            }
        } elseif ($period == 'year') {
            $months = ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyun', 'Iyul', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'];
            for ($i=1; $i<=12; $i++) {
                $d = $startDate->copy()->addMonths($i-1)->format('Y-m');
                $chartData[] = [
                    'label' => $months[$i-1],
                    'value' => isset($groupedSales[$d]) ? $groupedSales[$d]->sum('total') : 0
                ];
            }
        }

        $topProducts = \Illuminate\Support\Facades\DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_barcode', \Illuminate\Support\Facades\DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_barcode')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();
            
        $leastProducts = \Illuminate\Support\Facades\DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_barcode', \Illuminate\Support\Facades\DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_barcode')
            ->orderBy('total_sold', 'asc')
            ->limit(5)
            ->get();

        return response()->json([
            'period' => $period,
            'total_revenue' => $totalRevenue,
            'sales_count' => $salesCount,
            'avg_receipt' => $avgReceipt,
            'chart_data' => $chartData,
            'top_products' => $topProducts,
            'least_products' => $leastProducts
        ]);
    }

    #[OA\Get(path: "/api/reports/daily", summary: "Daily sales report", security: [["sanctum" => []]], tags: ["Reports"])]
    #[OA\Response(response: 200, description: "Report generated")]
    public function daily(Request $request) {
        $date = $request->query('date', date('Y-m-d'));
        $salesCount = Sale::whereDate('created_at', $date)->where('status', 'completed')->count();
        $total = Sale::whereDate('created_at', $date)->where('status', 'completed')->sum('total');
        
        $cashTotal = \App\Models\Payment::whereDate('created_at', $date)
                        ->whereHas('sale', function($q) { $q->where('status', 'completed'); })
                        ->where('method', 'cash')->sum('amount');
        $cashChange = \App\Models\Payment::whereDate('created_at', $date)
                        ->whereHas('sale', function($q) { $q->where('status', 'completed'); })
                        ->where('method', 'cash')->sum('change_amount');
                        
        $cardTotal = \App\Models\Payment::whereDate('created_at', $date)
                        ->whereHas('sale', function($q) { $q->where('status', 'completed'); })
                        ->where('method', 'card')->sum('amount');

        return response()->json([
            'date' => $date, 
            'total_revenue' => $total, 
            'sales_count' => $salesCount,
            'cash_revenue' => $cashTotal - $cashChange,
            'card_revenue' => $cardTotal
        ]);
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
