<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_from_every_staff_role_is_logged_and_only_admin_can_view_logs(): void
    {
        $accounts = collect(['Receptionist', 'Nurse', 'Physician', 'Administrator'])
            ->mapWithKeys(fn ($role, $index) => [$role => $this->user($role, 'activity'.$index)]);

        foreach ($accounts as $role => $account) {
            $this->actingAs($account)->get(route('dashboard'))->assertOk();
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $account->id,
                'actor_role' => $role,
                'action' => 'view',
                'route_name' => 'dashboard',
            ]);
        }

        foreach ($accounts->except('Administrator') as $account) {
            $this->actingAs($account)->get(route('activity-logs.index'))->assertForbidden();
        }

        $administrator = $accounts['Administrator'];
        $this->actingAs($administrator)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('Privacy-protected audit trail')
            ->assertSee('Staff member')
            ->assertSee('Activity');
    }

    public function test_successful_login_logout_and_filtered_user_activity_are_recorded_without_secrets(): void
    {
        $administrator = $this->user('Administrator', 'log-admin', 'AdminSecret123!');
        $nurse = $this->user('Nurse', 'log-nurse', 'NurseSecret123!');

        $this->post(route('login.attempt'), [
            'username' => $nurse->username,
            'password' => 'NurseSecret123!',
        ])->assertRedirect(route('dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $nurse->id,
            'action' => 'login',
            'route_name' => 'login.attempt',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $nurse->id,
            'action' => 'logout',
            'route_name' => 'logout',
        ]);

        $storedLogText = ActivityLog::where('user_id', $nurse->id)
            ->get()
            ->map(fn ($log) => implode(' ', $log->getAttributes()))
            ->implode(' ');
        $this->assertStringNotContainsString('NurseSecret123!', $storedLogText);

        $this->actingAs($administrator)->get(route('dashboard'))->assertOk();
        $response = $this->actingAs($administrator)->get(route('activity-logs.index', ['user' => $nurse->id]));
        $response->assertOk();
        $this->assertTrue($response->viewData('logs')->getCollection()->every(
            fn (ActivityLog $log) => $log->user_id === $nurse->id
        ));
    }

    public function test_activity_snapshot_remains_when_an_unused_staff_account_is_deleted(): void
    {
        $administrator = $this->user('Administrator', 'snapshot-admin');
        $receptionist = $this->user('Receptionist', 'snapshot-reception');

        $this->actingAs($receptionist)->get(route('dashboard'))->assertOk();
        $logId = ActivityLog::where('user_id', $receptionist->id)->value('id');

        $this->actingAs($administrator)->delete(route('users.destroy', $receptionist))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('activity_logs', [
            'id' => $logId,
            'user_id' => null,
            'actor_username' => 'snapshot-reception',
            'actor_role' => 'Receptionist',
        ]);
    }

    private function user(string $role, string $username, string $password = 'password'): User
    {
        return User::create([
            'name' => $role.' Activity User',
            'username' => $username,
            'email' => $username.'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make($password),
        ]);
    }
}
