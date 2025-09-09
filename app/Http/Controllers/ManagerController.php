<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ManagerController extends Controller
{
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
        
        // Filter stores based on current user's permissions
        if ($user->isAdmin()) {
            // Admins can assign managers to any store
            $stores = Store::all();
        } elseif ($user->isOwner()) {
            // Owners can only assign managers to stores they own
            $stores = Store::where('created_by', $user->id)->get();
        } else {
            // Managers shouldn't be able to create other managers, but just in case
            $stores = collect();
        }
        
        return view('managers.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'username' => 'nullable|string|max:255',
            'assigned_stores' => 'nullable|array',
            'assigned_stores.*' => 'integer',
        ]);

        // Use secure role assignment - only admins or owners can create managers
        if (!auth()->user()->hasPermission('manage_managers')) {
            abort(403, 'Insufficient permissions to create managers');
        }
        
        $validatedData['password'] = bcrypt($validatedData['password']);
        
        // Extract store assignments before creating user
        $assignedStores = $validatedData['assigned_stores'] ?? [];
        unset($validatedData['assigned_stores']); // Remove from user creation data

        $manager = User::create($validatedData);
        $manager->changeRole(UserRole::MANAGER, auth()->user());

        // Assign stores using pivot table
        if (!empty($assignedStores)) {
            $manager->stores()->sync($assignedStores);
        }

        return redirect()->route('managers.index')->with('success', 'Manager created successfully.');
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

        // Update user basic info
        $manager->update($validatedData);

        // Update store assignments using pivot table
        $manager->stores()->sync($assignedStores);

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

        $manager->stores()->sync($validatedData['store_ids']);

        return redirect()->route('managers.index')->with('success', 'Stores assigned successfully.');
    }
}
