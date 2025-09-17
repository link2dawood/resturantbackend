<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case OWNER = 'owner';
    case MANAGER = 'manager';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::OWNER => 'Owner',
            self::MANAGER => 'Manager',
        };
    }
    
    public function hasPermission(string $permission): bool
    {
        // Check database permissions first
        $hasDbPermission = \App\Models\RolePermission::where('role', $this->value)
            ->whereHas('permission', function($query) use ($permission) {
                $query->where('name', $permission)->where('is_active', true);
            })
            ->exists();
            
        if ($hasDbPermission) {
            return true;
        }
        
        // Fallback to hardcoded permissions for backward compatibility
        return match($this) {
            self::ADMIN =>in_array($permission, [
                'view_stores',
                'view_assigned_stores',
                'manage_reports',
                'manage_managers',
                'view_audit_logs',
                'manage_owners',
                'manage_stores',
                'manage_transaction_types',
            ]),
            self::OWNER => in_array($permission, [
                'view_stores',
                'view_reports',
                'create_reports',
                'manage_reports',
                'manage_managers',
                'view_audit_logs',
            ]),
            self::MANAGER => in_array($permission, [
                'view_assigned_stores',
                'create_reports',
                'view_reports',
            ]),
        };
    }
    
    public function canManageRole(UserRole $targetRole): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::OWNER => $targetRole === self::MANAGER,
            self::MANAGER => false,
        };
    }
}