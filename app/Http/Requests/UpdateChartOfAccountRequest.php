<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChartOfAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        return auth()->check() && ($user->isAdmin() || $user->isOwner());
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_global' => $this->has('is_global'),
            'is_active' => $this->has('is_active'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $chartOfAccountId = $this->route('chartOfAccount')?->id ?? $this->route('chart-of-account');

        return [
            'account_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('chart_of_accounts', 'account_code')->ignore($chartOfAccountId),
            ],
            'account_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(['Assets', 'Liability', 'Taxes', 'Revenue', 'COGS', 'Expense', 'Adjustments'])],
            'parent_account_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
            'is_global' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $chartOfAccountId = $this->route('chartOfAccount')?->id ?? $this->route('chart-of-account');

        $validator->after(function ($validator) use ($chartOfAccountId) {
            $isGlobal = (bool) $this->input('is_global');
            $storeIds = $this->input('store_ids', []);

            if (! $isGlobal && empty($storeIds)) {
                $validator->errors()->add('store_ids', 'Select at least one store or mark the account as global.');
            }

            $parentAccountId = $this->input('parent_account_id');
            if ($parentAccountId && (int) $parentAccountId === (int) $chartOfAccountId) {
                $validator->errors()->add('parent_account_id', 'An account cannot be its own parent.');
            }
        });
    }
}

