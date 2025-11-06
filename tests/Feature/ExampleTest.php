<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Root route redirects to login if not authenticated
        $response = $this->get('/');
        
        // Should redirect to login (302) or return 200 if authenticated
        $this->assertContains($response->status(), [200, 302]);
    }
}
