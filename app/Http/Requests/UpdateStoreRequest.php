<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $store = $this->route('store');

        // Check if user is authenticated
        if (! $user) {
            return false;
        }

        // Admin can update any store
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can update stores they created
        if ($user->isOwner() && $store && $store->created_by === $user->id) {
            return true;
        }

        // Manager cannot update stores (only view and create reports)
        // This maintains the business logic where only owners/admins can modify store information
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_info' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\(\d{3}\)\s\d{3}-\d{4}$/',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'created_by' => 'required|exists:users,id',
            'sales_tax_rate' => 'required|numeric|min:0|max:100',
            'medicare_tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
