<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use App\Models\User;

class OwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_owners');
    }

    public function index()
    {
        $owners = User::where('role', UserRole::OWNER)->get();
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
                'state' => 'required|string|size:2|in:' . implode(',', array_keys(\App\Helpers\USStates::getStates())),
                
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
            'email' => 'required|email|unique:users,email,' . $owner->id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|image|max:2048',
            'state' => 'required|string|size:2|in:' . implode(',', array_keys(\App\Helpers\USStates::getStates())),
            
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

        if (!empty($validatedData['password'])) {
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
        $stores = \App\Models\Store::all();
        // For owners, get stores they created (owned stores)
        $assignedStores = \App\Models\Store::where('created_by', $owner->id)->pluck('id')->toArray();
        
        return view('owners.assign-stores', compact('owner', 'stores', 'assignedStores'));
    }

    public function assignStores(Request $request, User $owner)
    {
        $validatedData = $request->validate([
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        try {
            \DB::beginTransaction();
            
            // Get admin user (first user with admin role) to assign unselected stores to
            $adminUser = \App\Models\User::where('role', \App\Enums\UserRole::ADMIN)->first();
            
            if (!$adminUser) {
                throw new \Exception('No admin user found to reassign stores.');
            }
            
            // Get currently owned stores
            $currentlyOwnedStores = \App\Models\Store::where('created_by', $owner->id)->pluck('id')->toArray();
            $newStoreIds = $validatedData['store_ids'];
            
            // Find stores to unassign (currently owned but not in new selection)
            $storesToUnassign = array_diff($currentlyOwnedStores, $newStoreIds);
            
            // Find stores to assign (in new selection but not currently owned)  
            $storesToAssign = array_diff($newStoreIds, $currentlyOwnedStores);
            
            // Unassign stores by transferring them to admin
            if (!empty($storesToUnassign)) {
                \App\Models\Store::whereIn('id', $storesToUnassign)
                    ->update(['created_by' => $adminUser->id]);
            }
            
            // Assign new stores to this owner
            if (!empty($storesToAssign)) {
                \App\Models\Store::whereIn('id', $storesToAssign)
                    ->update(['created_by' => $owner->id]);
            }
            
            \DB::commit();
            
            return redirect()->back()->with('success', 'Stores assigned successfully.');
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error assigning stores to owner', [
                'owner_id' => $owner->id,
                'error' => $e->getMessage(),
                'store_ids' => $validatedData['store_ids']
            ]);
            
            return redirect()->back()->withErrors(['error' => 'Failed to assign stores. Please try again.']);
        }
    }
}
