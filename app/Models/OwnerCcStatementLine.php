<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerCcStatementLine extends Model
{
    protected $fillable = [
        'owner_cc_statement_import_id',
        'status',
        'transaction_date',
        'description',
        'debit',
        'credit',
        'member_name',
        'transaction_type_id',
        'coa_id',
        'card_last4',
        'store_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(OwnerCcStatementImport::class, 'owner_cc_statement_import_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
