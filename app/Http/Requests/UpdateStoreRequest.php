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

        // Corporate Stores: Controlled by Franchisor, report to Franchisor, run by Managers
        // Only Franchisor (or Admin) can update Corporate Stores
        if ($store && $store->isCorporateStore()) {
            return $user->isFranchisor();
        }

        // Franchisee locations: Owner can update stores they created
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
            'store_type' => 'required|in:corporate,franchisee',
            'created_by' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value);
                    if (! $user || ! $user->isOwner()) {
                        $fail('Only owners can be assigned to stores. Admins cannot be assigned.');
                    }
                    
                    // Corporate Stores must be controlled by Franchisor
                    if ($this->input('store_type') === 'corporate' && !$user->isFranchisor()) {
                        $fail('Corporate Stores must be controlled by the Franchisor.');
                    }
                },
            ],
            'sales_tax_rate' => 'required|numeric|min:0|max:100',
            'medicare_tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
