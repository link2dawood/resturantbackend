<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware (role + store resolution in the controller) authorizes.
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer'],
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
