<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Only Admin and Franchisor (controls entire Fann's Philly Grill brand) can manage stores
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user->isAdmin() && !$user->isFranchisor()) {
                abort(403, 'Unauthorized access. Only Administrators and Franchisor can manage stores.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Franchisor (controls entire Fann's Philly Grill brand): can see every store (Corporate and Franchisee)
        // Franchisee (Owner): Controls one or more locations, reports to Franchisor - can see their stores
        // Manager: can only see their assigned stores
        $stores = $user->accessibleStores()->with('owners')->get();

        return view('stores.index', compact('stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $owners = User::where('role', 'owner')->get();
        $franchisor = User::getOrCreateFranchisor();

        return view('stores.create', compact('owners', 'franchisor'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'store_info' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\(\d{3}\)\s\d{3}-\d{4}$/',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'store_type' => 'required|in:corporate,franchisee',
            'created_by' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    $user = User::find($value);
                    if (! $user || ! $user->isOwner()) {
                        $fail('Only owners can be assigned to stores. Admins cannot be assigned.');
                    }
                    
                    // Corporate Stores must be created by Franchisor
                    if ($request->input('store_type') === 'corporate' && !$user->isFranchisor()) {
                        $fail('Corporate Stores must be created by the Franchisor.');
                    }
                },
            ],
            'sales_tax_rate' => 'required|numeric|min:0',
            'medicare_tax_rate' => 'nullable|numeric|min:0',
        ]);

        $store = Store::create($validatedData);
        
        $franchisor = User::getOrCreateFranchisor();
        
        // Corporate Stores: Controlled by Franchisor, report to Franchisor, run by Managers
        // The Franchisor is the controlling owner for Corporate Stores
        if ($validatedData['store_type'] === 'corporate') {
            // Corporate Stores are controlled by Franchisor
            // Remove any other owners and set Franchisor as the controlling owner
            $store->owners()->sync([$franchisor->id]);
        } else {
            // Franchisee locations: Controlled by Owner (Franchisee), reports to Franchisor, can have Managers running a location
            // Assign the owner (Franchisee) via pivot table
            $store->owners()->attach($validatedData['created_by']);
            // Also attach Franchisor for reporting purposes (Franchisee reports to Franchisor)
            if (!$store->owners()->where('users.id', $franchisor->id)->exists()) {
                $store->owners()->attach($franchisor->id);
            }
        }

        return redirect()->route('stores.index')->with('success', 'Store created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        $store->load('owners');
        $availableOwners = User::where('role', 'owner')->get();
        
        // Get all managers assigned to this store (via direct store_id or pivot table)
        $managerIds = User::where('role', 'manager')
            ->where(function($query) use ($store) {
                $query->where('store_id', $store->id)
                    ->orWhereHas('assignedStoresPivot', function($q) use ($store) {
                        $q->where('stores.id', $store->id);
                    });
            })
            ->pluck('id')
            ->toArray();
        
        $managers = User::whereIn('id', $managerIds)->with(['store', 'assignedStoresPivot'])->get();

        return view('stores.show', compact('store', 'availableOwners', 'managers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        $owners = User::where('role', 'owner')->get();
        $franchisor = User::getOrCreateFranchisor();

        return view('stores.edit', compact('store', 'owners', 'franchisor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        $validatedData = $request->validated();
        
        $franchisor = User::getOrCreateFranchisor();
        
        // If changing to Corporate Store, ensure Franchisor is the controlling owner
        if ($validatedData['store_type'] === 'corporate') {
            // Corporate Stores: Controlled by Franchisor, report to Franchisor, run by Managers
            // Ensure Franchisor is the only controlling owner
            $store->owners()->sync([$franchisor->id]);
        } elseif ($store->isCorporateStore() && $validatedData['store_type'] === 'franchisee') {
            // If changing from Corporate to Franchisee, assign the created_by owner
            // Franchisee (Owner): Controls one or more locations, reports to Franchisor, can have Managers running a location
            // Also attach Franchisor for reporting purposes (Franchisee reports to Franchisor)
            $store->owners()->sync([$validatedData['created_by'], $franchisor->id]);
        } elseif ($validatedData['store_type'] === 'franchisee') {
            // For Franchisee locations: Controlled by Owner (Franchisee), reports to Franchisor, can have Managers running a location
            // Ensure both the owner and Franchisor are attached
            $ownerIds = [$validatedData['created_by']];
            if (!$store->owners()->where('users.id', $franchisor->id)->exists()) {
                $ownerIds[] = $franchisor->id;
            }
            $store->owners()->sync($ownerIds);
        }

        $store->update($validatedData);

        return redirect()->route('stores.index')->with('success', 'Store updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()->route('stores.index')->with('success', 'Store deleted successfully.');
    }

    /**
     * Show the form for assigning an owner to a store.
     */
    public function assignOwnerForm(Store $store)
    {
        $owners = User::where('role', 'owner')->get();
        $assignedOwners = $store->owners;

        return view('stores.assign-owner', compact('store', 'owners', 'assignedOwners'));
    }

    /**
     * Assign an owner to a store.
     */
    public function assignOwner(Request $request, Store $store)
    {
        $request->validate([
            'owner_ids' => 'required|array',
            'owner_ids.*' => [
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (! $user || ! $user->isOwner()) {
                        $fail('Only owners can be assigned to stores. Admins cannot be assigned.');
                    }
                },
            ],
        ]);

        // Sync the owners - this will replace all current assignments
        $store->owners()->sync($request->owner_ids);

        return redirect()->route('stores.show', $store)->with('success', 'Store owners updated successfully.');
    }
}
