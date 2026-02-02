<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    // LIST SALES
    public function index()
    {
        $sales = Sale::with('customer')
            ->withCount('items')
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        return response()->json($sales);
    }
    public function store(StoreSaleRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $action = $request->input('action', 'draft'); // default = draft
                $items  = $request->items;
                $sale = Sale::create([
                    'customer_id' => $request->customer_id,
                    'sale_date'   => $request->sale_date,
                    'total_amount' => 0,
                    'status'      => $action === 'completed' ? 'completed' : 'draft',
                    'created_by'  => Auth::id() ?? 1,
                ]);
                $totalAmount = 0;
                foreach ($items as $item) {
                    // LOCK ROW (important)
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    // \Log::info("Checking stock for product {$product->id} - requested: {$item['quantity']}, available: {$product->available_stock}");
                    // stock check
                    if ($item['quantity'] > $product->available_stock) {
                        // \Log::error("Insufficient stock for product {$product->id} - requested: {$item['quantity']}, available: {$product->available_stock}");
                        throw new \Exception("Insufficient stock for {$product->name}");
                        }
                        $subtotal = $item['quantity'] * $item['price'];
                        $totalAmount += $subtotal;
                        // sale item
                        SaleItem::create([
                            'sale_id'   => $sale->id,
                            'product_id' => $product->id,
                            'quantity'  => $item['quantity'],
                            'price'     => $item['price'],
                            'subtotal'  => $subtotal,
                            ]);
                            \Log::info("SALE item created");
                    if ($action === 'draft') {
                        // reserve stock only
                        $product->lockStock($item['quantity']);
                    }
                    // \Log::info("Deducting stock FIFO for product {$product->id}, quantity: {$item['quantity']}");
                    if ($action === 'completed') {
                        $this->deductStockFIFO($product, $item['quantity'], $sale->id);
                    }
                }
                $sale->update(['total_amount' => $totalAmount]);
                return response()->json([
                    'message' => 'Sale saved successfully',
                    // 'sale'    => $sale->load('items'),
                    'sale'    => $sale->load(['items.product', 'customer']),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        // $sale = Sale::with('items.product')->findOrFail($id);
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);
        foreach ($sale->items as $item) {
            $product = $item->product;
            if ($product) {
                // locked stock from all other drafts
                $lockedStock = SaleItem::where('product_id', $product->id)
                    ->whereHas('sale', fn($q) => $q->where('status', 'draft')->where('id', '!=', $sale->id))
                    ->sum('quantity');
                // available stock = current stock - locked by others + this item's qty
                // $productLocked = $product->locked_stock;
                // $item->available_stock = $product->current_stock - $productLocked + $item->quantity;
                $currentDraftQty = $item->quantity;
                $item->available_stock = $product->current_stock - $lockedStock - $currentDraftQty;
                // $product->available_stock;
                // Log all details
                \Log::info(
                    "DEBUG Sale {$sale->id} - Product {$product->id}: "
                        . "current_stock={$product->current_stock}, "
                        . "locked_by_others={$lockedStock}, "
                        . "this_item_qty={$item->quantity}, "
                        . "current_draft_qty={$currentDraftQty}, "
                        . "available_stock={$item->available_stock}"
                );
            }
        }
        return response()->json($sale);
    }
    public function destroy(string $id)
    {
        //
    }
    public function update(Request $request, string $id)
    {
        // \Log::info('Update hit', [
        //     'id' => $id,
        //     'request' => $request->all()
        // ]);
        try {
            return DB::transaction(function () use ($request, $id) {
                // Lock the sale row to prevent concurrent updates
                $sale = Sale::with('items')->lockForUpdate()->findOrFail($id);
                // \Log::info('Sale fetched', ['sale' => $sale->toArray()]);
                // Only draft sales can be updated
                if ($sale->status !== 'draft') {
                    return response()->json(['error' => 'Only draft sales can be updated'], 403);
                }
                $action = $request->input('action', 'draft'); // draft or completed
                $items  = $request->items ?? [];
                if (empty($items)) {
                    return response()->json(['error' => 'Sale must have at least one item'], 422);
                }
                // \Log::info('Items data', ['items' => $request->items]);
                // Unlock stock from old items first (reverse previous draft reservations)
                foreach ($sale->items as $oldItem) {
                    $product = Product::lockForUpdate()->findOrFail($oldItem->product_id);
                    $product->unlockStock($oldItem->quantity);
                }
                // Delete old items
                $sale->items()->delete();
                $totalAmount = 0;
                // Add new items and handle stock
                foreach ($items as $item) {
                    // Lock the product row
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    // Check stock availability
                    if ($item['quantity'] > $product->available_stock) {
                        throw new \Exception("Insufficient stock for product {$product->name}. Available: {$product->available_stock}");
                    }
                    $subtotal = $item['quantity'] * $item['price'];
                    $totalAmount += $subtotal;
                    // \Log::info('Ready to update sale');
                    // Create sale item
                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $product->id,
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'],
                        'subtotal'   => $subtotal,
                    ]);
                    // \Log::info('sale created');

                    if ($action === 'draft') {
                        // Reserve stock for draft
                        $product->lockStock($item['quantity']);
                    }
                    // \Log::info('Stock Locked');
                    if ($action === 'completed') {
                        $this->deductStockFIFO($product, $item['quantity'], $sale->id);
                    }
                    // \Log::info('Deducted fifo stock');
                }
                // Update sale info
                $sale->update([
                    'customer_id' => $request->customer_id,
                    'sale_date'   => $request->sale_date,
                    'total_amount' => $totalAmount,
                    'status'      => $action === 'completed' ? 'completed' : 'draft',
                ]);
                // \Log::info('Products array', ['products' => $products ?? null]);
                // \Log::info('Draft items', ['items' => $items]);


                return response()->json([
                    'message' => 'Sale updated successfully',
                    'sale'    => $sale->load('items'),
                ]);
            }, 5); // retry 5 times on deadlock
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    protected function deductStockFIFO(Product $product, int $quantity, int $saleId)
    {
        // \Log::info("Starting deductStockFIFO for product {$product->id}, quantity: {$quantity}");
        $remaining = $quantity;
        foreach ($product->fifoBatches()->lockForUpdate()->get() as $batch) {
            if ($batch->quantity_remaining <= 0) continue; // skip empty batch
            // \Log::info("FIFO Batch {$batch->id} - remaining: {$batch->quantity_remaining}, remaining needed: {$remaining}");
            if ($remaining <= 0) break;
            $deduct = min($remaining, $batch->quantity_remaining);
            // \log::info("Deducted");
            $batch->quantity_remaining -= $deduct;
            $batch->save();
            \Log::info("Saved");
            StockMovement::create([
                'product_id'     => $product->id,
                'quantity'       => $deduct,
                'type'           => 'out',
                'reference_type' => 'sale',
                'reference_id'   => $saleId,
            ]);
            $remaining -= $deduct;
        }
        if ($remaining > 0) {
            throw new \Exception("Not enough stock for {$product->name}");
        }
    }
}
