<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(StorePurchaseRequest $request)
    {
        // return "testr";
        try {
            return DB::transaction(function () use ($request) {
                //To create a purchase
                $purchase = Purchase::create([
                    'supplier_id' => $request->supplier_id,
                    'purchase_date' => $request->purchase_date,
                    'total_amount' => 0,
                    'status' => 'posted',
                    'created_by' => auth()->guard()->id(),
                    // 'created_by' => 1,
                ]);

                $total = 0;

                foreach ($request->items as $item) {
                    $subtotal = $item['quantity'] * $item['price'];

                    $purchaseItem = PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal,
                    ]);
                    //increase stock
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'type' => 'in',
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                    ]);
                    $total += $subtotal;
                }

                $purchase->update(['total_amount' => $total]);
                return response()->json([
                    'message' => 'Purchase created successfully',
                    'purchase' => $purchase->load('items'),
                ]);
            });
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    // LIST PURCHASES
    public function index()
    {
        $purchases = Purchase::with('supplier')
            // ->withSum('items', 'quantity')
            ->withCount('items')
            ->orderBy('purchase_date', 'desc')
            ->paginate(15); // pagination recommended in real ERP

        return response()->json($purchases);
    }

    // SHOW SINGLE PURCHASE
    public function show($id)
    {
        $purchase = Purchase::with(['supplier', 'items.product'])
            ->findOrFail($id);

        return response()->json($purchase);
    }
}
