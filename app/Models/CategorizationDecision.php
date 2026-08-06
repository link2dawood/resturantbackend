<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit record of a single categorization decision — every auto-suggestion and
 * every reviewer assignment — so the learning loop can be debugged and improved.
 */
class CategorizationDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'store_id',
        'expense_transaction_id',
        'bank_transaction_id',
        'transaction_mapping_rule_id',
        'description',
        'source',    // rule | fuzzy | manual | learned | none
        'decision',  // suggested | accepted | overridden | manual | learned
        'suggested_coa_id',
        'chosen_coa_id',
        'confidence',
        'decided_by',
        'notes',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TransactionMappingRule::class, 'transaction_mapping_rule_id');
    }

    public function suggestedCoa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'suggested_coa_id');
    }

    public function chosenCoa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chosen_coa_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
