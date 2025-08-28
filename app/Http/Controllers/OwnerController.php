<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class OwnerController extends Controller
{
    public function index()
    {
        $owners = User::where('role', 'owner')->get();
        return view('owners.index', ['owners' => $owners]);
    }

    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $validatedData['email_verified_at'] = now();
            $validatedData['role'] = 'owner';
            $validatedData['password'] = bcrypt($validatedData['password']);

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $validatedData['avatar'] = $avatarPath;
            }

            User::create($validatedData);

            return redirect()->route('owners.index')->with('success', 'Owner created successfully.');
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $owner->id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|image',
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validatedData['avatar'] = $avatarPath;
        }

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $owner->update($validatedData);

        return redirect()->route('owners.index')->with('success', 'Owner updated successfully.');
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
