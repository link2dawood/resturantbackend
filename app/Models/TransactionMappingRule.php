<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionMappingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'description_pattern',
        'vendor_id',
        'coa_id',
        'confidence_score',
        'times_used',
        'times_correct',
        'times_incorrect',
        'last_used',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'last_used' => 'datetime',
        'times_used' => 'integer',
        'times_correct' => 'integer',
        'times_incorrect' => 'integer',
    ];

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    // Scopes
    public function scopeHighConfidence($query, $threshold = 0.80)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeMediumConfidence($query, $min = 0.50, $max = 0.80)
    {
        return $query->whereBetween('confidence_score', [$min, $max]);
    }

    public function scopeRecent($query)
    {
        return $query->whereNotNull('last_used')->orderBy('last_used', 'desc');
    }

    public function scopeByPattern($query, $pattern)
    {
        return $query->where('description_pattern', $pattern);
    }

    // Update confidence score based on usage
    public function updateConfidenceScore()
    {
        $total = $this->times_correct + $this->times_incorrect;
        
        if ($total === 0) {
            $this->confidence_score = 0;
        } else {
            $this->confidence_score = round($this->times_correct / $total, 2);
        }
        
        $this->save();
    }

    // Mark as used
    public function markAsUsed($correct = null)
    {
        $this->times_used++;
        $this->last_used = now();
        
        if ($correct === true) {
            $this->times_correct++;
        } elseif ($correct === false) {
            $this->times_incorrect++;
        }
        
        $this->updateConfidenceScore();
        $this->save();
    }
}
