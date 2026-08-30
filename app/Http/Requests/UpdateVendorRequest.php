<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isOwner());
    }

    public function rules(): array
    {
        // The route model is bound by id, so ignore this vendor's own identifier.
        $vendorId = $this->route('vendor');

        return [
            'vendor_name' => ['required', 'string', 'max:100'],
            'vendor_identifier' => [
                'nullable', 'string', 'max:100',
                Rule::unique('vendors', 'vendor_identifier')->ignore($vendorId),
            ],
            'vendor_type' => ['required', 'in:Food,Beverage,Supplies,Utilities,Services,Other'],
            'default_coa_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'default_transaction_type_id' => ['nullable', 'exists:transaction_types,id'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['exists:stores,id'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'order_method' => ['nullable', 'in:online,phone,in_person,email'],
            'order_notes' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_identifier.unique' => 'That vendor identifier is already used by another vendor.',
            'website.url' => 'Enter the full website address, including https://',
        ];
    }
}
