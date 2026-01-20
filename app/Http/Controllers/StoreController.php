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
            // One store can have only one owner - set Franchisor as the owner
            // Database unique constraint on store_id ensures this at the root level
            $store->owners()->sync([$franchisor->id]);
        } else {
            // Franchisee locations: Controlled by Owner (Franchisee), reports to Franchisor, can have Managers running a location
            // One store can have only one owner - assign the owner (Franchisee) only
            // Database unique constraint on store_id ensures this at the root level
            $store->owners()->sync([$validatedData['created_by']]);
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
            // One store can have only one owner - set Franchisor as the owner
            // Database unique constraint on store_id ensures this at the root level
            $store->owners()->sync([$franchisor->id]);
        } elseif ($store->isCorporateStore() && $validatedData['store_type'] === 'franchisee') {
            // If changing from Corporate to Franchisee, assign the created_by owner
            // Franchisee (Owner): Controls one or more locations, reports to Franchisor, can have Managers running a location
            // One store can have only one owner - assign the owner (Franchisee) only
            // Database unique constraint on store_id ensures this at the root level
            $store->owners()->sync([$validatedData['created_by']]);
        } elseif ($validatedData['store_type'] === 'franchisee') {
            // For Franchisee locations: Controlled by Owner (Franchisee), reports to Franchisor, can have Managers running a location
            // One store can have only one owner - ensure the owner is assigned
            // Database unique constraint on store_id ensures this at the root level
            $store->owners()->sync([$validatedData['created_by']]);
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
        // One store can have only one owner (enforced by database constraint)
        $assignedOwner = $store->assignedOwner();

        return view('stores.assign-owner', compact('store', 'owners', 'assignedOwner'));
    }

    /**
     * Assign an owner to a store.
     */
    public function assignOwner(Request $request, Store $store)
    {
        $request->validate([
            'owner_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (! $user || ! $user->isOwner()) {
                        $fail('Only owners can be assigned to stores. Admins cannot be assigned.');
                    }
                },
            ],
        ]);

        try {
            // Validate that the owner exists and is actually an owner
            $owner = User::findOrFail($request->owner_id);
            if (!$owner->isOwner()) {
                return redirect()->back()->withErrors([
                    'owner_id' => 'Only owners can be assigned to stores.'
                ]);
            }

            // One store can have only one owner - sync with single owner
            // The unique constraint on store_id in the pivot table ensures this at the database level
            // This will automatically remove any existing owner and assign the new one
            $store->owners()->sync([$request->owner_id]);

            return redirect()->route('stores.show', $store)->with('success', 'Store owner updated successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database constraint violations
            // The unique constraint on store_id should prevent multiple owners
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'unq_owner_store_store_id')) {
                return redirect()->back()->withErrors([
                    'owner_id' => 'This store already has an owner assigned. The database constraint prevents multiple owners per store.'
                ]);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error assigning owner to store', [
                'store_id' => $store->id,
                'owner_id' => $request->owner_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors([
                'owner_id' => 'Failed to assign owner. Please try again.'
            ]);
        }
    }
}
