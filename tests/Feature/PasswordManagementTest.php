<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_change_its_password_and_admin_reveals_the_latest_value(): void
    {
        $admin = $this->user('Administrator', 'supportadmin');

        foreach (['Receptionist', 'Nurse', 'Physician', 'Administrator'] as $index => $role) {
            $user = $this->user($role, 'staff'.$index);
            $newPassword = 'UpdatedClinic'.$index.'!';
            $this->actingAs($user)->get('/profile/edit')->assertOk()->assertSee('data-password-toggle', false);
            $this->put('/profile', [
                'name' => $user->name,
                'current_password' => 'OriginalClinic1!',
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])->assertRedirect('/profile');

            $user->refresh();
            $this->assertTrue(Hash::check($newPassword, $user->password));
            $this->assertFalse(Hash::check('OriginalClinic1!', $user->password));
            $this->assertSame($newPassword, Crypt::decryptString($user->password_ciphertext));
            $this->assertNotSame($newPassword, $user->password_ciphertext);
            $this->assertArrayNotHasKey('password_ciphertext', $user->toArray());
            $this->assertArrayNotHasKey('password', $user->toArray());

            $this->actingAs($admin)->get('/users/'.$user->id)
                ->assertOk()->assertSee('data-password-reveal', false)
                ->assertDontSee($newPassword)->assertDontSee($user->password_ciphertext);
            $response = $this->postJson('/users/'.$user->id.'/password/reveal')
                ->assertOk()->assertExactJson(['password' => $newPassword]);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

            $this->post('/logout');
            $this->post('/login', ['username' => $user->username, 'password' => 'OriginalClinic1!'])
                ->assertSessionHasErrors('password');
            $this->assertGuest();
            $this->post('/login', ['username' => $user->username, 'password' => $newPassword])
                ->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_admin_reset_updates_the_saved_password_and_blank_edits_preserve_it(): void
    {
        $admin = $this->user('Administrator', 'resetadmin');
        $staff = $this->user('Nurse', 'resetnurse');
        $fields = ['name' => $staff->name, 'username' => $staff->username, 'role' => 'Nurse', 'is_active' => 1];

        $this->actingAs($admin)->put('/users/'.$staff->id, $fields + [
            'password' => 'AdminReplacement1!', 'password_confirmation' => 'AdminReplacement1!',
        ])->assertRedirect('/users');
        $this->postJson('/users/'.$staff->id.'/password/reveal')
            ->assertOk()->assertExactJson(['password' => 'AdminReplacement1!']);
        $staff->refresh();
        $this->assertTrue(Hash::check('AdminReplacement1!', $staff->password));
        $ciphertext = $staff->password_ciphertext;

        $this->put('/users/'.$staff->id, $fields + ['password' => '', 'password_confirmation' => ''])
            ->assertRedirect('/users');
        $this->assertSame($ciphertext, $staff->fresh()->password_ciphertext);

        $this->post('/users', [
            'name' => 'New staff', 'username' => 'newstaff', 'role' => 'Receptionist', 'is_active' => 1,
            'password' => 'TemporaryClinic1!', 'password_confirmation' => 'TemporaryClinic1!',
        ])->assertRedirect('/users');
        $created = User::where('username', 'newstaff')->firstOrFail();
        $this->postJson('/users/'.$created->id.'/password/reveal')
            ->assertOk()->assertExactJson(['password' => 'TemporaryClinic1!']);

        $this->post('/logout');
        $this->post('/login', ['username' => $staff->username, 'password' => 'OriginalClinic1!'])
            ->assertSessionHasErrors('password');
        $this->post('/login', ['username' => $staff->username, 'password' => 'AdminReplacement1!'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($staff);
    }

    public function test_only_active_administrators_can_reveal_saved_passwords(): void
    {
        $target = $this->user('Nurse', 'target');
        $url = '/users/'.$target->id.'/password/reveal';
        $this->postJson($url)->assertUnauthorized();

        foreach (['Receptionist', 'Nurse', 'Physician'] as $index => $role) {
            $staff = $this->user($role, 'restricted'.$index);
            $this->actingAs($staff)->postJson($url)->assertForbidden();
            $this->get('/profile')->assertOk()->assertDontSee('data-password-reveal', false);
            $this->put('/users/'.$target->id, ['password' => 'Unauthorized1!'])->assertForbidden();
        }

        $admin = $this->user('Administrator', 'inactiveadmin');
        $admin->update(['is_active' => false]);
        $this->actingAs($admin)->postJson($url)->assertForbidden();
        $admin->update(['is_active' => true]);
        $this->getJson($url)->assertStatus(405);
    }

    public function test_invalid_password_changes_do_not_update_or_flash_passwords(): void
    {
        $staff = $this->user('Receptionist', 'validationstaff');
        $ciphertext = $staff->password_ciphertext;
        $this->actingAs($staff)->from('/profile/edit')->put('/profile', [
            'name' => $staff->name, 'current_password' => 'IncorrectCurrent1!',
            'password' => 'UnacceptedPassword1!', 'password_confirmation' => 'UnacceptedPassword1!',
        ])->assertSessionHasErrors('current_password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation')
            ->assertSessionMissing('_old_input.current_password');
        $this->assertSame($ciphertext, $staff->fresh()->password_ciphertext);

        $this->put('/profile', [
            'name' => $staff->name, 'current_password' => 'OriginalClinic1!',
            'password' => 'UnacceptedPassword1!', 'password_confirmation' => 'DifferentPassword1!',
        ])->assertSessionHasErrors('password_confirmation');
        $this->assertSame($ciphertext, $staff->fresh()->password_ciphertext);
    }

    public function test_legacy_unreadable_and_stale_copies_never_reveal_incorrect_passwords(): void
    {
        $admin = $this->user('Administrator', 'legacyadmin');
        $legacy = User::create([
            'name' => 'Legacy staff', 'username' => 'legacy', 'role' => 'Nurse', 'is_active' => true,
            'password' => Hash::make('LegacyPassword1!'),
        ]);
        $url = '/users/'.$legacy->id.'/password/reveal';
        $this->actingAs($admin)->postJson($url)->assertStatus(409)->assertJsonMissing(['password']);

        DB::table('users')->where('id', $legacy->id)->update(['password_ciphertext' => 'invalid-ciphertext']);
        $this->postJson($url)->assertStatus(503)->assertJsonMissing(['password']);

        DB::table('users')->where('id', $legacy->id)->update([
            'password_ciphertext' => Crypt::encryptString('DifferentPassword1!'),
        ]);
        $this->postJson($url)->assertStatus(409)->assertJsonMissing(['password']);

        $legacy->refresh()->update(['password' => 'RestoredPassword1!']);
        $this->postJson($url)->assertOk()->assertExactJson(['password' => 'RestoredPassword1!']);
    }

    public function test_hash_upgrades_preserve_matching_encrypted_copies_and_clear_stale_ones(): void
    {
        $staff = $this->user('Nurse', 'hashupgrade');
        $ciphertext = $staff->password_ciphertext;
        $staff->update(['password' => Hash::make('OriginalClinic1!')]);
        $this->assertSame($ciphertext, $staff->fresh()->password_ciphertext);
        $staff->update(['password' => Hash::make('DifferentPassword1!')]);
        $this->assertNull($staff->fresh()->password_ciphertext);
    }

    public function test_password_reveals_are_rate_limited(): void
    {
        $admin = $this->user('Administrator', 'rateadmin');
        $staff = $this->user('Nurse', 'ratestaff');
        $this->actingAs($admin);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/users/'.$staff->id.'/password/reveal')->assertOk();
        }
        $this->postJson('/users/'.$staff->id.'/password/reveal')->assertStatus(429);
    }

    public function test_form_passwords_that_resemble_hashes_are_still_hashed_and_saved_correctly(): void
    {
        $admin = $this->user('Administrator', 'hashtextadmin');
        $staff = $this->user('Nurse', 'hashtextstaff');
        $password = Hash::make('Text that happens to resemble a hash');
        $this->actingAs($staff)->put('/profile', [
            'name' => $staff->name, 'current_password' => 'OriginalClinic1!',
            'password' => $password, 'password_confirmation' => $password,
        ])->assertRedirect('/profile');
        $this->assertTrue(Hash::check($password, $staff->fresh()->password));
        $this->assertSame($password, Crypt::decryptString($staff->fresh()->password_ciphertext));

        $this->actingAs($admin)->put('/users/'.$staff->id, [
            'name' => $staff->name, 'username' => $staff->username, 'role' => 'Nurse', 'is_active' => 1,
            'password' => $password, 'password_confirmation' => $password,
        ])->assertRedirect('/users');
        $this->assertTrue(Hash::check($password, $staff->fresh()->password));
        $this->postJson('/users/'.$staff->id.'/password/reveal')
            ->assertOk()->assertExactJson(['password' => $password]);
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => $username, 'username' => $username, 'role' => $role,
            'is_active' => true, 'password' => 'OriginalClinic1!',
        ]);
    }
}
