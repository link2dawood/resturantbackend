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
        $managers = User::where('role', UserRole::MANAGER)->with('store')->get();
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
                'store_id' => 'nullable|exists:stores,id',
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
        
        \Log::info('Creating manager user', [
            'user_data' => array_keys($validatedData),
            'store_id' => $validatedData['store_id'] ?? null
        ]);

        try {
            DB::beginTransaction();

            $manager = User::create($validatedData);
            \Log::info('Manager user created', ['manager_id' => $manager->id]);

            $manager->changeRole(UserRole::MANAGER, auth()->user());
            \Log::info('Manager role assigned', ['manager_id' => $manager->id]);

            // Get assigned store for email notification
            $assignedStore = null;
            if (!empty($validatedData['store_id'])) {
                $assignedStore = Store::find($validatedData['store_id']);
                \Log::info('Store assigned to manager', [
                    'manager_id' => $manager->id,
                    'store_id' => $validatedData['store_id']
                ]);
            }

            // Send welcome email directly
            try {
                \Mail::to($manager->email)->send(new \App\Mail\WelcomeNewManagerWithPassword(
                    $manager,
                    $assignedStore ? collect([$assignedStore]) : collect(),
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
    public function show(User $manager)
    {
        // Ensure the user is actually a manager
        if (!$manager->isManager()) {
            abort(404);
        }

        $manager->load('store');
        return view('managers.show', compact('manager'));
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
        
        $manager->load('store'); // Load the store relationship
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
            'store_id' => 'nullable|exists:stores,id',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        // Get current store assignment before update
        $previousStore = $manager->store;

        // Update user basic info (including store_id)
        $manager->update($validatedData);

        // Get updated store assignment
        $currentStore = null;
        if (!empty($validatedData['store_id'])) {
            $currentStore = Store::find($validatedData['store_id']);
        }

        // Dispatch event for email notification if there are changes
        if ($currentStore && (!$previousStore || $previousStore->id !== $currentStore->id)) {
            event(new ManagerAssignedToStores(
                $manager,
                collect([$currentStore]),
                auth()->user(),
                false, // isNewManager
                $previousStore ? collect([$previousStore]) : collect()
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
     * Display the form for assigning a store to the manager.
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
     * Assign a store to the manager.
     */
    public function assignStores(Request $request, User $manager)
    {
        $validatedData = $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        // Get current store assignment before update
        $previousStore = $manager->store;

        // Update manager's store assignment
        $manager->update(['store_id' => $validatedData['store_id']]);

        // Get updated store assignment
        $currentStore = Store::find($validatedData['store_id']);

        // Dispatch event for email notification
        event(new ManagerAssignedToStores(
            $manager,
            collect([$currentStore]),
            auth()->user(),
            false, // isNewManager
            $previousStore ? collect([$previousStore]) : collect()
        ));

        return redirect()->route('managers.index')->with('success', 'Store assigned successfully.');
    }
}
