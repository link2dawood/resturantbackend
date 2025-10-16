<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Store Management
            ['name' => 'manage_stores', 'description' => 'Create, update, and delete stores', 'category' => 'stores'],
            ['name' => 'view_stores', 'description' => 'View store information', 'category' => 'stores'],
            ['name' => 'view_assigned_stores', 'description' => 'View stores assigned to user', 'category' => 'stores'],

            // User Management
            ['name' => 'manage_users', 'description' => 'Create, update, and delete users', 'category' => 'users'],
            ['name' => 'view_users', 'description' => 'View user information', 'category' => 'users'],
            ['name' => 'manage_managers', 'description' => 'Create, update, and delete managers', 'category' => 'users'],
            ['name' => 'manage_owners', 'description' => 'Create, update, and delete owners', 'category' => 'users'],
            ['name' => 'change_user_roles', 'description' => 'Change user roles', 'category' => 'users'],

            // Daily Reports
            ['name' => 'create_reports', 'description' => 'Create daily reports', 'category' => 'reports'],
            ['name' => 'view_reports', 'description' => 'View daily reports', 'category' => 'reports'],
            ['name' => 'edit_reports', 'description' => 'Edit daily reports', 'category' => 'reports'],
            ['name' => 'delete_reports', 'description' => 'Delete daily reports', 'category' => 'reports'],
            ['name' => 'approve_reports', 'description' => 'Approve daily reports', 'category' => 'reports'],
            ['name' => 'export_reports', 'description' => 'Export reports to PDF/CSV', 'category' => 'reports'],

            // Audit & Security
            ['name' => 'view_audit_logs', 'description' => 'View audit logs', 'category' => 'security'],
            ['name' => 'manage_permissions', 'description' => 'Manage system permissions', 'category' => 'security'],

            // Transaction Types
            ['name' => 'manage_transaction_types', 'description' => 'Manage transaction types', 'category' => 'configuration'],
            ['name' => 'view_transaction_types', 'description' => 'View transaction types', 'category' => 'configuration'],

            // Revenue Income Types
            ['name' => 'manage_revenue_types', 'description' => 'Manage revenue income types', 'category' => 'configuration'],
            ['name' => 'view_revenue_types', 'description' => 'View revenue income types', 'category' => 'configuration'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Set up role-permission relationships
        $rolePermissions = [
            UserRole::ADMIN->value => [
                // Admin can do everything: owners, stores, transaction types, revenue types
                'manage_users', 'view_users', 'manage_owners', 'manage_managers', 'change_user_roles',
                'manage_stores', 'view_stores',
                'create_reports', 'view_reports', 'edit_reports', 'delete_reports', 'approve_reports', 'export_reports',
                'view_audit_logs', 'manage_permissions',
                'manage_transaction_types', 'view_transaction_types',
                'manage_revenue_types', 'view_revenue_types',
            ],
            UserRole::OWNER->value => [
                // Owner can: manage stores, manage managers, assign managers to stores, create daily reports
                'manage_stores', 'view_stores',
                'manage_managers', 'view_users',
                'create_reports', 'view_reports', 'edit_reports', 'approve_reports', 'export_reports',
            ],
            UserRole::MANAGER->value => [
                // Manager can only: view assigned stores and create daily reports
                'view_assigned_stores',
                'create_reports', 'view_reports', 'edit_reports',
            ],
        ];

        foreach ($rolePermissions as $role => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    RolePermission::firstOrCreate([
                        'role' => $role,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }
    }
}
