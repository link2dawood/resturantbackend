<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isOwner() || $user->isManager());
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', Rule::in($this->user()->getAccessibleStoreIds())],
            'name' => ['required', 'string', 'max:150'],
            'inventory_category_id' => ['required', 'integer', 'exists:inventory_categories,id'],
            'base_unit' => ['required', 'string', 'max:20'],
            'purchase_unit' => ['required', 'string', 'max:20'],
            // The pack size. Editable for the life of the item: suppliers change
            // pack sizes, and a stale value silently corrupts every order and
            // variance figure derived from it.
            'units_per_purchase' => ['required', 'numeric', 'min:0.0001', 'max:99999999'],
            'portion_size' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'portion_unit' => ['nullable', 'required_with:portion_size', 'string', 'max:20'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'safety_buffer_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reorder_threshold' => ['nullable', 'numeric', 'min:0'],
            // Vendor mapping table, keyed by vendor id. Absent means "leave the
            // existing mappings alone"; present and empty means "clear them".
            'vendors' => ['nullable', 'array'],
            'vendors.*.enabled' => ['nullable', 'boolean'],
            'vendors.*.vendor_sku' => ['nullable', 'string', 'max:100'],
            'vendors.*.current_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'vendors.*.is_preferred' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The `vendors` keys are vendor ids, which `vendors.*` rules cannot reach.
     * Check them here so an unknown id is a validation error, not a foreign key
     * exception at save time.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = array_keys((array) $this->input('vendors', []));

            if (empty($ids)) {
                return;
            }

            $known = \App\Models\Vendor::whereIn('id', $ids)->pluck('id')->all();

            foreach ($ids as $id) {
                if (! in_array((int) $id, $known, true)) {
                    $validator->errors()->add("vendors.$id", 'That vendor does not exist.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'store_id.in' => 'You do not have access to that store.',
            'units_per_purchase.min' => 'Portions per unit must be greater than zero.',
            'portion_unit.required_with' => 'Give the portion size a unit, for example oz.',
        ];
    }

    public function attributes(): array
    {
        return [
            'units_per_purchase' => 'portions per unit',
            'inventory_category_id' => 'category',
            'purchase_unit' => 'unit',
        ];
    }
}
