<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_redirect_to_login_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
