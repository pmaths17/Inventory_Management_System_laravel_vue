<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.i.e list products
     */
    public function index()
    {
        $products = Product::withCount('purchaseItems','saleItems')->orderBy('name')->paginate(15);
        //include current stock in response
        $products->getCollection()->transform(function($product){
            $product->current_stock = $product->current_stock;
            return $product;
        });
        return response()->json($products);
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
