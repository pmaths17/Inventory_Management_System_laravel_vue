<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // LIST SALES
    public function index()
    {
        $sales = Sale::with('customer')
            ->withCount('items')
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        return response()->json($sales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                // Create sale
                $sale = Sale::create([
                    'customer_id' => $request->customer_id,
                    'sale_date' => $request->sale_date,
                    'total_amount' => 0,
                    'status' => 'completed',
                    'created_by' => auth()->guard()->id(), // replace with auth()->id() in real app
                ]);

                $total = 0;

                foreach ($request->items as $item) {
                    $subtotal = $item['quantity'] * $item['price'];

                    // Create sale item
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal,
                    ]);

                    // Decrease stock
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'type' => 'out',
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                    ]);

                    $total += $subtotal;
                }

                $sale->update(['total_amount' => $total]);

                return response()->json([
                    'message' => 'Sale created successfully',
                    'sale' => $sale->load('items'),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    // SHOW SINGLE SALE
    public function show($id)
    {
        $sale = Sale::with(['customer', 'items.product'])
            ->findOrFail($id);

        return response()->json($sale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
