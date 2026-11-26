<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_administrator_can_delete_a_patient_without_clinical_history(): void
    {
        $administrator = $this->user('Administrator', 'delete-admin');
        $receptionist = $this->user('Receptionist', 'delete-reception');
        $patient = $this->patient('P-DELETE-1');

        $this->actingAs($receptionist)->delete(route('patients.destroy', $patient))->assertForbidden();
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);

        $this->actingAs($administrator)->get(route('patients.show', $patient))
            ->assertOk()
            ->assertSee('Delete patient')
            ->assertSee('action="'.route('patients.destroy', $patient).'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('value="DELETE"', false);

        $this->actingAs($administrator)->delete(route('patients.destroy', $patient))
            ->assertRedirect(route('patients.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    public function test_patient_with_appointment_history_is_protected_from_deletion(): void
    {
        $administrator = $this->user('Administrator', 'history-admin');
        $receptionist = $this->user('Receptionist', 'history-reception');
        $patient = $this->patient('P-HISTORY');
        $physician = Physician::create([
            'name' => 'Dr. History',
            'specialization' => 'Family Medicine',
            'is_active' => true,
        ]);
        Appointment::create([
            'appointment_number' => 'APT-HISTORY',
            'patient_id' => $patient->id,
            'physician_id' => $physician->id,
            'appointment_date' => today()->addDay(),
            'appointment_time' => '09:00',
            'reason' => 'Protected history',
            'status' => 'Scheduled',
            'created_by' => $receptionist->id,
        ]);

        $this->actingAs($administrator)->delete(route('patients.destroy', $patient))
            ->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
        $this->assertDatabaseHas('appointments', ['patient_id' => $patient->id]);
    }

    public function test_administrator_can_delete_an_unused_staff_account_but_not_self_or_audited_staff(): void
    {
        $administrator = $this->user('Administrator', 'staff-delete-admin');
        $unusedNurse = $this->user('Nurse', 'unused-nurse');

        $this->actingAs($administrator)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Delete account')
            ->assertSee('action="'.route('users.destroy', $unusedNurse).'"', false);

        $this->actingAs($administrator)->delete(route('users.destroy', $unusedNurse))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $unusedNurse->id]);

        $this->actingAs($administrator)->delete(route('users.destroy', $administrator))
            ->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $administrator->id]);

        $auditedReceptionist = $this->user('Receptionist', 'audited-reception');
        $patient = $this->patient('P-AUDIT');
        $physician = Physician::create([
            'name' => 'Dr. Audit',
            'specialization' => 'General Practice',
            'is_active' => true,
        ]);
        Appointment::create([
            'appointment_number' => 'APT-AUDIT',
            'patient_id' => $patient->id,
            'physician_id' => $physician->id,
            'appointment_date' => today()->addDay(),
            'appointment_time' => '10:00',
            'reason' => 'Audit preservation',
            'status' => 'Scheduled',
            'created_by' => $auditedReceptionist->id,
        ]);

        $this->actingAs($administrator)->delete(route('users.destroy', $auditedReceptionist))
            ->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $auditedReceptionist->id]);
    }

    private function user(string $role, string $username): User
    {
        return User::create([
            'name' => str_replace('-', ' ', ucfirst($username)),
            'username' => $username,
            'email' => $username.'@example.test',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function patient(string $number): Patient
    {
        return Patient::create([
            'patient_number' => $number,
            'first_name' => 'Delete',
            'middle_name' => null,
            'last_name' => 'Candidate',
            'birth_date' => '1990-01-01',
            'sex' => 'Female',
            'contact_number' => '09170000000',
            'email' => strtolower($number).'@example.test',
            'address' => 'San Isidro',
        ]);
    }
}
