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
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->items as $item) {
                $product = Product::find($item['product_id']);
                if ($item['price'] != $product->sale_price) {
                    $validator->errors()->add(
                        'items',
                        "Price for product ID {$product->id} must be {$product->sale_price}"
                    );
                }
                // if ($item['quantity'] > $product->current_stock) {
                if ($item['quantity'] > $product->available_stock) {
                    $validator->errors()->add(
                        'items',
                        "Insufficient stock for product ID {$product->id}. Available: {$product->current_stock}"
                    );
                }
            }
        });
    }
}
