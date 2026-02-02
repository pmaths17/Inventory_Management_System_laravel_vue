<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function stockSummary()
    {
        $products = Product::orderBy('name')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->current_stock,
            ];
        });
        return response()->json($products);
    }
    public function stockLedger(Request $request)
    {
        $query = StockMovement::with('product')->orderBy('created_at', 'desc');
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }
        return response()->json($query->paginate(20));
    }

    public function lowStock(Request $request)
    {
        $threshold = $request->threshold ?? 10;
        $products = Product::all()->filter(function ($product) use ($threshold) {
            return $product->current_stock < $threshold;
        });
        return response()->json($products);
    }
    public function purchaseList(Request $request)
    {
        $query = Purchase::with('supplier');
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('purchase_date', [$request->from_date, $request->to_date]);
        }
        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }
        return response()->json($query->paginate(15));
    }
    public function purchaseDetails($id)
    {
        return response()->json(
            Purchase::with(['supplier', 'items.product'])->findOrFail($id)
        );
    }

    public function supplierWisePurchases(Request $request)
    {
        return response()->json(
            Purchase::select('supplier_id', DB::raw('SUM(total_amount) as total'))
                ->with('supplier')
                ->whereBetween('purchase_date', [$request->from_date, $request->to_date])
                ->groupBy('supplier_id')
                ->get()
        );
    }

    public function salesList(Request $request)
    {
        $query = Sale::with('customer');
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('sale_date', [$request->from_date, $request->to_date]);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        return response()->json($query->paginate(15));
    }
    public function salesdetails($id)
    {
        return response()->json(
            Sale::with(['customer', 'items.product'])->findOrFail($id)
        );
    }
    public function customerWiseSales(Request $request)
    {
        return response()->json(
            Sale::select('customer_id', DB::raw('SUM(total_amount) as total'))
                ->with('customer')
                ->whereBetween('sale_date', [$request->from_date, $request->to_date])
                ->groupBy('customer_id')
                ->get()
        );
    }


    public function productWiseSales(Request $request)
    {
        return response()->json(
            DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(quantity) as total_qty'),
                    DB::raw('SUM(subtotal) as revenue')
                )
                ->groupBy('products.id', 'products.name')
                ->get()
        );
    }

    public function revenue(Request $request)
    {
        return response()->json([
            'revenue' => Sale::whereBetween('sale_date', [$request->from_date, $request->to_date])
                ->sum('total_amount')
        ]);
    }

    public function purchaseCost(Request $request)
    {
        return response()->json([
            'cost' => Purchase::whereBetween('purchase_date', [$request->from_date, $request->to_date])
                ->sum('total_amount')
        ]);
    }

    public function profit(Request $request)
    {
        $sale = Sale::whereBetween('sale_date', [$request->from_date, $request->to_date])
            ->sum('total_amount');
        $purchase = Purchase::whereBetween('purchase_date', [$request->from_date, $request->to_date])
            ->sum('total_amount');
        return response()->json([
            'profit' => $sale - $purchase
        ]);
    }
    public function salesChartData(Request $request)
    {
        $days = $request->days ?? 30;

        // This query gets daily sales totals for the last X days
        $data = Sale::select(
            DB::raw('DATE(sale_date) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('sale_date', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        return response()->json($data);
    }

    public function dashboardSummary(Request $request)
{
    $days = $request->days ?? 30;
    $startDate = now()->subDays($days);

    // Sum totals using SQL for speed
    $totalSales = Sale::where('sale_date', '>=', $startDate)->sum('total_amount');
    $totalPurchases = Purchase::where('purchase_date', '>=', $startDate)->sum('total_amount');
    
    // Global counts (these usually stay static regardless of time filter)
    $totalProducts = Product::count();
    $lowStockThreshold = 10;
    // Using your existing logic but in a query for better performance
    $lowStockCount = Product::all()->filter(function ($product) use ($lowStockThreshold) {
        return $product->current_stock < $lowStockThreshold;
    })->count();

    return response()->json([
        'totalSales' => (float)$totalSales,
        'totalPurchases' => (float)$totalPurchases,
        'totalProducts' => $totalProducts,
        'lowStockItems' => $lowStockCount,
        'profit' => (float)($totalSales - $totalPurchases)
    ]);
}
// Add this method for detailed profit report
public function profitReport(Request $request)
{
    $fromDate = $request->from_date;
    $toDate = $request->to_date;

    // Get all completed sales with items
    $salesData = Sale::with('items.product')
        ->where('status', 'completed')
        ->whereBetween('sale_date', [$fromDate, $toDate])
        ->get();

    $reportData = [];
    $totalRevenue = 0;
    $totalCost = 0;
    $totalProfit = 0;

    foreach ($salesData as $sale) {
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            // Calculate cost using FIFO logic
            $costPerUnit = $this->calculateFIFOCost($product->id, $item->quantity, $sale->sale_date);
            $totalCostForItem = $costPerUnit * $item->quantity;
            $revenueForItem = $item->subtotal;
            $profitForItem = $revenueForItem - $totalCostForItem;

            $reportData[] = [
                'sale_id' => $sale->id,
                'sale_date' => $sale->sale_date,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'revenue' => $revenueForItem,
                'cost' => $totalCostForItem,
                'profit' => $profitForItem,
            ];

            $totalRevenue += $revenueForItem;
            $totalCost += $totalCostForItem;
            $totalProfit += $profitForItem;
        }
    }

    return response()->json([
        'data' => $reportData,
        'summary' => [
            'total_revenue' => round($totalRevenue, 2),
            'total_cost' => round($totalCost, 2),
            'total_profit' => round($totalProfit, 2),
            'profit_margin' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0
        ]
    ]);
}

// Helper method to calculate FIFO cost
private function calculateFIFOCost($productId, $quantity, $saleDate)
{
    $batches = DB::table('purchase_items')
        ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
        ->where('purchase_items.product_id', $productId)
        ->where('purchases.purchase_date', '<=', $saleDate)
        ->where('purchase_items.quantity_remaining', '>', 0)
        ->orderBy('purchases.purchase_date', 'asc')
        ->select('purchase_items.price', 'purchase_items.quantity_remaining')
        ->get();

    if ($batches->isEmpty()) {
        // Fallback: use latest purchase price
        $latestPrice = DB::table('purchase_items')
            ->where('product_id', $productId)
            ->orderBy('id', 'desc')
            ->value('price');
        return $latestPrice ?? 0;
    }

    $totalCost = 0;
    $remaining = $quantity;

    foreach ($batches as $batch) {
        if ($remaining <= 0) break;
        
        $deduct = min($remaining, $batch->quantity_remaining);
        $totalCost += $deduct * $batch->price;
        $remaining -= $deduct;
    }

    return $quantity > 0 ? ($totalCost / $quantity) : 0;
}

// Add this method for revenue breakdown
public function revenueBreakdown(Request $request)
{
    $fromDate = $request->from_date;
    $toDate = $request->to_date;

    $revenueData = Sale::select(
        DB::raw('DATE(sale_date) as date'),
        DB::raw('SUM(total_amount) as daily_revenue'),
        DB::raw('COUNT(*) as total_sales')
    )
    ->where('status', 'completed')
    ->whereBetween('sale_date', [$fromDate, $toDate])
    ->groupBy('date')
    ->orderBy('date', 'asc')
    ->get();

    $totalRevenue = Sale::where('status', 'completed')
        ->whereBetween('sale_date', [$fromDate, $toDate])
        ->sum('total_amount');

    return response()->json([
        'daily_breakdown' => $revenueData,
        'total_revenue' => round($totalRevenue, 2)
    ]);
}
}
