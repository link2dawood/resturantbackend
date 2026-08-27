<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $category = $this->route('inventoryCategory');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('inventory_categories', 'name')->ignore($category),
            ],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A category with that name already exists.',
        ];
    }
}
