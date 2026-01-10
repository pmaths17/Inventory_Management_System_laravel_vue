<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    // LIST CUSTOMERS
    public function index()
    {
        $customers = Customer::orderBy('name')->paginate(15);
        return response()->json($customers);
    }

    // CREATE CUSTOMER
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($request->all());
        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer
        ]);
    }

    // SHOW SINGLE CUSTOMER
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer);
    }

    // UPDATE CUSTOMER
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);
        $customer->update($request->all());
        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer
        ]);
    }

    // DELETE CUSTOMER
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete(); // soft delete if model uses SoftDeletes
        return response()->json(['message' => 'Customer deleted successfully']);
    }
}
