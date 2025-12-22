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
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::OWNER => 'Owner',
            self::MANAGER => 'Manager',
        };
    }

    public function hasPermission(string $permission): bool
    {
        // Check database permissions first
        $hasDbPermission = \App\Models\RolePermission::where('role', $this->value)
            ->whereHas('permission', function ($query) use ($permission) {
                $query->where('name', $permission)->where('is_active', true);
            })
            ->exists();

        if ($hasDbPermission) {
            return true;
        }

        // Fallback to hardcoded permissions for backward compatibility
        return match ($this) {
            // ADMIN: Technical/System Administration ONLY - No business operations
            self::ADMIN => in_array($permission, [
                'view_audit_logs',           // System audit logs
                'manage_transaction_types',   // System configuration
                'manage_system_settings',      // System settings
                'view_system_logs',           // System logs
            ]),
            // OWNER (including Franchisor): Full business control
            self::OWNER => in_array($permission, [
                'view_stores',
                'manage_stores',
                'view_daily_reports',
                'create_reports',
                'manage_reports',
                'view_reports',
                'manage_managers',
                'manage_owners',
                'view_audit_logs',
                'manage_transaction_types',
                'manage_revenue_income_types',
            ]),
            // MANAGER: Limited business operations for assigned stores
            self::MANAGER => in_array($permission, [
                'view_assigned_stores',
                'view_daily_reports',
                'create_reports',
            ]),
        };
    }

    public function canManageRole(UserRole $targetRole): bool
    {
        return match ($this) {
            self::ADMIN => true,
            self::OWNER => $targetRole === self::MANAGER,
            self::MANAGER => false,
        };
    }
}
