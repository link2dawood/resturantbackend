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
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isOwner()) {
            return Store::where('id', $storeId)
                ->where('created_by', $this->id)
                ->whereNull('deleted_at')
                ->exists();
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
        if ($this->isAdmin()) {
            return Store::whereNull('deleted_at');
        }

        if ($this->isOwner()) {
            return Store::where('created_by', $this->id)->whereNull('deleted_at');
        }

        if ($this->isManager()) {
            // Get stores from both direct assignment and pivot table
            $storeIds = collect([$this->store_id])->filter();
            $pivotStoreIds = $this->assignedStoresPivot()->pluck('stores.id');
            $allStoreIds = $storeIds->merge($pivotStoreIds)->unique()->filter();
            
            if ($allStoreIds->isEmpty()) {
                return Store::whereRaw('1 = 0'); // Return empty query
            }
            
            return Store::whereIn('id', $allStoreIds)->whereNull('deleted_at');
        }

        return Store::whereRaw('1 = 0'); // Return empty query
    }

    /**
     * Get all accessible store IDs for the user
     */
    public function getAccessibleStoreIds(): array
    {
        if ($this->isAdmin()) {
            return Store::whereNull('deleted_at')->pluck('id')->toArray();
        }

        if ($this->isOwner()) {
            return Store::where('created_by', $this->id)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();
        }

        if ($this->isManager()) {
            $storeIds = collect([$this->store_id])->filter();
            $pivotStoreIds = $this->assignedStoresPivot()->pluck('stores.id');
            return $storeIds->merge($pivotStoreIds)->unique()->filter()->toArray();
        }

        return [];
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
}
