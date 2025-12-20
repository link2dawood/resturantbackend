<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function start(User $user)
    {
        $currentUser = Auth::user();

        // Debug current user information
        \Log::info('Impersonation attempt debug', [
            'current_user_id' => $currentUser->id,
            'current_user_email' => $currentUser->email,
            'current_user_role' => $currentUser->role?->value,
            'is_admin_method' => $currentUser->isAdmin(),
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'target_user_role' => $user->role?->value,
        ]);

        // Only admins can impersonate
        if (! $currentUser || ! $currentUser->role || $currentUser->role !== UserRole::ADMIN) {
            \Log::warning('Non-admin impersonation attempt', [
                'user_id' => $currentUser->id ?? null,
                'user_email' => $currentUser->email ?? null,
                'user_role' => $currentUser->role?->value ?? null,
            ]);

            return redirect()->back()->with('error', 'Only administrators can impersonate users. Current role: '.($currentUser->role?->value ?? 'none'));
        }

        // Cannot impersonate self
        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'You cannot impersonate yourself');
        }

        // Cannot impersonate other admins
        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'You cannot impersonate other administrators');
        }

        // Only allow impersonating owners and managers
        if (! $user->isOwner() && ! $user->isManager()) {
            return redirect()->back()->with('error', 'You can only impersonate owners and managers');
        }

        // Store the original admin user ID in session
        Session::put('impersonating_admin_id', $currentUser->id);
        Session::put('impersonating_user_id', $user->id);

        // Log the impersonation start
        \Log::info('Impersonation started', [
            'admin_id' => $currentUser->id,
            'admin_email' => $currentUser->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'target_user_role' => $user->role?->value,
            'timestamp' => now(),
        ]);

        // Login as the target user
        Auth::login($user);

        return redirect(url('/home'))->with('success', "You are now impersonating {$user->name}");
    }

    /**
     * Stop impersonating and return to admin account.
     */
    public function stop()
    {
        $impersonatedUserId = Session::get('impersonating_user_id');
        $adminId = Session::get('impersonating_admin_id');

        if (! $adminId || ! $impersonatedUserId) {
            return redirect(url('/home'))->with('error', 'No active impersonation session found');
        }

        $admin = User::find($adminId);
        $impersonatedUser = User::find($impersonatedUserId);

        if (! $admin) {
            Session::forget(['impersonating_admin_id', 'impersonating_user_id']);

            return redirect()->route('login')->with('error', 'Original admin user not found');
        }

        // Log the impersonation end
        \Log::info('Impersonation ended', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'impersonated_user_id' => $impersonatedUserId,
            'impersonated_user_email' => $impersonatedUser?->email,
            'timestamp' => now(),
        ]);

        // Clear impersonation session data
        Session::forget(['impersonating_admin_id', 'impersonating_user_id']);

        // Login back as admin
        Auth::login($admin);

        // Redirect to home route - use URL directly to avoid route resolution issues
        return redirect(url('/home'))->with('success', 'Stopped impersonating. You are now back to your admin account');
    }

    /**
     * Check if currently impersonating.
     */
    public function isImpersonating()
    {
        return Session::has('impersonating_admin_id') && Session::has('impersonating_user_id');
    }

    /**
     * Get the original admin user if currently impersonating.
     */
    public function getOriginalAdmin()
    {
        $adminId = Session::get('impersonating_admin_id');

        return $adminId ? User::find($adminId) : null;
    }

    /**
     * Debug current user information - temporary method for troubleshooting
     */
    public function debug()
    {
        $user = Auth::user();

        return response()->json([
            'authenticated' => Auth::check(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
                'role_object' => $user->role,
                'is_admin' => $user->isAdmin(),
                'is_owner' => $user->isOwner(),
                'is_manager' => $user->isManager(),
            ] : null,
        ]);
    }
}
