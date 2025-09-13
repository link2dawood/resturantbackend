<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_stores')->only(['create', 'store', 'edit', 'update', 'destroy']);
        $this->middleware('permission:view_stores')->only(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $stores = $user->accessibleStores()->get();
        
        return view('stores.index', compact('stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $owners = User::where('role','owner')->get();
        return view('stores.create',compact('owners'));
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
            'created_by' => 'required',
            'sales_tax_rate' => 'required|numeric|min:0',
            'medicare_tax_rate' => 'nullable|numeric|min:0',
        ]);

       

        Store::create($validatedData);

        return redirect()->route('stores.index')->with('success', 'Store created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function reports()
    {
        return view('reports');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        $owners = User::where('role','owner')->get();
        return view('stores.edit', compact('store','owners'));
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
}
