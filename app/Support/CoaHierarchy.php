<?php

namespace App\Support;

use App\Models\ChartOfAccount;
use Illuminate\Contracts\Validation\Validator;

/**
 * Shared Chart-of-Accounts hierarchy validation, used by the web form requests
 * and the API controller so the rules are enforced identically everywhere:
 *
 *  - codes must be a 4-digit number within their account-type range
 *  - a child's type must match its parent's type
 *  - detail (leaf) accounts cannot be used as parents
 *  - on CREATE only: a child's code must fall inside its parent's sub-range
 *
 * The sub-range rule is deliberately NOT enforced when re-parenting an existing
 * account. The tree lives in `parent_account_id`, and an admin must be able to
 * file any account under any category regardless of the number it was given
 * (e.g. moving 6310 Life Insurance under 6900 Insurance). On create the code is
 * derived from the chosen parent anyway, so the rule still holds there.
 */
class CoaHierarchy
{
    public static function applyTo(Validator $validator, array $input, bool $enforceChildCodeRange = true): void
    {
        // Only add hierarchy errors when the base fields are otherwise present.
        if ($validator->errors()->hasAny(['account_code', 'account_type'])) {
            return;
        }

        $code = (string) ($input['account_code'] ?? '');
        $type = (string) ($input['account_type'] ?? '');
        $parentId = $input['parent_account_id'] ?? null;

        if (! ctype_digit($code) || strlen($code) !== 4) {
            $validator->errors()->add('account_code', 'Account code must be a 4-digit number.');

            return;
        }

        $codeNum = (int) $code;

        // 1. Code within the account type's range.
        $range = ChartOfAccount::codeRangeForType($type);
        if ($range && ($codeNum < $range[0] || $codeNum > $range[1])) {
            $validator->errors()->add(
                'account_code',
                "{$type} accounts must use a code between {$range[0]} and {$range[1]}."
            );
        }

        // 2/3. Parent relationship checks.
        if ($parentId) {
            $parent = ChartOfAccount::find($parentId);

            if ($parent) {
                if ($parent->account_type !== $type) {
                    $validator->errors()->add(
                        'parent_account_id',
                        "Parent account type ({$parent->account_type}) must match this account's type ({$type})."
                    );
                }

                $childRange = ChartOfAccount::childCodeRangeForParent((string) $parent->account_code);

                if ($childRange === null) {
                    $validator->errors()->add(
                        'parent_account_id',
                        "Account {$parent->account_code} is a detail account and cannot be a parent."
                    );
                } elseif ($enforceChildCodeRange && ($codeNum < $childRange[0] || $codeNum > $childRange[1])) {
                    $validator->errors()->add(
                        'account_code',
                        "Under parent {$parent->account_code}, the code must be between {$childRange[0]} and {$childRange[1]}."
                    );
                }
            }
        }
    }
}
