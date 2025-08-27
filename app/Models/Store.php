<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_info',
        'contact_name',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'sales_tax_rate',
        'medicare_tax_rate',
        'created_by',
    ];

    /**
     * The managers assigned to the store.
     */
    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_store', 'store_id', 'manager_id');
    }
 
}
