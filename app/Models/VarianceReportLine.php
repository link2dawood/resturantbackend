<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarianceReportLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'variance_report_id', 'inventory_item_id', 'starting_stock', 'ordered_qty',
        'total_available', 'theoretical_usage', 'theoretical_ending', 'actual_ending',
        'variance', 'variance_pct', 'severity', 'base_unit', 'is_incomplete',
    ];

    protected $casts = [
        'starting_stock' => 'decimal:4',
        'ordered_qty' => 'decimal:4',
        'total_available' => 'decimal:4',
        'theoretical_usage' => 'decimal:4',
        'theoretical_ending' => 'decimal:4',
        'actual_ending' => 'decimal:4',
        'variance' => 'decimal:4',
        'variance_pct' => 'decimal:4',
        'is_incomplete' => 'boolean',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(VarianceReport::class, 'variance_report_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
