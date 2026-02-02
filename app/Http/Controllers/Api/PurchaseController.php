<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PurchaseController extends Controller
{
    public function store(StorePurchaseRequest $request)
    {
        // return "testr";
        try {
            // Log::info('Purchase store called', ['request' => $request->all()]);
            return DB::transaction(function () use ($request) {
                Log::info('Creating purchase...');
                //To create a purchase
                $purchase = Purchase::create([
                    'supplier_id' => $request->supplier_id,
                    'purchase_date' => $request->purchase_date,
                    'total_amount' => 0,
                    'status' => 'posted',
                    'created_by' => auth()->guard()->id(),
                    // 'created_by' => 1,
                ]);
                Log::info('Purchase created', ['purchase_id' => $purchase->id]);

                $total = 0;

                foreach ($request->items as $item) {
                    Log::info("Processing item #index", $item);
                    $subtotal = $item['quantity'] * $item['price'];

                    $purchaseItem = PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'quantity_remaining' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal,
                    ]);
                    Log::info('Purchase item created', ['purchase_item_id' => $purchaseItem->id]);
                    //increase stock
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'type' => 'in',
                        'reference_type' => 'purchase',
                        'reference_id' => $purchase->id,
                    ]);
                    // Log::info('Stock movement created', ['product_id' => $item['product_id']]);
                    $total += $subtotal;
                }

                $purchase->update(['total_amount' => $total]);
                // Log::info('Purchase total updated', ['total' => $total]);
                return response()->json([
                    'message' => 'Purchase created successfully',
                    'purchase' => $purchase->load('items'),
                ]);
            });
        }
        catch (\Exception $e) {
            // Log the error to Laravel log
            \Log::error('Purchase save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            // Return proper error response
            return response()->json([
                'message' => 'Failed to save purchase',
                'error' => $e->getMessage()
            ], 500);
        };
    }


    // LIST PURCHASES
    public function index()
    {
        // $purchases = Purchase::with('supplier')
        $purchases = Purchase::with(['supplier', 'items.product'])
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
