<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
public function index(Request $request)
{
    $query = Product::query();

    // 🔹 Stock calculation (SQL-level)
    $stockSql = "
        COALESCE(
            (
                SELECT SUM(
                    CASE
                        WHEN type = 'in' THEN quantity
                        ELSE -quantity
                    END
                )
                FROM stock_movements
                WHERE stock_movements.product_id = products.id
            ),
            0
        )
    ";

    // Inject stock into SELECT
    $query->select('products.*')
          ->selectRaw("$stockSql AS current_stock");

    // 1️⃣ Search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    // 2️⃣ Stock filter
    $stockFilterApplied = false;
    if (in_array($request->stock_status, ['out', 'low'])) {
        $stockFilterApplied = true;
        if ($request->stock_status === 'out') {
            $query->whereRaw("($stockSql) <= 0");
        } else {
            $query->whereRaw("($stockSql) > 0 AND ($stockSql) <= 10");
        }
    }

    // 3️⃣ Sorting
    $sort = $request->get('sort_by');

    if ($stockFilterApplied || $sort === 'stock' || !$sort) {
        // Stock filter applied OR stock sort OR initial load → order by ID
        $query->orderBy('id', 'asc');
    } elseif ($sort === 'price') {
        $query->orderBy('sale_price', 'desc');
    } elseif ($sort === 'name') {
        $query->orderBy('name', 'asc');
    }
$perPage = $request->query('per_page', 15);
    return response()->json(
        $query->paginate($perPage));
}



    /**
     * Store a newly created resource in storage.i.e create product
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        $product->current_stock=$product->current_stock;
        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ]);
    }

    /**
     * Display the specified resource.i.e. show single product
     */
    public function show($id)
    {
        $product = Product::with('purchaseItems', 'saleItems')->findOrFail($id);
        $product->current_stock = $product->current_stock;
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.i.e to update an existing record
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());
        $product->current_stock = $product->current_stock;
        return response() -> json([
            'message'=>'Product updated successfully',
            'product'=>$product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product= Product::findOrFail($id);
        $product->delete();
        return response()->json([
            'message'=>'Product deleted(soft delete)'
        ]);
    }
}
