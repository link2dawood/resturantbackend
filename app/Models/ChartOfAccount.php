<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    public const MERCHANT_PROCESSING_FEE_CODES = ['6100', '6000'];

    /**
     * Allowed 4-digit code range per account type. New accounts must use a code
     * inside their type's range (Expense -> 6000-6999, COGS -> 5000-5999, ...).
     */
    public const TYPE_CODE_RANGES = [
        'Assets' => [1000, 1999],
        'Liability' => [2000, 2999],
        'Taxes' => [3000, 3999],
        'Revenue' => [4000, 4999],
        'COGS' => [5000, 5999],
        'Expense' => [6000, 6999],
        'Adjustments' => [7000, 7999],
        'Equity' => [8000, 8999],
    ];

    /** @return array{0:int,1:int}|null [min, max] code for an account type. */
    public static function codeRangeForType(?string $type): ?array
    {
        return self::TYPE_CODE_RANGES[$type] ?? null;
    }

    /**
     * The allowed numeric child-code range under a parent, derived from the
     * parent's trailing-zero "block":
     *   6000 (X000 root) -> 6001-6999
     *   6400 (XY00)      -> 6401-6499
     *   6450 (XYZ0)      -> 6451-6459   (e.g. Online Merchant -> DoorDash/Uber/…)
     *   6451 (leaf)      -> null (a detail account cannot be a parent)
     *
     * @return array{0:int,1:int}|null
     */
    public static function childCodeRangeForParent(string $parentCode): ?array
    {
        if (! ctype_digit($parentCode) || strlen($parentCode) !== 4) {
            return null;
        }

        $code = (int) $parentCode;

        if ($code % 1000 === 0) {
            return [$code + 1, $code + 999];
        }
        if ($code % 100 === 0) {
            return [$code + 1, $code + 99];
        }
        if ($code % 10 === 0) {
            return [$code + 1, $code + 9];
        }

        return null;
    }

    /**
     * The code blocks a 4-digit code rolls into, nearest first — pure arithmetic,
     * no database lookup:
     *
     *   6451 -> [6450, 6400, 6000]
     *   6450 -> [6400, 6000]
     *   6100 -> [6000]
     *   6000 -> []          (X000 is a type root)
     *
     * @return array<int, int>
     */
    public static function parentCodeCandidates(string $accountCode): array
    {
        if (! ctype_digit($accountCode) || strlen($accountCode) !== 4) {
            return [];
        }

        $n = (int) $accountCode;
        if ($n % 1000 === 0) {
            return [];
        }

        $candidates = [];
        if ($n % 100 !== 0) {
            $candidates[] = intdiv($n, 10) * 10;   // XYZ0 (e.g. 6450 for 6451)
        }
        $candidates[] = intdiv($n, 100) * 100;     // XY00
        $candidates[] = intdiv($n, 1000) * 1000;   // X000

        return array_values(array_unique(array_filter($candidates, fn ($c) => $c !== $n)));
    }

    /**
     * Best-guess parent CODE for a 4-digit account, from its number: the nearest
     * block that actually exists (6451 -> 6450 if present, else 6400, else 6000).
     *
     * NOTE: this is only a seeding/backfill heuristic, used to give the chart an
     * initial shape. Once `parent_account_id` is set it is the source of truth —
     * an admin can move an account to any category regardless of its number.
     */
    public static function inferParentCode(string $accountCode): ?string
    {
        foreach (static::parentCodeCandidates($accountCode) as $candidate) {
            if (static::withoutGlobalScopes()->where('account_code', (string) $candidate)->exists()) {
                return (string) $candidate;
            }
        }

        return null;
    }

    /** True if this account is itself a rollup "total" row. */
    public function isRollupTotal(): bool
    {
        return in_array((string) $this->account_code, self::totalRollupAccountCodes(), true);
    }

    /**
     * Non-blocking warning shown when an account is a rollup total, or rolls up
     * into one (so the user doesn't double-count). Returns null if neither.
     */
    public static function rollupWarningFor(string $code, ?string $parentCode = null): ?string
    {
        $rollups = self::totalRollupAccountCodes();

        if (in_array($code, $rollups, true)) {
            return "Account {$code} is a rollup total that sums its sub-accounts. Post day-to-day transactions to its child accounts, not to this total directly.";
        }

        $effectiveParent = $parentCode;

        // No explicit parent chosen: warn if the code numerically rolls into a
        // total. This is about the number block, so it doesn't depend on which
        // ancestor rows happen to exist yet.
        if (! $effectiveParent) {
            foreach (self::parentCodeCandidates($code) as $candidate) {
                if (in_array((string) $candidate, $rollups, true)) {
                    $effectiveParent = (string) $candidate;
                    break;
                }
            }
        }

        if ($effectiveParent && in_array($effectiveParent, $rollups, true)) {
            return "This account rolls up into the {$effectiveParent} total — avoid also posting the same amounts to {$effectiveParent} directly (it would double-count).";
        }

        return null;
    }

    /**
     * Account codes that are rollup "totals" (sum of rows below). Hidden from
     * transaction-type/COA dropdowns on daily reports and owner CC statements.
     */
    public static function totalRollupAccountCodes(): array
    {
        return [
            '6200', // Equipment Total
            '6300', // Insurance Total
            '6400', // Marketing Total
            '6450', // Online Merchant Expenses - Total
            '6500', // Rent Total
            '6600', // Payroll Total
            '6700', // Professional Services Total
            '6800', // Permits and Fees Total
            '6900', // Travel and Expense Total
            '6950', // Utilities Total
        ];
    }

    public static function merchantProcessingFeesAccount(): ?self
    {
        return static::query()
            ->where(function ($query) {
                $query->whereIn('account_code', self::MERCHANT_PROCESSING_FEE_CODES)
                    ->orWhere('account_name', 'Merchant Processing Fees')
                    ->orWhere('account_name', 'Merchant Processing Fees (CC)')
                    ->orWhere('account_name', 'like', 'Merchant Processing Fees%');
            })
            ->orderByRaw("CASE WHEN account_code = '6100' THEN 0 WHEN account_name = 'Merchant Processing Fees' THEN 1 ELSE 2 END")
            ->first();
    }

    /**
     * All active chart rows that represent in-store / credit card merchant processing fees (e.g. 6100, 6000).
     *
     * @return array<int, int>
     */
    public static function merchantProcessingFeeCoaIds(): array
    {
        return static::query()
            ->where(function ($query) {
                $query->whereIn('account_code', self::MERCHANT_PROCESSING_FEE_CODES)
                    ->orWhere('account_name', 'Merchant Processing Fees')
                    ->orWhere('account_name', 'Merchant Processing Fees (CC)')
                    ->orWhere('account_name', 'like', 'Merchant Processing Fees%');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * COA ids for Merchant Fee Analytics: credit card / in-store processing fees only (not Grubhub/Uber/DoorDash expense accounts).
     */
    public static function merchantFeeAnalyticsCoaIds(): array
    {
        return static::merchantProcessingFeeCoaIds();
    }

    public static function thirdPartyPlatformExpenseAccount(string $platform): ?self
    {
        $platform = strtolower(trim($platform));

        $mapping = [
            'doordash' => ['6451', 'Doordash Expense'],
            'grubhub' => ['6452', 'Grubhub Expense'],
            'ubereats' => ['6453', 'Uber Expense'],
        ];

        if (! isset($mapping[$platform])) {
            return null;
        }

        [$accountCode, $accountName] = $mapping[$platform];

        return static::query()
            ->where(function ($query) use ($accountCode, $accountName) {
                $query->where('account_code', $accountCode)
                    ->orWhere('account_name', $accountName);
            })
            ->orderByRaw("CASE WHEN account_code = ? THEN 0 WHEN account_name = ? THEN 1 ELSE 2 END", [$accountCode, $accountName])
            ->first();
    }

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'is_active',
        'is_system_account',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'coa_store_assignments', 'coa_id', 'store_id')
                    ->withPivot('is_global')
                    ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Whether the given user may edit/delete this account. Admins and the
     * franchisor may manage any account; an owner may manage only the
     * (non-system) accounts they created themselves.
     */
    /**
     * The next available code to assign to a new child of $parentCode, preferring
     * the natural block step (100 under x000, 10 under xy00, 1 under xyz0) and
     * falling back to any free code in the parent's range. Null if the code can't
     * have children or the range is full.
     */
    public static function nextChildCode(string $parentCode): ?string
    {
        $range = self::childCodeRangeForParent($parentCode);
        if (! $range) {
            return null;
        }

        [$min, $max] = $range;
        $code = (int) $parentCode;
        $step = $code % 1000 === 0 ? 100 : ($code % 100 === 0 ? 10 : 1);

        $used = self::whereBetween('account_code', [(string) $min, (string) $max])
            ->pluck('account_code')
            ->map(fn ($c) => (int) $c)
            ->all();

        for ($c = $code + $step; $c <= $max; $c += $step) {
            if (! in_array($c, $used, true)) {
                return (string) $c;
            }
        }
        for ($c = $min; $c <= $max; $c++) {
            if (! in_array($c, $used, true)) {
                return (string) $c;
            }
        }

        return null;
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->isAdmin() || $user->isFranchisor()) {
            return true;
        }

        // Owners may edit/delete only the (non-seeded) accounts they created
        // themselves — never the standard seeded chart or other tenants' accounts.
        return $user->isOwner()
            && ! $this->is_system_account
            && (int) $this->created_by === (int) $user->id;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }
}
