<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_confirmation_errors_identify_the_confirmation_field(): void
    {
        $user = $this->user('Nurse', 'confirmationnurse');
        $original = $user->password;
        foreach (['', 'DifferentPassword1!'] as $confirmation) {
            $this->actingAs($user)->from('/profile/edit')->put('/profile', [
                'name' => $user->name, 'current_password' => 'OriginalPassword1!',
                'password' => 'NewPassword123!', 'password_confirmation' => $confirmation,
                '_validation_form' => 'http://localhost/profile',
            ])->assertRedirect('/profile/edit')
                ->assertSessionHasErrors('password_confirmation')
                ->assertSessionDoesntHaveErrors('password')
                ->assertSessionMissing('_old_input.password')
                ->assertSessionMissing('_old_input.password_confirmation');
            $this->assertSame($original, $user->fresh()->password);
            $this->get('/profile/edit')->assertOk()
                ->assertSee('id="validation-errors"', false)
                ->assertSee('data-validation-key="password_confirmation"', false)
                ->assertSee('js/form-validation.js', false);
        }
    }

    public function test_admin_password_confirmation_errors_are_scoped_to_the_user_form(): void
    {
        $admin = $this->user('Administrator', 'confirmationadmin');
        $staff = $this->user('Receptionist', 'confirmationstaff');
        $this->actingAs($admin)->from('/users/'.$staff->id.'/edit')->put('/users/'.$staff->id, [
            'name' => $staff->name, 'username' => $staff->username, 'role' => $staff->role, 'is_active' => 1,
            'password' => 'UpdatedPassword1!', 'password_confirmation' => 'DifferentPassword1!',
            '_validation_form' => 'http://localhost/users/'.$staff->id,
        ])->assertSessionHasErrors('password_confirmation')->assertSessionDoesntHaveErrors('password');
        $this->get('/users/'.$staff->id.'/edit')->assertOk()
            ->assertSee('data-validation-scope="http://localhost/users/'.$staff->id.'"', false);
    }

    public function test_current_password_and_short_password_errors_use_clear_field_messages(): void
    {
        $user = $this->user('Physician', 'validationphysician');
        $this->actingAs($user)->from('/profile/edit')->put('/profile', [
            'name' => $user->name, 'current_password' => 'IncorrectPassword1!',
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors([
            'current_password' => 'Your current password is incorrect. Re-enter the password you use to sign in.',
            'password' => 'Your new password must contain at least 8 characters.',
        ]);
    }

    public function test_login_failures_identify_the_password_without_exposing_account_existence(): void
    {
        $user = $this->user('Receptionist', 'validationlogin');
        foreach ([$user->username, 'unknown-account'] as $username) {
            $this->from('/')->post('/login', ['username' => $username, 'password' => 'IncorrectPassword1!'])
                ->assertSessionHasErrors(['password' => 'The selected clinic account or password is incorrect. Check the account and re-enter your password.'])
                ->assertSessionMissing('_old_input.password');
            $this->get('/')->assertOk()->assertSee('data-validation-key="password"', false);
        }
    }

    public function test_missing_required_information_is_returned_with_field_keys(): void
    {
        $user = $this->user('Receptionist', 'missingprofile');
        $this->actingAs($user)->from('/profile/edit')->put('/profile', ['name' => ''])
            ->assertSessionHasErrors('name');
        $this->get('/profile/edit')->assertOk()->assertSee('data-validation-key="name"', false);
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => $username, 'username' => $username, 'role' => $role,
            'is_active' => true, 'password' => 'OriginalPassword1!',
        ]);
    }
}
