<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            // 'sale_date' => 'required|date',
            'sale_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    // Only allow today's date
                    if ($value !== now()->toDateString()) {
                        $fail('Purchases can only be created for today.');
                    }
                }
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }
    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         foreach ($this->items as $item) {
    //             $product = Product::find($item['product_id']);
    //             if ($item['price'] != $product->sale_price) {
    //                 $validator->errors()->add(
    //                     'items',
    //                     "Price for product ID {$product->id} must be {$product->sale_price}"
    //                 );
    //             }
    //             // if ($item['quantity'] > $product->current_stock) {
    //             if ($item['quantity'] > $product->available_stock) {
    //                 $validator->errors()->add(
    //                     'items',
    //                     "Insufficient stock for product ID {$product->id}. Available: {$product->current_stock}. Locked Stock:{$product->available_stock}"
    //                 );
    //             }
    //         }
    //     });
    // }
    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // Collect all items to check if user didn't select same product twice
        foreach ($this->items as $item) {
            $product = Product::find($item['product_id']);
            
            if (!$product) continue;

            // 1. Price Check
            if ($item['price'] != $product->sale_price) {
                $validator->errors()->add(
                    'items',
                    "Price for {$product->name} must be {$product->sale_price}"
                );
            }

            // 2. Stock Check
            // available_stock is usually (current_stock - locked_stock)
            if ($item['quantity'] > $product->available_stock) {
                $locked = $product->locked_stock; // Reserved by other draft sales
                $actualAvailable = $product->available_stock;

                $validator->errors()->add(
                    'items',
                    "Insufficient stock for {$product->name}. " . 
                    "Total in warehouse: {$product->current_stock}, " .
                    "Locked in other drafts: {$locked}, " .
                    "Truly Available: {$actualAvailable}."
                );
            }
        }
    });
}
}
