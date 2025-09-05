<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use App\Models\User;

class OwnerController extends Controller
{
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

            // Only admins can create owners
            if (!auth()->user()->hasPermission('manage_owners')) {
                abort(403, 'Insufficient permissions to create owners');
            }

            $validatedData['email_verified_at'] = now();
            $validatedData['password'] = bcrypt($validatedData['password']);

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validatedData['avatar'] = basename($avatarPath);
            }

            $owner = User::create($validatedData);
            $owner->changeRole(UserRole::OWNER, auth()->user());

            return redirect()->route('owners.index')->with('success', 'Owner created successfully with complete profile information.');
        }

        return view('owners.create');
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

    public function assignStores(Request $request, User $manager)
    {
        $validatedData = $request->validate([
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id',
        ]);

        $manager->stores()->sync($validatedData['store_ids']);

        return redirect()->back()->with('success', 'Stores assigned successfully.');
    }
}
