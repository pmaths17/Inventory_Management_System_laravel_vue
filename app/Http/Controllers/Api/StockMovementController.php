<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockMovement;

class StockMovementController extends Controller
{
    // LIST ALL STOCK MOVEMENTS
    public function index(Request $request)
    {
        $query = StockMovement::with('product');

        // Optional filters
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->type) {
            $query->where('type', $request->type); // 'in' or 'out'
        }
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($movements);
    }

    // SHOW SINGLE STOCK MOVEMENT
    public function show($id)
    {
        $movement = StockMovement::with('product')->findOrFail($id);
        return response()->json($movement);
    }
}
