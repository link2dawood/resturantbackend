<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
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

        // The code is auto-assigned from the chosen parent (the form has no
        // free-text code field) — derive it server-side so it's authoritative.
        $parentId = $this->input('parent_account_id');
        if ($parentId) {
            $parent = \App\Models\ChartOfAccount::find($parentId);
            $next = $parent ? \App\Models\ChartOfAccount::nextChildCode((string) $parent->account_code) : null;
            if ($next) {
                $this->merge(['account_code' => $next]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'account_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('chart_of_accounts', 'account_code'),
            ],
            'account_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(['Assets', 'Liability', 'Equity', 'Taxes', 'Revenue', 'COGS', 'Expense', 'Adjustments'])],
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
        $validator->after(function ($validator) {
            $isGlobal = (bool) $this->input('is_global');
            $storeIds = $this->input('store_ids', []);

            if (! $isGlobal && empty($storeIds)) {
                $validator->errors()->add('store_ids', 'Select at least one store or mark the account as global.');
            }

            // Owners may only assign accounts to their own stores.
            $user = auth()->user();
            if ($user && ! $user->isAdmin() && ! empty($storeIds)) {
                $accessible = $user->getAccessibleStoreIds();
                foreach ($storeIds as $sid) {
                    if (! in_array((int) $sid, $accessible, true)) {
                        $validator->errors()->add('store_ids', 'You can only assign accounts to your own stores.');
                        break;
                    }
                }
            }

            \App\Support\CoaHierarchy::applyTo($validator, $this->all());
        });
    }
}


