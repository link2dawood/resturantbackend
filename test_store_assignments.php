<?php
/**
 * Quick Testing Script for Store Assignment Features
 * 
 * Run this via: php artisan tinker
 * Then copy-paste sections as needed
 */

// ============================================
// Test 1: Verify Franchisor Owner Creation
// ============================================
$franchisor = \App\Models\User::getOrCreateFranchisor();
echo "Franchisor ID: " . $franchisor->id . "\n";
echo "Franchisor Name: " . $franchisor->name . "\n";
echo "Franchisor Email: " . $franchisor->email . "\n";
echo "Franchisor Role: " . $franchisor->role->value . "\n";

// ============================================
// Test 2: Verify Admins Cannot Be Assigned
// ============================================
$admin = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
$owner = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->first();

if ($admin && $owner) {
    echo "\nAdmin ID: " . $admin->id . "\n";
    echo "Owner ID: " . $owner->id . "\n";
    
    // Try to validate admin assignment (should fail)
    try {
        $validator = \Validator::make(
            ['created_by' => $admin->id],
            [
                'created_by' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = \App\Models\User::find($value);
                        if (! $user || ! $user->isOwner()) {
                            $fail('Only owners can be assigned to stores. Admins cannot be assigned.');
                        }
                    },
                ],
            ]
        );
        
        if ($validator->fails()) {
            echo "✓ Validation correctly prevents admin assignment\n";
            echo "  Error: " . $validator->errors()->first('created_by') . "\n";
        } else {
            echo "✗ Validation failed - admin was allowed!\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// ============================================
// Test 3: Verify Owner Can Be Assigned
// ============================================
if ($owner) {
    try {
        $validator = \Validator::make(
            ['created_by' => $owner->id],
            [
                'created_by' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        $user = \App\Models\User::find($value);
                        if (! $user || ! $user->isOwner()) {
                            $fail('Only owners can be assigned to stores. Admins cannot be assigned.');
                        }
                    },
                ],
            ]
        );
        
        if ($validator->passes()) {
            echo "\n✓ Validation correctly allows owner assignment\n";
        } else {
            echo "\n✗ Validation incorrectly blocked owner assignment\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// ============================================
// Test 4: Check Pivot Table Relationships
// ============================================
$stores = \App\Models\Store::with('owners')->get();
echo "\n=== Store-Owner Relationships ===\n";
foreach ($stores as $store) {
    echo "Store: {$store->store_info} (ID: {$store->id})\n";
    if ($store->owners->count() > 0) {
        foreach ($store->owners as $owner) {
            echo "  - Owner: {$owner->name} (ID: {$owner->id}, Role: {$owner->role->value})\n";
        }
    } else {
        echo "  - No owners assigned (should be assigned to Franchisor)\n";
    }
}

// ============================================
// Test 5: Verify No Admins in Pivot Table
// ============================================
$adminAssignments = \DB::table('owner_store')
    ->join('users', 'owner_store.owner_id', '=', 'users.id')
    ->where('users.role', 'admin')
    ->count();

if ($adminAssignments === 0) {
    echo "\n✓ No admin users found in owner_store pivot table\n";
} else {
    echo "\n✗ WARNING: Found {$adminAssignments} admin assignments in pivot table!\n";
}

// ============================================
// Test 6: Check Unassigned Stores Go to Franchisor
// ============================================
$storesWithoutOwners = \App\Models\Store::doesntHave('owners')->get();
if ($storesWithoutOwners->count() > 0) {
    echo "\n⚠ Found {$storesWithoutOwners->count()} stores without owners:\n";
    foreach ($storesWithoutOwners as $store) {
        echo "  - {$store->store_info} (ID: {$store->id})\n";
        // Auto-assign to Franchisor
        $store->owners()->attach($franchisor->id);
        echo "    → Assigned to Franchisor\n";
    }
} else {
    echo "\n✓ All stores have at least one owner assigned\n";
}

echo "\n=== Testing Complete ===\n";

