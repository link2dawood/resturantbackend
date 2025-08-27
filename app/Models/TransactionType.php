<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = ['name','p_id'];

        public function parent()
    {
        return $this->belongsTo(TransactionType::class, 'p_id');
    }

    public function children()
    {
        return $this->hasMany(TransactionType::class, 'p_id');
    }
}
