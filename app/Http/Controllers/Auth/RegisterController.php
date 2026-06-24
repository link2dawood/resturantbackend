<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Helpers\USStates;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use App\Support\TrialMailer;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
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
        $stateCodes = 'in:'.implode(',', array_keys(USStates::getStates()));

        return Validator::make($data, [
            // Step 1 — Account
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Step 2 — Business / corporate details
            'state' => ['required', 'string', 'size:2', $stateCodes],
            'corporate_address' => ['required', 'string', 'max:1000'],
            'corporate_phone' => ['required', 'string', 'max:30'],
            'corporate_email' => ['required', 'email', 'max:255'],
            'corporate_ein' => ['nullable', 'string', 'max:20'],
            'corporate_creation_date' => ['nullable', 'date'],

            // Step 3 — First restaurant / store
            'store_info' => ['required', 'string', 'max:255'],
            'store_contact_name' => ['required', 'string', 'max:255'],
            'store_phone' => ['required', 'string', 'max:30'],
            'store_address' => ['required', 'string', 'max:255'],
            'store_city' => ['required', 'string', 'max:120'],
            'store_state' => ['required', 'string', 'size:2', $stateCodes],
            'store_zip' => ['required', 'string', 'max:12'],
            'store_sales_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'store_medicare_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // Create the owner, their corporate profile, and their first restaurant
        // atomically — a failure anywhere rolls the whole signup back.
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                // Business / corporate profile
                'state' => $data['state'],
                'corporate_address' => $data['corporate_address'],
                'corporate_phone' => $data['corporate_phone'],
                'corporate_email' => $data['corporate_email'],
                'corporate_ein' => $data['corporate_ein'] ?? null,
                'corporate_creation_date' => $data['corporate_creation_date'] ?? null,
            ]);

            // Self-serve SaaS signup: a public registrant is the owner of their own
            // workspace. `role` is guarded against mass assignment, so set it
            // directly. Email stays unverified until they confirm (sent below).
            $user->role = UserRole::OWNER;
            $user->save();

            // Their first restaurant. created_by ties it to the owner so it shows
            // up in accessibleStores() immediately and the trial is usable.
            Store::create([
                'store_info' => $data['store_info'],
                'contact_name' => $data['store_contact_name'],
                'phone' => $data['store_phone'],
                'address' => $data['store_address'],
                'city' => $data['store_city'],
                'state' => $data['store_state'],
                'zip' => $data['store_zip'],
                'sales_tax_rate' => $data['store_sales_tax_rate'],
                'medicare_tax_rate' => $data['store_medicare_tax_rate'],
                'store_type' => 'franchisee',
                'created_by' => $user->id,
            ]);

            return $user;
        });

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
