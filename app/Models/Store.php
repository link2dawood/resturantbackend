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
     * The manager assigned to the store (one-to-many: store has one manager).
     */
    public function manager()
    {
        return $this->hasOne(User::class, 'store_id')->where('role', 'manager');
    }

    /**
     * The daily reports for this store.
     */
    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }

    /**
     * The owners assigned to the store (many-to-many relationship).
     */
    public function owners()
    {
        return $this->belongsToMany(User::class, 'owner_store', 'store_id', 'owner_id')
                    ->where('role', 'owner');
    }

}
