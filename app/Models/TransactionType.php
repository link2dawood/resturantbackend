<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = ['description_name', 'category_transaction_type_id'];

    public function categoryTransactionType()
    {
        return $this->belongsTo(TransactionType::class, 'category_transaction_type_id');
    }

    public function children()
    {
        return $this->hasMany(TransactionType::class, 'category_transaction_type_id');
    }

    public function parent()
    {
        return $this->belongsTo(TransactionType::class, 'category_transaction_type_id');
    }
}
