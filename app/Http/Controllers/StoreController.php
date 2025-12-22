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
        $this->middleware('role:admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Franchisor: can see every store (Corporate and Franchisee)
        // Franchisee (Owner): can see their stores
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
        
        // Assign the owner via pivot table
        $store->owners()->attach($validatedData['created_by']);
        
        // Corporate Stores: Controlled by Franchisor, report to Franchisor
        // Franchisee locations: Controlled by Owner, but also report to Franchisor
        $franchisor = User::getOrCreateFranchisor();
        if (!$store->owners()->where('users.id', $franchisor->id)->exists()) {
            $store->owners()->attach($franchisor->id);
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

        return view('stores.show', compact('store', 'availableOwners'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        $owners = User::where('role', 'owner')->get();

        return view('stores.edit', compact('store', 'owners'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        $validatedData = $request->validated();

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
