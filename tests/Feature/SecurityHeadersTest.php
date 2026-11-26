<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_send_browser_security_and_private_cache_headers(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }

    public function test_authenticated_pages_send_the_same_headers(): void
    {
        $user = User::create([
            'name' => 'Header tester',
            'username' => 'header-tester',
            'role' => 'Nurse',
            'is_active' => true,
            'password' => 'OriginalClinic1!',
        ]);

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();

        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_forbidden_pages_keep_the_security_headers(): void
    {
        $user = User::create([
            'name' => 'Restricted tester',
            'username' => 'restricted-tester',
            'role' => 'Nurse',
            'is_active' => true,
            'password' => 'OriginalClinic1!',
        ]);

        $response = $this->actingAs($user)->get('/users')->assertForbidden();

        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
