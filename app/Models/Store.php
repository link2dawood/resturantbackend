<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

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
     * Default relationships to eager load to prevent N+1 queries
     */
    protected $with = ['owner'];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The owner who created the store.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The managers assigned to the store.
     */
    public function managers()
    {
        return $this->belongsToMany(User::class, 'manager_store', 'store_id', 'manager_id');
    }

    /**
     * The daily reports for this store.
     */
    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }
 
}
