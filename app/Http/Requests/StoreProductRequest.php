<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow all users (adjust later if needed)
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // 'sku' => 'required|string|max:100|unique:products,sku',
            'sku' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z]{3}-[0-9]{5}$/',
                'unique:products,sku',
            ],
            // 'purchase_price' => 'required|numeric|min:0',
            // 'sale_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($value <= $this->purchase_price) {
                        $fail('Sale price must be greater than purchase price.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already used by another product.',
            'sku.regex' => 'SKU must be in format ABC-12345',
        ];
    }
}
