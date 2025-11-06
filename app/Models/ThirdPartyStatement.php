<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThirdPartyStatement extends Model
{
    use HasFactory;
    protected $fillable = [
        'platform',
        'store_id',
        'statement_date',
        'statement_id',
        'gross_sales',
        'marketing_fees',
        'delivery_fees',
        'processing_fees',
        'net_deposit',
        'sales_tax_collected',
        'import_batch_id',
        'file_name',
        'file_hash',
        'imported_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'gross_sales' => 'decimal:2',
        'marketing_fees' => 'decimal:2',
        'delivery_fees' => 'decimal:2',
        'processing_fees' => 'decimal:2',
        'net_deposit' => 'decimal:2',
        'sales_tax_collected' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ExpenseTransaction::class, 'third_party_statement_id');
    }
}
