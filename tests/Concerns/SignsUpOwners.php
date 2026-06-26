<?php

namespace Tests\Concerns;

/**
 * Phase 4 — multi-step owner signup.
 *
 * The /register endpoint now requires the owner's business profile and their
 * first restaurant (all steps required). This builds a complete, valid payload
 * so tests can override just the fields they care about.
 */
trait SignsUpOwners
{
    protected function ownerSignupPayload(array $overrides = []): array
    {
        return array_merge([
            // Account
            'name' => 'Jane Founder',
            'email' => 'jane@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            // Business / corporate
            'corporate_name' => 'Acme Restaurants LLC',
            'state' => 'CA',
            'corporate_address' => '123 Corp St',
            'corporate_phone' => '(555) 123-4567',
            'corporate_email' => 'office@example.com',
            // First restaurant
            'store_info' => 'Downtown Diner',
            'store_contact_name' => 'Jane Founder',
            'store_phone' => '(555) 765-4321',
            'store_address' => '456 Market St',
            'store_city' => 'Philadelphia',
            'store_state' => 'PA',
            'store_zip' => '19102',
            'store_sales_tax_rate' => '8.5',
            'store_medicare_tax_rate' => '1.45',
            // Terms acceptance
            'terms' => 'on',
        ], $overrides);
    }
}
