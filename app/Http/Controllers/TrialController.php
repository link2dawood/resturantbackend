<?php

namespace App\Http\Controllers;

use App\Support\TrialMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrialController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * The "Trial Expired" screen. If the workspace actually still has access
     * (e.g. an admin, or a paid/active account that landed here by accident),
     * send them back to the dashboard instead of showing the wall.
     */
    public function expired(Request $request)
    {
        $user = $request->user();
        $owner = $user->billingOwner();

        if (! $owner || $owner->hasActiveAccess()) {
            return redirect()->route('home');
        }

        return view('trial.expired', [
            'owner' => $owner,
            'isOwner' => $user->is($owner),
            'requestedAt' => $owner->trial_extension_requested_at,
        ]);
    }

    /**
     * "Request to Continue" CTA: record the request and alert the sales team
     * (plus confirm to the client). Only the owner can submit this.
     */
    public function requestContinue(Request $request)
    {
        $user = $request->user();
        $owner = $user->billingOwner();

        if (! $owner) {
            return redirect()->route('home');
        }

        // Only the owner of the workspace can request continuation; a manager is
        // sent back to the expired screen with a note.
        if (! $user->is($owner)) {
            return redirect()->route('trial.expired')
                ->with('error', 'Please ask your account owner to request continuation.');
        }

        // Debounce: avoid spamming sales if they click repeatedly.
        $alreadyRequested = $owner->trial_extension_requested_at
            && $owner->trial_extension_requested_at->greaterThan(now()->subDay());

        if (! $alreadyRequested) {
            $owner->trial_extension_requested_at = now();
            $owner->save();

            TrialMailer::continueRequested($owner);
        }

        return redirect()->route('trial.expired')
            ->with('success', 'Thanks! Our team has been notified and will reach out to help you continue.');
    }
}
