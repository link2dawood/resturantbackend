<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TrialMailer;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Self-serve SaaS signup: a public registrant is the owner of their own
        // workspace. `role` is guarded against mass assignment, so set it directly.
        // Email stays unverified — the verification link is sent below and the
        // 'verified' middleware gates app access until they confirm.
        $user->role = UserRole::OWNER;
        $user->save();

        // Activate the 30-day free trial and notify the client + sales team.
        $user->startTrial();
        TrialMailer::signup($user);

        // New tenant: ensure the standard chart of accounts exists (idempotent;
        // a no-op once the global chart has been seeded).
        app(\App\Services\CoaTemplateService::class)->ensureSeededForNewTenant();

        // Send the email-verification link, but never let an SMTP/mail failure
        // break signup — the account is already created and the link can be
        // re-sent from the "verify your email" screen once mail is working.
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

        return $user;
    }
}
