<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Events\ManagerAssignedToStores;
use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_managers');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $managers = User::where('role', UserRole::MANAGER)->with('stores')->get();
        return view('managers.index', compact('managers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $stores = $user->accessibleStores()->get();
        
        return view('managers.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Manager creation started', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role?->value,
            'request_data' => $request->except(['password'])
        ]);

        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'username' => 'nullable|string|max:255',
                'assigned_stores' => 'nullable|array',
                'assigned_stores.*' => 'integer',
            ]);

            \Log::info('Validation passed', ['validated_fields' => array_keys($validatedData)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['password'])
            ]);
            throw $e;
        }

        // Use secure role assignment - only admins or owners can create managers
        if (!auth()->user()->hasPermission('manage_managers')) {
            \Log::error('Permission denied for manager creation', [
                'user_id' => auth()->id(),
                'user_role' => auth()->user()->role?->value,
                'user_permissions' => auth()->user()->getAllPermissions()
            ]);
            abort(403, 'Insufficient permissions to create managers');
        }

        \Log::info('Permission check passed');
        
        // Store the plain password for email before hashing
        $temporaryPassword = $validatedData['password'];
        $validatedData['password'] = bcrypt($validatedData['password']);
        
        // Extract store assignments before creating user
        $assignedStores = $validatedData['assigned_stores'] ?? [];
        unset($validatedData['assigned_stores']); // Remove from user creation data

        \Log::info('Creating manager user', [
            'user_data' => array_keys($validatedData),
            'assigned_stores' => $assignedStores
        ]);

        try {
            DB::beginTransaction();

            $manager = User::create($validatedData);
            \Log::info('Manager user created', ['manager_id' => $manager->id]);

            $manager->changeRole(UserRole::MANAGER, auth()->user());
            \Log::info('Manager role assigned', ['manager_id' => $manager->id]);

            // Assign stores using pivot table
            $assignedStoresCollection = collect();
            if (!empty($assignedStores)) {
                $manager->stores()->sync($assignedStores);
                $assignedStoresCollection = Store::whereIn('id', $assignedStores)->get();
                \Log::info('Stores assigned to manager', [
                    'manager_id' => $manager->id,
                    'store_count' => $assignedStoresCollection->count()
                ]);
            }

            // Send welcome email directly
            try {
                \Mail::to($manager->email)->send(new \App\Mail\WelcomeNewManagerWithPassword(
                    $manager,
                    $assignedStoresCollection,
                    auth()->user(),
                    $temporaryPassword
                ));
                \Log::info('Welcome email sent successfully', ['manager_id' => $manager->id]);
            } catch (\Exception $emailError) {
                \Log::error('Failed to send welcome email', [
                    'manager_id' => $manager->id,
                    'error' => $emailError->getMessage()
                ]);
            }

            DB::commit();
            \Log::info('Manager creation completed successfully', ['manager_id' => $manager->id]);

            return redirect()->route('managers.index')->with('success', 'Manager created successfully. Welcome email has been sent.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Manager creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password'])
            ]);

            return redirect()->back()
                ->withInput($request->except(['password']))
                ->withErrors(['error' => 'Failed to create manager: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $manager)
    {
        $user = auth()->user();
        
        // Filter stores based on current user's permissions
        if ($user->isAdmin()) {
            // Admins can assign managers to any store
            $stores = Store::all();
        } elseif ($user->isOwner()) {
            // Owners can only assign managers to stores they own
            $stores = Store::where('created_by', $user->id)->get();
        } else {
            // Managers shouldn't be able to edit other managers, but just in case
            $stores = collect();
        }
        
        $manager->load('stores'); // Load the pivot table relationship
        return view('managers.edit', compact('manager', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $manager)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $manager->id,
            'password' => 'nullable|string|min:8',
            'username' => 'nullable|string|max:255',
            'assigned_stores' => 'nullable|array',
            'assigned_stores.*' => 'integer',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        // Extract store assignments before updating user
        $assignedStores = $validatedData['assigned_stores'] ?? [];
        unset($validatedData['assigned_stores']); // Remove from user update data

        // Get current store assignments before update
        $previousStores = $manager->stores;

        // Update user basic info
        $manager->update($validatedData);

        // Update store assignments using pivot table
        $manager->stores()->sync($assignedStores);

        // Get updated store assignments
        $currentStores = Store::whereIn('id', $assignedStores)->get();

        // Dispatch event for email notification if there are changes
        if ($currentStores->isNotEmpty()) {
            event(new ManagerAssignedToStores(
                $manager,
                $currentStores,
                auth()->user(),
                false, // isNewManager
                $previousStores
            ));
        }

        return redirect()->route('managers.index')->with('success', 'Manager updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $manager)
    {
        $manager->delete();

        return redirect()->route('managers.index')->with('success', 'Manager deleted successfully.');
    }

    /**
     * Display the form for assigning stores to the manager.
     */
    public function assignStoresForm(User $manager)
    {
        $user = auth()->user();
        
        // Filter stores based on current user's permissions
        if ($user->isAdmin()) {
            // Admins can assign managers to any store
            $stores = Store::all();
        } elseif ($user->isOwner()) {
            // Owners can only assign managers to stores they own
            $stores = Store::where('created_by', $user->id)->get();
        } else {
            // Managers shouldn't be able to assign stores to other managers
            $stores = collect();
        }
        
        return view('managers.assign-stores', compact('manager', 'stores'));
    }

    /**
     * Assign stores to the manager.
     */
    public function assignStores(Request $request, User $manager)
    {
        $validatedData = $request->validate([
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        // Get current store assignments before update
        $previousStores = $manager->stores;

        $manager->stores()->sync($validatedData['store_ids']);

        // Get updated store assignments
        $currentStores = Store::whereIn('id', $validatedData['store_ids'])->get();

        // Dispatch event for email notification
        event(new ManagerAssignedToStores(
            $manager,
            $currentStores,
            auth()->user(),
            false, // isNewManager
            $previousStores
        ));

        return redirect()->route('managers.index')->with('success', 'Stores assigned successfully.');
    }
}
