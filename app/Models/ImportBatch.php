<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type',
        'file_name',
        'file_hash',
        'store_id',
        'transaction_count',
        'imported_count',
        'duplicate_count',
        'error_count',
        'needs_review_count',
        'date_range_start',
        'date_range_end',
        'status',
        'error_log',
        'imported_at',
        'imported_by',
    ];

    protected $casts = [
        'date_range_start' => 'date',
        'date_range_end' => 'date',
        'imported_at' => 'datetime',
        'transaction_count' => 'integer',
        'imported_count' => 'integer',
        'duplicate_count' => 'integer',
        'error_count' => 'integer',
        'needs_review_count' => 'integer',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function expenseTransactions()
    {
        return $this->hasMany(ExpenseTransaction::class, 'import_batch_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('import_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Calculate success rate
    public function getSuccessRateAttribute()
    {
        if ($this->transaction_count === 0) {
            return 0;
        }
        return round(($this->imported_count / $this->transaction_count) * 100, 2);
    }
}
