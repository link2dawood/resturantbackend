<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $user = auth()->user();
        
        // Franchisor: can see every owner
        // Others: filtered by access rules
        $owners = $user->accessibleOwners()->get();

        return view('owners.index', ['owners' => $owners]);
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $validatedData = $request->validate([
                // Basic Information
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'avatar' => 'nullable|image|max:2048',
                'state' => 'required|string|size:2|in:'.implode(',', array_keys(\App\Helpers\USStates::getStates())),

                // Personal Information
                'home_address' => 'nullable|string|max:1000',
                'personal_phone' => 'nullable|string|regex:/^\(\d{3}\)\s\d{3}-\d{4}$/',
                'personal_email' => 'nullable|email|max:255',

                // Corporate Information
                'corporate_address' => 'nullable|string|max:1000',
                'corporate_phone' => 'nullable|string|regex:/^\(\d{3}\)\s\d{3}-\d{4}$/',
                'corporate_email' => 'nullable|email|max:255',
                'fanns_philly_email' => 'nullable|email|max:255',

                // Business Details
                'corporate_ein' => 'nullable|string|max:20',
                'corporate_creation_date' => 'nullable|date',
            ]);

            // Store the plain password for email before hashing
            $temporaryPassword = $validatedData['password'];

            $validatedData['email_verified_at'] = now();
            $validatedData['password'] = bcrypt($validatedData['password']);

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validatedData['avatar'] = basename($avatarPath);
            }

            $owner = User::create($validatedData);
            $owner->changeRole(UserRole::OWNER, auth()->user());

            // Dispatch event to send welcome email
            \App\Events\OwnerCreated::dispatch($owner, $temporaryPassword, auth()->user());

            return redirect()->route('owners.index')->with('success', 'Owner created successfully with complete profile information. Welcome email has been sent.');
        }

        return view('owners.create');
    }

    public function show(User $owner)
    {
        $assignedStores = $owner->ownedStores;
        $availableStores = \App\Models\Store::all();

        return view('owners.show', compact('owner', 'assignedStores', 'availableStores'));
    }

    public function edit(User $owner)
    {
        return view('owners.edit', compact('owner'));
    }

    public function update(Request $request, User $owner)
    {
        $validatedData = $request->validate([
            // Basic Information
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$owner->id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|image|max:2048',
            'state' => 'required|string|size:2|in:'.implode(',', array_keys(\App\Helpers\USStates::getStates())),

            // Personal Information
            'home_address' => 'nullable|string|max:1000',
            'personal_phone' => 'nullable|string|max:50',
            'personal_email' => 'nullable|email|max:255',

            // Corporate Information
            'corporate_address' => 'nullable|string|max:1000',
            'corporate_phone' => 'nullable|string|max:50',
            'corporate_email' => 'nullable|email|max:255',
            'fanns_philly_email' => 'nullable|email|max:255',

            // Business Details
            'corporate_ein' => 'nullable|string|max:20',
            'corporate_creation_date' => 'nullable|date',
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validatedData['avatar'] = basename($avatarPath);
        }

        if (! empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $owner->update($validatedData);

        return redirect()->route('owners.index')->with('success', 'Owner updated successfully with complete profile information.');
    }

    public function destroy(User $owner)
    {
        $owner->delete();

        return redirect()->route('owners.index')->with('success', 'Owner deleted successfully.');
    }

    public function assignStoresForm(User $owner)
    {
        $user = auth()->user();
        
        // Admin and Franchisor can assign stores to owners
        // Admin: can assign any store to any owner
        // Franchisor: can assign any store (Corporate or Franchisee) to any owner
        if ($user->isAdmin() || $user->isFranchisor()) {
            $stores = \App\Models\Store::whereNull('deleted_at')->get();
        } else {
            $stores = collect();
        }
        
        // Get stores assigned via pivot table
        $assignedStores = $owner->ownedStores->pluck('id')->toArray();

        return view('owners.assign-stores', compact('owner', 'stores', 'assignedStores'));
    }

    public function assignStores(Request $request, User $owner)
    {
        $validatedData = $request->validate([
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
        ]);
        
        // Ensure store_ids is always an array (even if empty)
        $validatedData['store_ids'] = $validatedData['store_ids'] ?? [];

        try {
            // Get the Franchisor owner for unassigned stores
            $franchisor = \App\Models\User::getOrCreateFranchisor();

            // Get currently assigned stores
            $currentlyAssignedStoreIds = $owner->ownedStores->pluck('id')->toArray();
            $newStoreIds = $validatedData['store_ids'];

            // Find stores to unassign (currently assigned but not in new selection)
            $storesToUnassign = array_diff($currentlyAssignedStoreIds, $newStoreIds);

            // Unassign stores by removing from pivot table
            // If a store has no owners after unassignment, assign it to Franchisor
            if (! empty($storesToUnassign)) {
                foreach ($storesToUnassign as $storeId) {
                    $store = \App\Models\Store::find($storeId);
                    if ($store) {
                        // Remove this owner from the store
                        $store->owners()->detach($owner->id);
                        
                        // If store has no owners, assign to Franchisor
                        if ($store->owners()->count() === 0) {
                            $store->owners()->attach($franchisor->id);
                        }
                    }
                }
            }

            // Sync the stores using the pivot table - this will replace all current assignments
            $owner->ownedStores()->sync($validatedData['store_ids']);

            return redirect()->route('owners.show', $owner)->with('success', 'Stores assigned successfully.');

        } catch (\Exception $e) {
            \Log::error('Error assigning stores to owner', [
                'owner_id' => $owner->id,
                'error' => $e->getMessage(),
                'store_ids' => $validatedData['store_ids'],
            ]);

            return redirect()->back()->withErrors(['error' => 'Failed to assign stores. Please try again.']);
        }
    }
}
