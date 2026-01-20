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
        'store_type',
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
     * All managers assigned to the store (one-to-many: store has multiple managers).
     */
    public function managers()
    {
        return $this->hasMany(User::class, 'store_id')->where('role', 'manager');
    }

    /**
     * Managers assigned via pivot table (many-to-many relationship).
     */
    public function assignedManagers()
    {
        return $this->belongsToMany(User::class, 'manager_store', 'store_id', 'manager_id')
            ->where('role', 'manager');
    }

    /**
     * The daily reports for this store.
     */
    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }

    /**
     * The owners assigned to the store.
     * 
     * NOTE: One store can have only one owner (enforced by unique constraint on store_id in owner_store table).
     * This relationship is defined as belongsToMany for flexibility, but the database constraint ensures
     * only one owner can be assigned to each store.
     * 
     * Use assignedOwner() to get the single owner assigned via the pivot table.
     */
    public function owners()
    {
        return $this->belongsToMany(User::class, 'owner_store', 'store_id', 'owner_id')
            ->where('role', 'owner');
    }

    /**
     * Get the single owner assigned to this store via the pivot table.
     * 
     * One store can have only one owner (enforced by database unique constraint on store_id).
     * This is different from owner() which returns the creator (created_by).
     * 
     * @return User|null
     */
    public function assignedOwner(): ?User
    {
        return $this->owners()->first();
    }

    /**
     * The chart of accounts assigned to this store.
     */
    public function chartOfAccounts()
    {
        return $this->belongsToMany(ChartOfAccount::class, 'coa_store_assignments', 'store_id', 'coa_id')
                    ->withPivot('is_global')
                    ->withTimestamps();
    }

    /**
     * The vendors assigned to this store.
     */
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'vendor_store_assignments', 'store_id', 'vendor_id')
                    ->withPivot('is_global')
                    ->withTimestamps();
    }

    /**
     * Check if this is a Corporate Store
     * Corporate Stores: Controlled by Franchisor, report to Franchisor, run by Managers
     */
    public function isCorporateStore(): bool
    {
        return $this->store_type === 'corporate';
    }

    /**
     * Check if this is a Franchisee location
     * Franchisee (Owner): Controls one or more locations, reports to Franchisor, can have Managers running a location
     */
    public function isFranchiseeLocation(): bool
    {
        return $this->store_type === 'franchisee';
    }

    /**
     * Get the Franchisor owner (for reporting purposes)
     * Corporate Stores: Controlled by Franchisor, report to Franchisor, run by Managers
     * Franchisee locations: Controlled by Owner (Franchisee), reports to Franchisor, can have Managers running a location
     */
    public function franchisor(): ?User
    {
        return User::getOrCreateFranchisor();
    }

    /**
     * Get the controlling owner
     * Corporate Stores: Franchisor (controls the store, reports to Franchisor, run by Managers)
     * Franchisee locations: Owner (Franchisee) controls the location, reports to Franchisor, can have Managers running it
     */
    public function controllingOwner(): ?User
    {
        if ($this->isCorporateStore()) {
            // Corporate Stores are controlled by Franchisor
            return $this->franchisor();
        }
        
        // For Franchisee locations, return the primary owner (Franchisee)
        // Franchisee (Owner) controls one or more locations, reports to Franchisor
        return $this->owners()->first() ?? $this->owner;
    }
}
