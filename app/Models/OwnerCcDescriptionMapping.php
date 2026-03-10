<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerCcDescriptionMapping extends Model
{
    protected $fillable = [
        'description_pattern',
        'transaction_type_id',
        'coa_id',
        'created_by',
        'times_matched',
    ];

    protected $casts = [
        'times_matched' => 'integer',
    ];

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Normalize a statement description for consistent matching.
     */
    public static function normalizeDescription(?string $description): string
    {
        if ($description === null || $description === '') {
            return '';
        }
        $s = trim(preg_replace('/\s+/', ' ', $description));
        $s = mb_strtolower($s);
        return mb_substr($s, 0, 200);
    }
}
