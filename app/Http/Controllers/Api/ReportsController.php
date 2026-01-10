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

    public function supplierWisePurchases(Request $request){
        return response()->json(
            Purchase::select('supplier_id', DB::raw('SUM(total_amount) as total'))
            ->with('supplier')
            ->whereBetween('purchase_date',[$request->from_date,$request->to_date])
            ->groupBy('supplier_id')
            ->get()
        );
    }

    public function salesList(Request $request){
        $query=Sale::with('customer');
        if($request->from_date && $request->to_date){
            $query->whereBetween('sale_date',[$request->from_date,$request->to_date]);
        }
        if($request->customer_id){
            $query->where('customer_id',$request->customer_id);
        }
        return response()->json($query->paginate(15));
    }
    public function salesdetails($id){
        return response()->json(
            Sale::with(['customer','items.product'])->findOrFail($id)
        );
    }
    public function customerWiseSales(Request $request){
        return response()->json(
            Sale::select('customer_id', DB::raw('SUM(total_amount) as total'))
            ->with('customer')
            ->whereBetween('sale_date',[$request->from_date,$request->to_date])
            ->groupBy('customer_id')
            ->get()
        );
    }


    public function productWiseSales(Request $request){
        return response()->json(
            DB::table('sale_items')
            ->join('products','sale_items.product_id','=','products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as revenue')
            )
            ->groupBy('products.id','products.name')
            ->get()
        );
    }

    public function revenue(Request $request){
        return response()->json([
            'revenue'=>Sale::whereBetween('sale_date',[$request->from_date,$request->to_date])
            ->sum('total_amount')
        ]);
    }

    public function purchaseCost(Request $request){
        return response()->json([
            'cost' => Purchase::whereBetween('purchase_date',[$request->from_date,$request->to_date])
            ->sum('total_amount')
        ]);
    }

    public function profit(Request $request){
        $sale = Sale::whereBetween('sale_date',[$request->from_date,$request->to_date])
        ->sum('total_amount');
        $purchase = Purchase::whereBetween('purchase_date',[$request->from_date,$request->to_date])
        ->sum('total_amount');
        return response()->json([
            'profit'=>$sale-$purchase
        ]);
    }
}
