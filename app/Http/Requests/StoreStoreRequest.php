<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['owner', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_info' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\.]+$/'],
            'contact_name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'phone' => ['required', 'string', 'regex:/^[\+]?[1-9][\d]{0,15}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'state' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'zip' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
            'sales_tax_rate' => ['required', 'numeric', 'between:0,99.99'],
            'medicare_tax_rate' => ['required', 'numeric', 'between:0,99.99'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'store_info.regex' => 'Store info can only contain letters, numbers, spaces, hyphens, and dots.',
            'contact_name.regex' => 'Contact name can only contain letters, spaces, hyphens, and dots.',
            'phone.regex' => 'Phone number format is invalid.',
            'city.regex' => 'City name can only contain letters, spaces, hyphens, and dots.',
            'state.regex' => 'State must be a 2-letter uppercase abbreviation.',
            'zip.regex' => 'ZIP code must be in format 12345 or 12345-6789.',
            'sales_tax_rate.between' => 'Sales tax rate must be between 0.00 and 99.99.',
            'medicare_tax_rate.between' => 'Medicare tax rate must be between 0.00 and 99.99.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[^\d\+]/', '', $this->phone),
            'state' => strtoupper($this->state),
            'zip' => trim($this->zip),
        ]);
    }
}
