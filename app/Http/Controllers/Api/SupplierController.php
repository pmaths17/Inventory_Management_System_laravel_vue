<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    // LIST SUPPLIERS
    public function index()
    {
        $suppliers = Supplier::orderBy('name')->paginate(15);
        return response()->json($suppliers);
    }

    // CREATE SUPPLIER
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $supplier = Supplier::create($request->all());

        return response()->json([
            'message' => 'Supplier created successfully',
            'supplier' => $supplier
        ]);
    }

    // SHOW SINGLE SUPPLIER
    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    // UPDATE SUPPLIER
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $supplier->update($request->all());

        return response()->json([
            'message' => 'Supplier updated successfully',
            'supplier' => $supplier
        ]);
    }

    // DELETE SUPPLIER
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete(); // soft delete if model uses SoftDeletes
        return response()->json([
            'message' => 'Supplier deleted successfully'
        ]);
    }
}
