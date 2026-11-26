<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_logins_are_limited_without_flashing_passwords_and_expire(): void
    {
        $this->freezeTime();
        $staff = $this->user('Nurse', 'limitedstaff');
        $browser = [];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->request($browser, 'POST', '/login', [
                'username' => $staff->username, 'password' => 'IncorrectPassword1!',
            ])->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
        }

        $this->request($browser, 'POST', '/login', [
            'username' => $staff->username, 'password' => 'OriginalClinic1!',
        ])->assertSessionHasErrors(['password' => 'Too many sign-in attempts. Try again in 60 seconds.'])
            ->assertSessionHas('_old_input.username', $staff->username)
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->login($browser, $staff);
        $this->request($browser, 'GET', '/profile')->assertOk();
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        $staff = $this->user('Nurse', 'clearedstaff');
        $browser = [];
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->request($browser, 'POST', '/login', [
                'username' => $staff->username, 'password' => 'IncorrectPassword1!',
            ])->assertSessionHasErrors('password');
        }
        $this->login($browser, $staff);
        $this->request($browser, 'POST', '/logout')->assertRedirect('/');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->request($browser, 'POST', '/login', [
                'username' => $staff->username, 'password' => 'IncorrectPassword1!',
            ])->assertSessionHasErrors(['password' => 'The selected clinic account or password is incorrect. Check the account and re-enter your password.']);
        }
    }

    public function test_account_deactivation_blocks_an_existing_session_on_auth_only_routes(): void
    {
        $staff = $this->user('Nurse', 'deactivatedstaff');
        $admin = $this->user('Administrator', 'deactivatingadmin');
        $staffBrowser = [];
        $adminBrowser = [];
        $this->login($staffBrowser, $staff);
        $this->request($staffBrowser, 'GET', '/patients')->assertOk();
        $this->login($adminBrowser, $admin);
        $this->request($adminBrowser, 'PATCH', '/users/'.$staff->id.'/deactivate')->assertRedirect();

        $originalSession = $staffBrowser;
        $this->request($staffBrowser, 'GET', '/patients', [], true)->assertForbidden();
        $this->assertGuest();

        $this->request($adminBrowser, 'PUT', '/users/'.$staff->id, $this->staffFields($staff))
            ->assertRedirect('/users');
        $this->request($staffBrowser, 'GET', '/profile')->assertRedirect('/');
        $this->request($originalSession, 'GET', '/profile')->assertRedirect('/');
    }

    public function test_inactive_accounts_cannot_use_any_auth_only_pages(): void
    {
        $staff = $this->user('Nurse', 'inactivepages');
        foreach (['/profile', '/profile/edit', '/dashboard', '/patients', '/schedules', '/visits'] as $url) {
            $staff->update(['is_active' => true]);
            $browser = [];
            $this->login($browser, $staff);
            // Even if the password hash also changed, inactive access returns 403.
            $staff->setLoginPassword('OriginalClinic1!');
            $staff->is_active = false;
            $staff->save();
            $this->request($browser, 'GET', $url, [], true)->assertForbidden();
            $this->assertGuest();
        }
    }

    public function test_admin_reset_revokes_a_real_session_before_its_first_protected_request(): void
    {
        $staff = $this->user('Nurse', 'resetstaff');
        $admin = $this->user('Administrator', 'resettingadmin');
        $staffBrowser = [];
        $adminBrowser = [];
        $this->login($staffBrowser, $staff);
        $this->login($adminBrowser, $admin);
        $this->request($adminBrowser, 'PUT', '/users/'.$staff->id, $this->staffFields($staff) + [
            'password' => 'ReplacementClinic1!', 'password_confirmation' => 'ReplacementClinic1!',
        ])->assertRedirect('/users');

        $originalSession = $staffBrowser;
        $this->request($staffBrowser, 'GET', '/profile')->assertRedirect('/');
        $this->assertGuest();
        $this->request($originalSession, 'GET', '/profile')->assertRedirect('/');
        $this->login($staffBrowser, $staff, 'ReplacementClinic1!', '/profile');
        $this->request($staffBrowser, 'GET', '/profile')->assertOk();
    }

    public function test_own_password_change_keeps_current_browser_and_revokes_another_browser(): void
    {
        $staff = $this->user('Nurse', 'passwordstaff');
        $currentBrowser = [];
        $otherBrowser = [];
        $this->login($currentBrowser, $staff);
        $this->login($otherBrowser, $staff);
        $this->request($currentBrowser, 'PUT', '/profile', [
            'name' => $staff->name, 'current_password' => 'OriginalClinic1!',
            'password' => 'ChangedClinic1!', 'password_confirmation' => 'ChangedClinic1!',
        ])->assertRedirect('/profile');

        $this->request($currentBrowser, 'GET', '/profile')->assertOk();
        $this->request($otherBrowser, 'GET', '/profile')->assertRedirect('/');
        $this->assertGuest();
        $this->request($currentBrowser, 'GET', '/profile')->assertOk();
    }

    public function test_blank_admin_password_edits_do_not_revoke_staff_sessions(): void
    {
        $staff = $this->user('Nurse', 'unchangedstaff');
        $admin = $this->user('Administrator', 'editingadmin');
        $staffBrowser = [];
        $adminBrowser = [];
        $this->login($staffBrowser, $staff);
        $this->login($adminBrowser, $admin);
        $this->request($adminBrowser, 'PUT', '/users/'.$staff->id, $this->staffFields($staff) + [
            'password' => '', 'password_confirmation' => '',
        ])->assertRedirect('/users');
        $this->request($staffBrowser, 'GET', '/profile')->assertOk();
    }

    public function test_sessions_from_before_the_security_update_must_sign_in_again(): void
    {
        $staff = $this->user('Nurse', 'previoussession');
        $browser = [];
        $this->login($browser, $staff);
        // Remove only the marker from the real saved session, retaining login ID/hash.
        $session = $this->app['session']->driver();
        $session->forget('clinic_auth_session_version');
        $session->save();

        $this->request($browser, 'GET', '/profile')->assertRedirect('/');
        $this->assertGuest();
        $this->login($browser, $staff, 'OriginalClinic1!', '/profile');
        $this->request($browser, 'GET', '/profile')->assertOk();
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => $username, 'username' => $username, 'role' => $role,
            'is_active' => true, 'password' => 'OriginalClinic1!',
        ]);
    }

    private function staffFields(User $user): array
    {
        return ['name' => $user->name, 'username' => $user->username, 'role' => $user->role, 'is_active' => 1];
    }

    private function login(array &$browser, User $user, string $password = 'OriginalClinic1!', string $redirect = '/dashboard'): void
    {
        $this->request($browser, 'POST', '/login', ['username' => $user->username, 'password' => $password])
            ->assertRedirect($redirect);
        $this->assertAuthenticatedAs($user);
    }

    private function request(array &$browser, string $method, string $uri, array $data = [], bool $json = false): TestResponse
    {
        // Simulate separate browsers using real encrypted response cookies and fresh guards.
        // Keep the array-session handler so each browser's session survives subsequent requests.
        $this->app['session']->driver()->flush();
        $this->app['auth']->forgetGuards();
        $this->app['cookie']->flushQueuedCookies();
        $response = $this->call($method, $uri, $data, $browser, [], $json ? ['HTTP_ACCEPT' => 'application/json'] : []);
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() <= now()->timestamp) {
                unset($browser[$cookie->getName()]);
            } else {
                $browser[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $response;
    }
}
