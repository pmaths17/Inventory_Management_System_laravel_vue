<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // adjust if using auth
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0.01'
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->items as $item) {
                $product = Product::find($item['product_id']);

                // Optional: validate stock if needed (for returns or negative stock)
                // if ($item['quantity'] > some logic) ...

                // Validate purchase price
                if ($item['price'] != $product->purchase_price) {
                    $validator->errors()->add(
                        'items',
                        "Price for product ID {$product->id} must be {$product->purchase_price}"
                    );
                }
            }
        });
    }
}
