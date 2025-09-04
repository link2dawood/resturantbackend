<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['owner', 'admin', 'manager']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date', 'before_or_equal:today'],
            'store_id' => ['required', 'exists:stores,id'],
            'projected_sales' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'amount_of_cancels' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'amount_of_voids' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'number_of_no_sales' => ['required', 'integer', 'min:0', 'max:99999'],
            'total_coupons' => ['required', 'integer', 'min:0', 'max:99999'],
            'gross_sales' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'coupons_received' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'adjustments_overrings' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'total_customers' => ['required', 'integer', 'min:0', 'max:99999'],
            'net_sales' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'tax' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'average_ticket' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'sales' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'total_paid_outs' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'credit_cards' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cash_to_account' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'actual_deposit' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'short' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'over' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'page_number' => ['required', 'integer', 'min:1', 'max:999'],
            'weather' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z\s\-]+$/'],
            'holiday_event' => ['nullable', 'string', 'max:100'],
            
            // Transaction validations
            'transactions' => ['required', 'array', 'min:1'],
            'transactions.*.transaction_type_id' => ['required', 'exists:transaction_types,id'],
            'transactions.*.amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            
            // Revenue validations
            'revenues' => ['required', 'array', 'min:1'],
            'revenues.*.revenue_income_type_id' => ['required', 'exists:revenue_income_types,id'],
            'revenues.*.amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'report_date.before_or_equal' => 'Report date cannot be in the future.',
            'store_id.exists' => 'Selected store does not exist.',
            'weather.regex' => 'Weather description can only contain letters, spaces, and hyphens.',
            '*.numeric' => 'The :attribute must be a valid number.',
            '*.min' => 'The :attribute must be at least :min.',
            '*.max' => 'The :attribute cannot exceed :max.',
            'transactions.*.transaction_type_id.exists' => 'Invalid transaction type selected.',
            'revenues.*.revenue_income_type_id.exists' => 'Invalid revenue income type selected.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize numeric fields
        $numericFields = [
            'projected_sales', 'amount_of_cancels', 'amount_of_voids', 'gross_sales',
            'coupons_received', 'adjustments_overrings', 'net_sales', 'tax',
            'average_ticket', 'sales', 'total_paid_outs', 'credit_cards',
            'cash_to_account', 'actual_deposit', 'short', 'over'
        ];

        $sanitized = [];
        foreach ($numericFields as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = round(floatval($this->$field), 2);
            }
        }

        // Sanitize integer fields
        $integerFields = ['number_of_no_sales', 'total_coupons', 'total_customers', 'page_number'];
        foreach ($integerFields as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = intval($this->$field);
            }
        }

        $this->merge($sanitized);
    }
}
