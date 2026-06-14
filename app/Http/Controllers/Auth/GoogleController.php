<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TrialMailer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $existing = User::where('google_id', $googleUser->id)->first();

            if ($existing) {
                Auth::login($existing);

                return redirect()->intended('home');
            }

            // New Google sign-up: provision a self-serve Owner. Google has already
            // verified the email address, so mark it verified and skip our own flow.
            $user = User::updateOrCreate(
                ['email' => $googleUser->email],
                [
                    'name' => $googleUser->name,
                    'google_id' => $googleUser->id,
                    // Random, unguessable password — the account signs in via OAuth,
                    // never with this value (replaces the old hardcoded "123456dummy").
                    'password' => Hash::make(Str::random(40)),
                    'email_verified_at' => now(),
                ]
            );

            // Only assign the Owner role to brand-new accounts; never downgrade an
            // existing admin/manager who happens to log in with Google.
            if (is_null($user->role)) {
                $user->role = UserRole::OWNER;
                $user->save();

                // Brand-new self-serve owner: start the trial + send signup emails.
                $user->startTrial();
                TrialMailer::signup($user);
            }

            Auth::login($user);

            return redirect()->intended('home');
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => 'We could not sign you in with Google. Please try again.']);
        }
    }
}
