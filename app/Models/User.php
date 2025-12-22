<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Default relationships to eager load to prevent N+1 queries
     */
    protected $with = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'state',
        'email_verified_at',
        'username',
        'last_online',
        // Personal Information
        'home_address',
        'personal_phone',
        'personal_email',
        // Corporate Information
        'corporate_address',
        'corporate_phone',
        'corporate_email',
        'fanns_philly_email',
        // Business Details
        'corporate_ein',
        'corporate_creation_date',
        'store_id',
    ];

    /**
     * The attributes that should be guarded from mass assignment.
     *
     * @var list<string>
     */
    protected $guarded = [
        'role',
        'assigned_stores',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'corporate_creation_date' => 'date',
            'last_online' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * Get the user's picture URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/'.$this->avatar);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=206bc4&color=fff&size=128';
    }

    /**
     * The store assigned to the manager (one-to-many: manager belongs to one store).
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Alias for store() - more descriptive for managers
     */
    public function managedStore()
    {
        return $this->store();
    }

    /**
     * The stores owned by the user (many-to-many relationship).
     */
    public function ownedStores()
    {
        return $this->belongsToMany(Store::class, 'owner_store', 'owner_id', 'store_id');
    }

    /**
     * The stores assigned to the manager (many-to-many relationship via manager_store pivot).
     */
    public function assignedStoresPivot()
    {
        return $this->belongsToMany(Store::class, 'manager_store', 'manager_id', 'store_id');
    }

    /**
     * Get the stores assigned to the manager from the assigned_stores field.
     */
    public function getAssignedStoresAttribute()
    {
        $storeIds = json_decode($this->attributes['assigned_stores'] ?? '[]', true);

        return $storeIds ? Store::whereIn('id', $storeIds)->get() : collect();
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Check if user is an owner
     */
    public function isOwner(): bool
    {
        return $this->role === UserRole::OWNER;
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->role === UserRole::MANAGER;
    }

    /**
     * Check if user is the Franchisor owner
     */
    public function isFranchisor(): bool
    {
        return $this->isOwner() && strtolower($this->name) === 'franchisor';
    }

    /**
     * Get or create the Franchisor owner
     */
    public static function getOrCreateFranchisor(): self
    {
        // First, try to find by name (case-insensitive) with owner role
        $franchisor = self::where('role', UserRole::OWNER)
            ->whereRaw('LOWER(name) = ?', ['franchisor'])
            ->first();

        // If not found by name, try to find by email (regardless of role)
        if (! $franchisor) {
            $franchisor = self::where('email', 'franchisor@system.local')
                ->first();
        }

        // If found by email but wrong role/name, update it
        if ($franchisor && (strtolower($franchisor->name) !== 'franchisor' || $franchisor->role !== UserRole::OWNER)) {
            $franchisor->update([
                'name' => 'Franchisor',
                'role' => UserRole::OWNER,
            ]);
        }

        // If still not found, create it
        if (! $franchisor) {
            try {
                $franchisor = self::create([
                    'name' => 'Franchisor',
                    'email' => 'franchisor@system.local',
                    'password' => bcrypt('changeme123'),
                    'role' => UserRole::OWNER,
                    'email_verified_at' => now(),
                ]);

                Log::info('Franchisor owner created', [
                    'franchisor_id' => $franchisor->id,
                    'email' => $franchisor->email,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // If duplicate entry error, try to find it again (race condition)
                if ($e->getCode() == 23000) {
                    $franchisor = self::where('email', 'franchisor@system.local')
                        ->orWhere(function($query) {
                            $query->where('role', UserRole::OWNER)
                                  ->whereRaw('LOWER(name) = ?', ['franchisor']);
                        })
                        ->first();
                    
                    if ($franchisor) {
                        // Ensure correct name and role
                        if (strtolower($franchisor->name) !== 'franchisor' || $franchisor->role !== UserRole::OWNER) {
                            $franchisor->update([
                                'name' => 'Franchisor',
                                'role' => UserRole::OWNER,
                            ]);
                        }
                    } else {
                        throw $e; // Re-throw if still not found
                    }
                } else {
                    throw $e; // Re-throw other exceptions
                }
            }
        }

        return $franchisor;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * Securely change user role with proper authorization and logging
     */
    public function changeRole(UserRole $newRole, User $changedBy): bool
    {
        // Check if the user making the change has permission
        if (! $changedBy->role?->canManageRole($newRole)) {
            Log::warning('Unauthorized role change attempt', [
                'target_user' => $this->id,
                'target_email' => $this->email,
                'old_role' => $this->role?->value,
                'new_role' => $newRole->value,
                'changed_by' => $changedBy->id,
                'changed_by_email' => $changedBy->email,
            ]);

            return false;
        }

        $oldRole = $this->role;
        $this->role = $newRole;

        if ($this->save()) {
            Log::info('User role changed successfully', [
                'user_id' => $this->id,
                'user_email' => $this->email,
                'old_role' => $oldRole?->value,
                'new_role' => $newRole->value,
                'changed_by' => $changedBy->id,
                'changed_by_email' => $changedBy->email,
                'timestamp' => now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Check if user has access to a specific store
     */
    public function hasStoreAccess(int $storeId): bool
    {
        // If user is being impersonated by an admin, grant admin-level access
        if ($this->isBeingImpersonated()) {
            return true;
        }

        // Franchisor has full business access to all stores (Corporate and Franchisee)
        if ($this->isFranchisor()) {
            return Store::where('id', $storeId)->whereNull('deleted_at')->exists();
        }

        // Franchisee (Owner): can see their stores (created by them or assigned via pivot)
        if ($this->isOwner()) {
            // Check if store was created by this owner
            $createdByOwner = Store::where('id', $storeId)
                ->where('created_by', $this->id)
                ->whereNull('deleted_at')
                ->exists();
            
            // Check if store is assigned via pivot table
            $assignedViaPivot = $this->ownedStores()
                ->where('stores.id', $storeId)
                ->whereNull('stores.deleted_at')
                ->exists();
            
            return $createdByOwner || $assignedViaPivot;
        }

        if ($this->isManager()) {
            // Check direct store_id assignment
            if ($this->store_id == $storeId) {
                return true;
            }
            
            // Check pivot table assignment
            return $this->assignedStoresPivot()
                ->where('stores.id', $storeId)
                ->whereNull('stores.deleted_at')
                ->exists();
        }

        return false;
    }

    /**
     * Get all accessible stores for the user
     */
    public function accessibleStores()
    {
        // If user is being impersonated by an admin, grant admin-level access
        if ($this->isBeingImpersonated()) {
            return Store::whereNull('deleted_at');
        }

        // Franchisor has full business access to all stores (Corporate and Franchisee)
        if ($this->isFranchisor()) {
            return Store::whereNull('deleted_at');
        }

        // Admin has technical access but NOT business store access
        // (Admin should not access business operations)
        // if ($this->isAdmin()) {
        //     return Store::whereNull('deleted_at');
        // }

        // Franchisee (Owner): can see their stores (created by them or assigned via pivot)
        // Owners can see stores they created OR stores assigned to them via owner_store pivot table
        if ($this->isOwner()) {
            $storeIds = $this->ownedStores()->pluck('stores.id')->toArray();
            $createdStoreIds = Store::where('created_by', $this->id)->pluck('id')->toArray();
            $allStoreIds = array_unique(array_merge($storeIds, $createdStoreIds));
            
            if (empty($allStoreIds)) {
                return Store::whereRaw('1 = 0');
            }
            
            return Store::whereIn('id', $allStoreIds)->whereNull('deleted_at');
        }

        if ($this->isManager()) {
            // Managers can only see stores they are assigned to
            // Corporate store managers: only see their assigned corporate store locations
            // Franchisee store managers: only see their assigned franchisee locations
            $storeIds = collect([$this->store_id])->filter();
            $pivotStoreIds = $this->assignedStoresPivot()->pluck('stores.id');
            $allStoreIds = $storeIds->merge($pivotStoreIds)->unique()->filter();
            
            if ($allStoreIds->isEmpty()) {
                return Store::whereRaw('1 = 0'); // Return empty query - no stores assigned
            }
            
            // Return only the stores assigned to this manager
            return Store::whereIn('id', $allStoreIds)->whereNull('deleted_at');
        }

        return Store::whereRaw('1 = 0'); // Return empty query
    }

    /**
     * Get all accessible store IDs for the user
     */
    public function getAccessibleStoreIds(): array
    {
        // If user is being impersonated by an admin, grant admin-level access
        if ($this->isBeingImpersonated()) {
            return Store::whereNull('deleted_at')->pluck('id')->toArray();
        }

        // Admin has technical access but NOT business store access
        // (Admin should not access business operations)
        // if ($this->isAdmin()) {
        //     return Store::whereNull('deleted_at')->pluck('id')->toArray();
        // }

        // Franchisor has full business access to all stores
        if ($this->isFranchisor()) {
            return Store::whereNull('deleted_at')->pluck('id')->toArray();
        }

        // Franchisee (Owner): can see their stores (created by them or assigned via pivot)
        if ($this->isOwner()) {
            $storeIds = $this->ownedStores()->pluck('stores.id')->toArray();
            $createdStoreIds = Store::where('created_by', $this->id)->pluck('id')->toArray();
            return array_unique(array_merge($storeIds, $createdStoreIds));
        }

        if ($this->isManager()) {
            $storeIds = collect([$this->store_id])->filter();
            $pivotStoreIds = $this->assignedStoresPivot()->pluck('stores.id');
            return $storeIds->merge($pivotStoreIds)->unique()->filter()->toArray();
        }

        return [];
    }

    /**
     * Check if the current user is being impersonated by an admin
     */
    public function isBeingImpersonated(): bool
    {
        return \Illuminate\Support\Facades\Session::has('impersonating_admin_id') 
            && \Illuminate\Support\Facades\Session::has('impersonating_user_id')
            && \Illuminate\Support\Facades\Session::get('impersonating_user_id') == $this->id;
    }

    /**
     * Update the user's last online timestamp
     */
    public function updateLastOnline(): void
    {
        $this->update(['last_online' => now()]);
    }

    /**
     * Get human readable last online time
     */
    public function getLastOnlineHumanAttribute(): string
    {
        if (! $this->last_online) {
            return 'Never';
        }

        $now = now();

        if ($this->last_online->diffInMinutes($now) < 5) {
            return 'Online';
        }

        return $this->last_online->diffForHumans();
    }

    /**
     * Get all accessible owners for the user
     * Franchisor: sees all owners
     * Others: see only themselves or their related owners
     */
    public function accessibleOwners()
    {
        // Franchisor can see every owner
        if ($this->isFranchisor()) {
            return User::where('role', UserRole::OWNER)->whereNull('deleted_at');
        }

        // Franchisee (Owner): can see themselves
        if ($this->isOwner()) {
            return User::where('id', $this->id)->whereNull('deleted_at');
        }

        // Managers: cannot see owners directly
        return User::whereRaw('1 = 0');
    }

    /**
     * Get all accessible managers for the user
     * Franchisor: sees all managers
     * Franchisee (Owner): sees managers of their stores
     * Managers: see only themselves
     */
    public function accessibleManagers()
    {
        // Franchisor can see every manager
        if ($this->isFranchisor()) {
            return User::where('role', UserRole::MANAGER)->whereNull('deleted_at');
        }

        // Franchisee (Owner): can see managers of their stores
        // Owners can see all managers assigned to stores they own (via direct store_id or pivot table)
        if ($this->isOwner()) {
            $storeIds = $this->getAccessibleStoreIds();
            
            if (empty($storeIds)) {
                return User::whereRaw('1 = 0');
            }

            // Get managers assigned to their stores (via direct store_id or pivot table)
            $managerIds = User::where('role', UserRole::MANAGER)
                ->where(function($query) use ($storeIds) {
                    $query->whereIn('store_id', $storeIds)
                        ->orWhereHas('assignedStoresPivot', function($q) use ($storeIds) {
                            $q->whereIn('stores.id', $storeIds);
                        });
                })
                ->pluck('id')
                ->toArray();

            return User::whereIn('id', $managerIds)->whereNull('deleted_at');
        }

        // Manager: can only see themselves
        if ($this->isManager()) {
            return User::where('id', $this->id)->whereNull('deleted_at');
        }

        return User::whereRaw('1 = 0');
    }
}
