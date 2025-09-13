<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to manager_store pivot table
        Schema::table('manager_store', function (Blueprint $table) {
            // Unique constraint to prevent duplicate manager-store assignments
            $table->unique(['manager_id', 'store_id'], 'unq_manager_store');
            
            // Indexes for efficient lookups
            $table->index('manager_id', 'idx_manager_store_manager_id');
            $table->index('store_id', 'idx_manager_store_store_id');
            $table->index('created_at', 'idx_manager_store_created_at');
        });

        // Add indexes to daily_report_transactions
        Schema::table('daily_report_transactions', function (Blueprint $table) {
            // Composite index for common queries
            $table->index(['daily_report_id', 'transaction_type_id'], 'idx_daily_report_transactions_report_type');
            
            // Index for amount-based queries (financial analysis)
            $table->index('amount', 'idx_daily_report_transactions_amount');
            
            // Index for company lookups
            $table->index('company', 'idx_daily_report_transactions_company');
            
            // Index for created_at timestamp queries
            $table->index('created_at', 'idx_daily_report_transactions_created_at');
        });

        // Add indexes to daily_report_revenues if it exists
        if (Schema::hasTable('daily_report_revenues')) {
            Schema::table('daily_report_revenues', function (Blueprint $table) {
                // Composite index for common queries
                $table->index(['daily_report_id', 'revenue_income_type_id'], 'idx_daily_report_revenues_report_type');
                
                // Index for amount-based queries
                $table->index('amount', 'idx_daily_report_revenues_amount');
                
                // Index for created_at timestamp queries
                $table->index('created_at', 'idx_daily_report_revenues_created_at');
            });
        }

        // Add indexes to audit_logs if it exists
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                // Index for user-based audit queries
                $table->index('user_id', 'idx_audit_logs_user_id');
                
                // Index for model-based queries
                $table->index(['auditable_type', 'auditable_id'], 'idx_audit_logs_auditable');
                
                // Index for action-based queries
                $table->index('action', 'idx_audit_logs_action');
                
                // Composite index for time-based analysis
                $table->index(['created_at', 'action'], 'idx_audit_logs_created_action');
            });
        }

        // Add indexes to role_permissions if it exists
        if (Schema::hasTable('role_permissions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                // Unique constraint to prevent duplicate role-permission assignments
                if (!Schema::hasIndex('role_permissions', 'unq_role_permission')) {
                    $table->unique(['role', 'permission_id'], 'unq_role_permission');
                }
                
                // Indexes for efficient permission checking
                $table->index('role', 'idx_role_permissions_role');
                $table->index('permission_id', 'idx_role_permissions_permission_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manager_store', function (Blueprint $table) {
            $table->dropUnique('unq_manager_store');
            $table->dropIndex('idx_manager_store_manager_id');
            $table->dropIndex('idx_manager_store_store_id');
            $table->dropIndex('idx_manager_store_created_at');
        });

        Schema::table('daily_report_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_daily_report_transactions_report_type');
            $table->dropIndex('idx_daily_report_transactions_amount');
            $table->dropIndex('idx_daily_report_transactions_company');
            $table->dropIndex('idx_daily_report_transactions_created_at');
        });

        if (Schema::hasTable('daily_report_revenues')) {
            Schema::table('daily_report_revenues', function (Blueprint $table) {
                $table->dropIndex('idx_daily_report_revenues_report_type');
                $table->dropIndex('idx_daily_report_revenues_amount');
                $table->dropIndex('idx_daily_report_revenues_created_at');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('idx_audit_logs_user_id');
                $table->dropIndex('idx_audit_logs_auditable');
                $table->dropIndex('idx_audit_logs_action');
                $table->dropIndex('idx_audit_logs_created_action');
            });
        }

        if (Schema::hasTable('role_permissions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropUnique('unq_role_permission');
                $table->dropIndex('idx_role_permissions_role');
                $table->dropIndex('idx_role_permissions_permission_id');
            });
        }
    }
};