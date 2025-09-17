<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = ['name', 'p_id'];

    public function parent()
    {
        return $this->belongsTo(TransactionType::class, 'p_id');
    }

    public function children()
    {
        return $this->hasMany(TransactionType::class, 'p_id');
    }

    // Store relationship can be added later when pivot table is created
    // public function stores()
    // {
    //     return $this->belongsToMany(\App\Models\Store::class, 'store_transaction_type', 'transaction_type_id', 'store_id');
    // }
}
