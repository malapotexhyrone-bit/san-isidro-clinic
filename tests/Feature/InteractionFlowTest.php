<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\PhysicianSchedule;
use App\Models\User;
use App\Models\Visit;
use App\Models\VitalSign;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class InteractionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_can_save_incomplete_notes_and_resume_without_completing_the_visit(): void
    {
        $clinic = $this->clinic();
        $visit = $clinic['visit'];
        $this->actingAs($clinic['doctor'])->from('/visits/'.$visit->id)->post('/visits/'.$visit->id.'/consultation', [
            'action' => 'draft', 'chief_complaint' => 'Sample complaint', 'assessment' => '', 'notes' => 'Unfinished sample notes',
        ])->assertSessionHasNoErrors()->assertSessionHas('success', 'Consultation draft saved.');
        $this->assertDatabaseHas('consultations', ['visit_id' => $visit->id, 'chief_complaint' => 'Sample complaint', 'assessment' => '', 'notes' => 'Unfinished sample notes']);
        $this->assertSame('In Consultation', $visit->fresh()->status);
        $this->assertNull($visit->fresh()->completed_at);
        $this->assertSame('Arrived', $clinic['appointment']->fresh()->status);
        $this->get('/visits/'.$visit->id)->assertOk()->assertSee('Unfinished sample notes')
            ->assertSee('id="success-modal"', false)->assertSee('data-validation-mode="draft"', false);
        $this->get('/visits')->assertOk()->assertSee('Resume draft')->assertDontSee('id="success-modal"', false);
    }

    public function test_blank_drafts_and_partial_updates_preserve_existing_text(): void
    {
        $clinic = $this->clinic();
        $url = '/visits/'.$clinic['visit']->id.'/consultation';
        $this->actingAs($clinic['doctor'])->post($url, ['action' => 'draft', 'chief_complaint' => '', 'assessment' => ''])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('consultations', ['visit_id' => $clinic['visit']->id, 'chief_complaint' => '', 'assessment' => '']);
        $this->post($url, ['action' => 'draft', 'chief_complaint' => 'Existing complaint', 'assessment' => 'Existing draft assessment'])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'draft', 'notes' => 'Added notes'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('consultations', ['visit_id' => $clinic['visit']->id, 'chief_complaint' => 'Existing complaint', 'assessment' => 'Existing draft assessment', 'notes' => 'Added notes']);
        $this->assertDatabaseCount('consultations', 1);
    }

    public function test_completing_requires_the_clinical_fields_and_locks_completed_records(): void
    {
        $clinic = $this->clinic();
        $url = '/visits/'.$clinic['visit']->id.'/consultation';
        $this->actingAs($clinic['doctor'])->from('/visits/'.$clinic['visit']->id)->post($url, ['action' => 'complete', 'chief_complaint' => '', 'assessment' => ''])
            ->assertSessionHasErrors(['chief_complaint', 'assessment'])->assertSessionMissing('success');
        $this->assertDatabaseCount('consultations', 0);
        $this->post($url, ['action' => 'complete', 'chief_complaint' => 'Sample complaint', 'assessment' => 'Sample assessment', 'notes' => 'Sample notes'])
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'Visit completed.');
        $this->assertSame('Completed', $clinic['visit']->fresh()->status);
        $this->assertNotNull($clinic['visit']->fresh()->completed_at);
        $this->assertSame('Completed', $clinic['appointment']->fresh()->status);
        $this->get('/visits/'.$clinic['visit']->id)->assertOk()->assertSee('Read only')->assertSee('Sample notes')
            ->assertDontSee('Save draft')->assertDontSee('id="consultation-form"', false);
        $this->get('/visits')->assertOk()->assertSee('View completed record');
        $this->post($url, ['action' => 'draft', 'notes' => 'Attempted edit'])->assertStatus(422);
        $this->assertDatabaseHas('consultations', ['visit_id' => $clinic['visit']->id, 'notes' => 'Sample notes']);
    }

    public function test_draft_keeps_length_limits_role_ownership_and_vital_sign_checks(): void
    {
        $clinic = $this->clinic();
        $url = '/visits/'.$clinic['visit']->id.'/consultation';
        $this->actingAs($clinic['doctor'])->post($url, ['action' => 'draft', 'notes' => str_repeat('x', 5001)])
            ->assertSessionHasErrors('notes')->assertSessionMissing('success');
        $this->post($url, ['action' => 'unknown'])->assertSessionHasErrors('action');
        $this->actingAs($clinic['nurse'])->post($url, ['action' => 'draft'])->assertForbidden();
        $other = $this->user('Physician', 'otherdoctor');
        Physician::create(['name' => 'Other physician', 'specialization' => 'Family Medicine', 'user_id' => $other->id, 'is_active' => true]);
        $this->actingAs($other)->post($url, ['action' => 'draft'])->assertForbidden();
        $clinic['visit']->vitalSign()->delete();
        $this->actingAs($clinic['doctor'])->post($url, ['action' => 'draft'])->assertSessionHasErrors('vital_signs');
        $this->assertDatabaseCount('consultations', 0);
    }

    public function test_all_roles_receive_a_one_time_centered_success_with_a_queue_link(): void
    {
        $clinic = $this->clinic();
        foreach (['doctor', 'nurse', 'reception', 'admin'] as $role) {
            $this->actingAs($clinic[$role])->put('/profile', ['name' => $clinic[$role]->name])->assertSessionHasNoErrors();
            session()->flash('warning', 'Sample delivery notice.');
            $this->get('/visits')->assertOk()->assertSee('id="success-modal"', false)
                ->assertSee('id="success-continue"', false)->assertSee('href="'.route('visits.index').'"', false)
                ->assertSee('Your profile was updated successfully.')->assertSee('Please note')->assertSee('Sample delivery notice.')
                ->assertDontSee('class="alert alert-success"', false);
            $this->get('/visits')->assertOk()->assertDontSee('id="success-modal"', false);
        }
    }

    public function test_missing_action_retains_a_clear_summary_instead_of_attaching_to_a_button(): void
    {
        $clinic = $this->clinic();
        $this->actingAs($clinic['doctor'])->from('/visits/'.$clinic['visit']->id)
            ->post('/visits/'.$clinic['visit']->id.'/consultation', ['chief_complaint' => 'Sample'])
            ->assertSessionHasErrors('action');
        $this->get('/visits/'.$clinic['visit']->id)->assertOk()->assertSee('Choose Save draft or Complete consultation.')
            ->assertDontSee('id="success-modal"', false);
    }

    public function test_visible_form_actions_and_internal_links_match_registered_routes_for_every_role(): void
    {
        $clinic = $this->clinic();
        $patient = $clinic['patient']->id;
        $physician = $clinic['physician']->id;
        $visit = $clinic['visit']->id;
        $appointment = $clinic['appointment']->id;
        $staff = $clinic['nurse']->id;
        $pages = [
            'admin' => ['/dashboard','/patients','/patients-create',"/patients/$patient", "/patients/$patient/edit",'/appointments','/appointments/create',"/appointments/$appointment",'/walk-ins/create','/visits',"/visits/$visit",'/schedules','/physicians','/physicians/create',"/physicians/$physician/edit",'/users','/users/create',"/users/$staff", "/users/$staff/edit",'/reports','/profile','/profile/edit'],
            'reception' => ['/dashboard','/patients','/patients-create',"/patients/$patient", "/patients/$patient/edit",'/appointments','/appointments/create',"/appointments/$appointment",'/walk-ins/create','/visits',"/visits/$visit",'/schedules','/profile','/profile/edit'],
            'doctor' => ['/dashboard','/patients',"/patients/$patient",'/appointments','/visits',"/visits/$visit",'/schedules','/profile','/profile/edit'],
            'nurse' => ['/dashboard','/patients',"/patients/$patient",'/visits',"/visits/$visit",'/schedules','/profile','/profile/edit'],
        ];
        foreach ($pages as $role => $urls) {
            $this->actingAs($clinic[$role]);
            foreach ($urls as $url) {
                $response = $this->get($url)->assertOk();
                $dom = new DOMDocument;
                $previous = libxml_use_internal_errors(true);
                $dom->loadHTML($response->getContent());
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                $xpath = new DOMXPath($dom);
                foreach ($xpath->query('//form') as $form) {
                    $action = $form->getAttribute('action') ?: $url;
                    $method = strtoupper($form->getAttribute('method') ?: 'GET');
                    foreach ($xpath->query('.//input[@name="_method"]', $form) as $override) $method = strtoupper($override->getAttribute('value'));
                    $route = app('router')->getRoutes()->match(Request::create($action, $method));
                    $this->assertContains($method, $route->methods(), "$role $url form $action");
                }
                foreach ($xpath->query('//a[@href]') as $link) {
                    $href = $link->getAttribute('href');
                    if (!$href || str_starts_with($href, '#') || preg_match('#^(mailto:|tel:|javascript:)#', $href)) continue;
                    $host = parse_url($href, PHP_URL_HOST);
                    if ($host && $host !== parse_url(url('/'), PHP_URL_HOST)) continue;
                    $route = app('router')->getRoutes()->match(Request::create($href, 'GET'));
                    $this->assertContains('GET', $route->methods(), "$role $url link $href");
                }
            }
        }
    }

    public function test_administrative_add_update_and_deactivate_actions_persist_their_changes(): void
    {
        $clinic = $this->clinic();
        $this->actingAs($clinic['admin']);
        $this->post('/physicians', ['name' => 'Added physician', 'specialization' => 'General Medicine', 'is_active' => 1])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $physician = Physician::where('name', 'Added physician')->firstOrFail();
        $this->put('/physicians/'.$physician->id, ['name' => 'Updated physician', 'specialization' => 'Family Medicine', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame('Updated physician', $physician->fresh()->name);
        $this->patch('/physicians/'.$physician->id.'/deactivate')->assertSessionHasNoErrors();
        $this->assertFalse((bool)$physician->fresh()->is_active);
        $this->post('/users', ['name' => 'Added staff', 'username' => 'addedstaff', 'role' => 'Nurse', 'is_active' => 1, 'password' => 'SamplePassword1!', 'password_confirmation' => 'SamplePassword1!'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $staff = User::where('username', 'addedstaff')->firstOrFail();
        $this->put('/users/'.$staff->id, ['name' => 'Updated staff', 'username' => 'addedstaff', 'role' => 'Nurse', 'is_active' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('Updated staff', $staff->fresh()->name);
        $this->patch('/users/'.$staff->id.'/deactivate')->assertSessionHasNoErrors();
        $this->assertFalse((bool)$staff->fresh()->is_active);
        $this->patch('/users/'.$clinic['admin']->id.'/deactivate')->assertStatus(422);
    }

    public function test_schedule_save_edit_remove_and_report_filters_work(): void
    {
        $clinic = $this->clinic();
        $this->actingAs($clinic['admin']);
        $data = ['physician_id' => $clinic['physician']->id, 'day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '11:00', 'slot_duration' => 60, 'is_available' => 1];
        $this->post('/schedules', $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $schedule = PhysicianSchedule::where('day_of_week', 3)->firstOrFail();
        $this->put('/schedules/'.$schedule->id, array_merge($data, ['end_time' => '12:00']))->assertSessionHasNoErrors();
        $this->assertSame('12:00', substr($schedule->fresh()->end_time, 0, 5));
        $this->delete('/schedules/'.$schedule->id)->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('physician_schedules', ['id' => $schedule->id]);
        $this->get('/reports?from='.today()->toDateString().'&to='.today()->toDateString())->assertOk()->assertSee('Print report');
        $this->get('/patients?search=Sample')->assertOk()->assertSee('Maria Sample');
        $this->get('/visits?date='.today()->toDateString())->assertOk()->assertSee('Maria Sample');
    }

    public function test_patient_save_edit_and_logout_actions_work(): void
    {
        $clinic = $this->clinic();
        $data = ['first_name' => 'New', 'last_name' => 'Sample', 'birth_date' => '1990-01-01', 'sex' => 'Female', 'contact_number' => '09170000000', 'email' => 'new@example.test', 'address' => 'Sample address'];
        $this->actingAs($clinic['reception'])->post('/patients', $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $patient = Patient::where('email', 'new@example.test')->firstOrFail();
        $this->put('/patients/'.$patient->id, array_merge($data, ['address' => 'Updated sample address']))->assertSessionHasNoErrors();
        $this->assertSame('Updated sample address', $patient->fresh()->address);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    private function clinic(): array
    {
        $admin = $this->user('Administrator', 'sampleadmin');
        $reception = $this->user('Receptionist', 'samplereception');
        $doctor = $this->user('Physician', 'sampledoctor');
        $nurse = $this->user('Nurse', 'samplenurse');
        $patient = Patient::create(['patient_number' => 'P-SAMPLE', 'first_name' => 'Maria', 'last_name' => 'Sample', 'birth_date' => '1990-01-01', 'sex' => 'Female', 'contact_number' => '09170000000', 'email' => 'maria@example.test', 'address' => 'Sample address']);
        $physician = Physician::create(['user_id' => $doctor->id, 'name' => 'Sample physician', 'specialization' => 'Family Medicine', 'is_active' => true]);
        PhysicianSchedule::create(['physician_id' => $physician->id, 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '17:00', 'slot_duration' => 30, 'is_available' => true]);
        $appointment = Appointment::create(['appointment_number' => 'APT-SAMPLE', 'patient_id' => $patient->id, 'physician_id' => $physician->id, 'appointment_date' => today()->addDay(), 'appointment_time' => '09:00', 'reason' => 'Sample appointment', 'status' => 'Arrived', 'verification_code' => 'SIC-SAMPLE', 'verified_at' => now(), 'created_by' => $reception->id]);
        $visit = Visit::create(['visit_number' => 'VIS-SAMPLE', 'patient_id' => $patient->id, 'physician_id' => $physician->id, 'appointment_id' => $appointment->id, 'arrived_at' => now(), 'visit_type' => 'Scheduled', 'reason' => 'Sample visit', 'status' => 'Vital Signs Recorded', 'created_by' => $reception->id]);
        VitalSign::create(['visit_id' => $visit->id, 'blood_pressure' => '120/80', 'temperature' => 36.7, 'pulse' => 76, 'respiratory_rate' => 18, 'weight' => 58.5, 'height' => 160, 'recorded_by' => $nurse->id]);
        return compact('admin','reception','doctor','nurse','patient','physician','visit','appointment');
    }

    private function user(string $role, string $username): User
    {
        return User::create(['name' => $username, 'username' => $username, 'role' => $role, 'is_active' => true, 'password' => 'SamplePassword1!']);
    }
}
