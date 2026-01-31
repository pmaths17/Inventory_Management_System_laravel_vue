<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id'); // get product id from route

        return [
            'name' => 'required|string|max:255',
            // 'sku' => [
            //     'required',
            //     'string',
            //     'max:100',
            //     Rule::unique('products', 'sku')->ignore($productId),
            // ],
            'sku' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z]{3}-[0-9]{5}$/',
                Rule::unique('products', 'sku')->ignore($productId),
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
            'sku.regex' => 'SKU must be in format ABC-12345',
            'sku.unique' => 'This SKU is already used by another product.',
        ];
    }
}
