<?php

namespace Tests\Feature;

use App\Mail\AppointmentConfirmation;
use App\Models\{Appointment,Patient,Physician,PhysicianSchedule,User,Visit};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ClinicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_role_middleware(): void
    {
        $receptionist = $this->user('Receptionist','frontdesk');
        $this->get('/')
            ->assertOk()
            ->assertSee('<select id="username"',false)
            ->assertSee('value="frontdesk"',false);
        $this->post('/login',['username'=>'frontdesk','password'=>'incorrect'])->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->post('/login',['username'=>'frontdesk','password'=>'password'])->assertRedirect('/dashboard');
        $this->actingAs($receptionist)->get('/users')->assertForbidden();
        $this->actingAs($this->user('Administrator','admin'))->get('/users')->assertOk();
    }

    public function test_secondary_interfaces_have_clear_return_buttons_to_their_section_landing_pages(): void
    {
        [$receptionist,$patient,$physician,$date] = $this->clinicSetup();
        $administrator = $this->user('Administrator','return-admin');
        $appointment = $this->appointment($receptionist,$patient,$physician,$date, [
            'appointment_number' => 'APT-RETURN',
            'verification_code' => 'SIC-RETURN',
        ]);
        $visit = Visit::create([
            'visit_number' => 'VIS-RETURN',
            'patient_id' => $patient->id,
            'physician_id' => $physician->id,
            'appointment_id' => $appointment->id,
            'arrived_at' => now(),
            'visit_type' => 'Scheduled',
            'reason' => 'Return navigation check',
            'status' => 'Waiting',
            'created_by' => $receptionist->id,
        ]);

        foreach ([
            ['/patients-create', 'Return to patients', route('patients.index')],
            ["/patients/{$patient->id}", 'Return to patients', route('patients.index')],
            ['/appointments/create', 'Return to appointments', route('appointments.index')],
            ["/appointments/{$appointment->id}", 'Return to appointments', route('appointments.index')],
            ['/walk-ins/create', 'Return to appointments', route('appointments.index')],
            ["/visits/{$visit->id}", 'Return to patient visits', route('visits.index')],
            ['/profile/edit', 'Return to profile', route('profile.show')],
        ] as [$url,$label,$destination]) {
            $this->actingAs($receptionist)->get($url)
                ->assertOk()
                ->assertSee($label)
                ->assertSee('href="'.$destination.'"', false);
        }

        foreach ([
            ['/physicians/create', 'Return to physician records', route('physicians.index')],
            ["/physicians/{$physician->id}/edit", 'Return to physician records', route('physicians.index')],
            ['/users/create', 'Return to user management', route('users.index')],
            ["/users/{$administrator->id}/edit", 'Return to user management', route('users.index')],
        ] as [$url,$label,$destination]) {
            $this->actingAs($administrator)->get($url)
                ->assertOk()
                ->assertSee($label)
                ->assertSee('href="'.$destination.'"', false);
        }
    }

    public function test_every_staff_role_can_view_and_update_its_own_profile(): void
    {
        foreach (['Receptionist', 'Nurse', 'Physician', 'Administrator'] as $index => $role) {
            $user = $this->user($role, 'profile'.$index);
            $originalPassword = $user->password;

            $this->actingAs($user)->get('/profile')
                ->assertOk()
                ->assertSee($user->name)
                ->assertSee($role)
                ->assertSee('Password security');

            $this->actingAs($user)->put('/profile', [
                'name' => $role.' Staff Member',
                'email' => 'profile'.$index.'@example.test',
                'birthdate' => '1995-05-15',
                'address' => 'San Isidro, Nueva Ecija',
            ])->assertRedirect('/profile');

            $user->refresh();
            $this->assertSame($role.' Staff Member', $user->name);
            $this->assertSame('San Isidro, Nueva Ecija', $user->address);
            $this->assertSame($originalPassword, $user->password);
        }
    }

    public function test_profile_password_change_requires_the_current_password(): void
    {
        $user = $this->user('Receptionist', 'secureprofile');

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'current_password' => 'wrong-password',
            'password' => 'NewClinic123!',
            'password_confirmation' => 'NewClinic123!',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'current_password' => 'password',
            'password' => 'NewClinic123!',
            'password_confirmation' => 'NewClinic123!',
        ])->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewClinic123!', $user->fresh()->password));
    }

    public function test_administrator_can_view_staff_profile_pages(): void
    {
        $admin = $this->user('Administrator', 'profileadmin');
        $nurse = $this->user('Nurse', 'profilenurse');

        $this->actingAs($admin)->get("/users/{$nurse->id}")
            ->assertOk()
            ->assertSee($nurse->name)
            ->assertSee('Edit staff profile');

        $this->actingAs($nurse)->get("/users/{$admin->id}")->assertForbidden();
    }

    public function test_both_physician_profiles_have_four_theme_and_hover_images(): void
    {
        $angela = $this->user('Physician', 'physician2');
        $rafael = $this->user('Physician', 'physician');

        $this->actingAs($angela)->get('/profile')
            ->assertOk()
            ->assertSee('doctor2-formal-light.jpeg', false)
            ->assertSee('doctor2-shy-light.jpeg', false)
            ->assertSee('doctor2-formal-dark.jpeg', false)
            ->assertSee('doctor2-shy-dark.jpeg', false);

        $this->actingAs($angela)->get('/dashboard')
            ->assertOk()
            ->assertSee('doctor2-formal-light.jpeg', false)
            ->assertSee('doctor2-shy-dark.jpeg', false);

        $this->actingAs($rafael)->get('/profile')
            ->assertOk()
            ->assertSee('doctor1-formal-light.jpeg', false)
            ->assertSee('doctor1-shy-light.jpeg', false)
            ->assertSee('doctor1-formal-dark.jpeg', false)
            ->assertSee('doctor1-shy-dark.jpeg', false);

        $this->actingAs($rafael)->get('/dashboard')
            ->assertOk()
            ->assertSee('doctor1-formal-light.jpeg', false)
            ->assertSee('doctor1-shy-dark.jpeg', false);
    }

    public function test_patient_email_is_required_unique_and_unchanged_email_is_accepted_on_update(): void
    {
        $staff = $this->user('Receptionist','reception');
        $patient = Patient::create($this->patientData(['patient_number'=>'P-TEST']));

        $this->actingAs($staff)->put("/patients/{$patient->id}", $this->patientData())
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('patients', ['id'=>$patient->id, 'email'=>'maria@example.test']);

        $duplicate = $this->patientData([
            'first_name'=>'Jose', 'last_name'=>'Cruz', 'email'=>'maria@example.test',
        ]);
        $this->actingAs($staff)->post('/patients', $duplicate)->assertSessionHasErrors('email');

        $missing = $this->patientData(['email'=>null]);
        $this->actingAs($staff)->post('/patients', $missing)->assertSessionHasErrors('email');
    }

    public function test_appointment_form_autofills_a_readonly_patient_email(): void
    {
        [$staff,$patient] = $this->clinicSetup();

        $this->actingAs($staff)->get("/appointments/create?patient={$patient->id}")
            ->assertOk()
            ->assertSee('data-email="maria@example.test"', false)
            ->assertSee('id="notification-email"', false)
            ->assertSee('id="booking-patient-heading"', false)
            ->assertSee('id="booking-date-heading"', false)
            ->assertSee('id="booking-time-heading"', false)
            ->assertSee('id="availability-picker"', false)
            ->assertSee(route('appointments.availability'), false)
            ->assertSee('readonly', false);
    }

    public function test_availability_picker_disables_booked_slots_and_returns_open_times(): void
    {
        [$staff,$patient,$physician,$date] = $this->clinicSetup();
        $this->appointment($staff,$patient,$physician,$date,[
            'appointment_number'=>'APT-BOOKED-SLOT',
            'appointment_time'=>'09:00',
            'verification_code'=>'SIC-BKD001',
        ]);

        $response = $this->actingAs($staff)->getJson('/appointments/availability?'.http_build_query([
            'physician_id'=>$physician->id,
            'month'=>Carbon::parse($date)->format('Y-m'),
        ]));

        $response->assertOk();
        $day = $response->json("dates.{$date}");
        $this->assertSame(17,$day['open_slots']);
        $this->assertFalse(collect($day['times'])->firstWhere('time','09:00')['available']);
        $this->assertTrue(collect($day['times'])->firstWhere('time','09:30')['available']);
    }

    public function test_elapsed_times_cannot_be_booked_even_when_the_physician_has_a_schedule(): void
    {
        [$staff,$patient,$physician] = $this->clinicSetup();
        PhysicianSchedule::updateOrCreate(
            ['physician_id'=>$physician->id,'day_of_week'=>now()->dayOfWeek,'start_time'=>'00:00'],
            ['end_time'=>'23:59','slot_duration'=>30,'is_available'=>true]
        );

        $this->actingAs($staff)->post('/appointments',[
            'patient_id'=>$patient->id,
            'physician_id'=>$physician->id,
            'appointment_date'=>now()->toDateString(),
            'appointment_time'=>'00:00',
            'reason'=>'Consultation',
        ])->assertSessionHasErrors('appointment_time');
        $this->assertDatabaseCount('appointments',0);
    }

    public function test_save_and_high_impact_actions_include_confirmation_alerts(): void
    {
        [$staff,$patient,$physician,$date] = $this->clinicSetup();
        $appointment = $this->appointment($staff,$patient,$physician,$date, [
            'verification_code'=>'SIC-ALT001',
            'notification_email'=>$patient->email,
            'verified_at'=>now(),
        ]);

        $this->actingAs($staff)->get('/appointments/create')
            ->assertOk()
            ->assertSee('Create this appointment and send the confirmation email', false)
            ->assertSee('Are you sure you want to log out', false)
            ->assertSee('id="confirmation-modal"', false)
            ->assertSee('Confirm action', false)
            ->assertSee('id="confirmation-cancel"', false)
            ->assertSee('id="confirmation-continue"', false)
            ->assertDontSee('window.confirm', false)
            ->assertSee('buttonLabel', false);

        $this->actingAs($staff)->get("/patients/{$patient->id}/edit")
            ->assertSee('Save this patient information?', false);

        $this->actingAs($staff)->get("/appointments/{$appointment->id}")
            ->assertSee('Verify this code for', false)
            ->assertSee('Reschedule this appointment', false)
            ->assertSee('Send another confirmation email', false)
            ->assertSee('Cancel appointment', false)
            ->assertSee('Mark '.$patient->full_name.' as Arrived', false)
            ->assertSee('Mark '.$patient->full_name.' as No-show', false);
    }

    public function test_appointment_schedule_is_separated_by_period_and_physician(): void
    {
        [$staff,$patient,$physician] = $this->clinicSetup();
        $administrator = $this->user('Administrator','schedule-admin');
        PhysicianSchedule::updateOrCreate(
            ['physician_id'=>$physician->id,'day_of_week'=>now()->dayOfWeek,'start_time'=>'08:00'],
            ['end_time'=>'17:00','slot_duration'=>30,'is_available'=>true]
        );
        $secondPhysician = Physician::create(['name'=>'Dr. Angela Lim','specialization'=>'General Practice','is_active'=>true]);
        PhysicianSchedule::create(['physician_id'=>$secondPhysician->id,'day_of_week'=>now()->dayOfWeek,'start_time'=>'08:00','end_time'=>'17:00','slot_duration'=>30,'is_available'=>true]);

        $this->appointment($staff,$patient,$physician,now()->subDay()->toDateString(),[
            'appointment_number'=>'APT-EARLIER','verification_code'=>'SIC-EAR001',
        ]);
        $this->appointment($staff,$patient,$physician,now()->toDateString(),[
            'appointment_number'=>'APT-CURRENT','verification_code'=>'SIC-CUR001',
        ]);
        $this->appointment($staff,$patient,$secondPhysician,now()->addDay()->toDateString(),[
            'appointment_number'=>'APT-UPCOMING','verification_code'=>'SIC-UPC001',
        ]);
        $this->appointment($staff,$patient,$physician,now()->subDays(2)->toDateString(),[
            'appointment_number'=>'APT-COMPLETED','verification_code'=>'SIC-DONE01','status'=>'Completed',
        ]);
        $this->appointment($staff,$patient,$secondPhysician,now()->subDays(3)->toDateString(),[
            'appointment_number'=>'APT-SECOND-COMPLETED','verification_code'=>'SIC-DONE02','status'=>'Completed',
        ]);

        $this->actingAs($administrator)->get('/appointments')
            ->assertOk()
            ->assertSee('Clinic Day')
            ->assertSee('APT-CURRENT')
            ->assertSee('Dr. Reyes')
            ->assertSee('Dr. Angela Lim')
            ->assertSee('Available')
            ->assertDontSee('APT-EARLIER')
            ->assertDontSee('APT-UPCOMING');

        $this->actingAs($administrator)->get('/appointments?period=needs-review')
            ->assertOk()
            ->assertSee('Needs Review')
            ->assertSee('Choose a physician')
            ->assertSee('reservation need action')
            ->assertSee('Dr. Angela Lim')
            ->assertSee('No reservations need action')
            ->assertSee('review-disabled-button', false)
            ->assertSee('disabled', false)
            ->assertSee('View reservations')
            ->assertDontSee('APT-EARLIER');

        $this->actingAs($administrator)->get('/appointments?'.http_build_query(['period'=>'needs-review','review_physician'=>$secondPhysician->id]))
            ->assertOk()
            ->assertSee('Choose a physician')
            ->assertSee('No reservations need action')
            ->assertDontSee('APT-SECOND-COMPLETED');

        $this->actingAs($administrator)->get('/appointments?'.http_build_query(['period'=>'needs-review','review_physician'=>$physician->id]))
            ->assertOk()
            ->assertSee('Unprocessed appointment review')
            ->assertSee('appointment need action')
            ->assertSee('Return to physicians')
            ->assertDontSee('New appointment')
            ->assertDontSee('Record walk-in')
            ->assertDontSee('Clinic Day')
            ->assertDontSee('Upcoming Schedule')
            ->assertSee('APT-EARLIER')
            ->assertSee('class="appointment-patient-name"', false)
            ->assertDontSee('class="btn btn-sm" href="'.route('patients.show', $patient).'"', false)
            ->assertSee('Needs action')
            ->assertDontSee('>Scheduled<',false)
            ->assertSee('APT-COMPLETED')
            ->assertSee('review-status-success', false)
            ->assertSee('Completed')
            ->assertSee('Reschedule')
            ->assertSee('Mark No-show')
            ->assertDontSee('APT-CURRENT');

        $this->actingAs($administrator)->get('/appointments?period=upcoming')
            ->assertOk()
            ->assertSee('Upcoming Schedule')
            ->assertSee('APT-UPCOMING')
            ->assertSee('Dr. Angela Lim')
            ->assertDontSee('APT-CURRENT');
    }

    public function test_physician_schedules_are_displayed_in_separate_physician_sections(): void
    {
        [$staff, , $physician] = $this->clinicSetup();
        $administrator = $this->user('Administrator','schedule-view-admin');
        $secondPhysician = Physician::create([
            'name'=>'Dr. Angela Lim',
            'specialization'=>'General Practice',
            'is_active'=>true,
        ]);
        PhysicianSchedule::create([
            'physician_id'=>$secondPhysician->id,
            'day_of_week'=>2,
            'start_time'=>'10:00',
            'end_time'=>'15:00',
            'slot_duration'=>30,
            'is_available'=>true,
        ]);

        $this->actingAs($administrator)->get('/schedules')
            ->assertOk()
            ->assertSee('Each physician has a separate weekly schedule')
            ->assertSee('data-physician-schedule="'.$physician->id.'"', false)
            ->assertSee('data-physician-schedule="'.$secondPhysician->id.'"', false)
            ->assertSee('Dr. Reyes')
            ->assertSee('Family Medicine')
            ->assertSee('Dr. Angela Lim')
            ->assertSee('General Practice')
            ->assertSee('Monday')
            ->assertSee('Tuesday')
            ->assertDontSee('<th>Physician</th>', false);
    }

    public function test_receptionist_can_view_open_slots_and_fully_booked_or_unavailable_days_are_disabled(): void
    {
        [$receptionist, $patient, $physician, $date] = $this->clinicSetup();
        $schedule = $physician->schedules()->firstOrFail();
        $schedule->update([
            'slot_duration' => 60,
            'day_of_week' => (now()->dayOfWeek + 1) % 7,
        ]);
        $displayedScheduleDate = now()->startOfDay()
            ->addDays(($schedule->day_of_week - now()->dayOfWeek + 7) % 7)
            ->toDateString();

        $this->actingAs($receptionist)->get('/schedules')
            ->assertOk()
            ->assertSee('data-availability-status="available"', false)
            ->assertSee('data-slot-action="available"', false)
            ->assertSee('data-slot-open', false)
            ->assertSee('View available slots')
            ->assertSee('id="schedule-slot-viewer"', false)
            ->assertSee('Return to schedules')
            ->assertSee('8:00 AM');

        foreach (range(8, 16) as $hour) {
            $this->appointment($receptionist, $patient, $physician, $displayedScheduleDate, [
                'appointment_number' => 'APT-FULL-'.$hour,
                'appointment_time' => sprintf('%02d:00', $hour),
            ]);
        }

        $this->actingAs($receptionist)->get('/schedules')
            ->assertOk()
            ->assertSee('data-availability-status="fully-booked"', false)
            ->assertSee('View available slots · Fully booked')
            ->assertSee('data-slot-action="disabled"', false);

        $schedule->update(['is_available' => false, 'unavailability_reason' => 'Physician leave']);
        $this->actingAs($receptionist)->get('/schedules')
            ->assertOk()
            ->assertSee('data-availability-status="unavailable"', false)
            ->assertSee('View available slots · Unavailable')
            ->assertSee('Physician leave');
    }

    public function test_admin_report_separates_physician_counts_and_shows_a_combined_total(): void
    {
        [$staff,$patient,$firstPhysician,$date] = $this->clinicSetup();
        $secondPhysician = Physician::create([
            'name'=>'Dr. Angela Lim',
            'specialization'=>'General Practice',
            'is_active'=>true,
        ]);
        $this->appointment($staff,$patient,$firstPhysician,$date,[
            'appointment_number'=>'APT-REPORT-1',
            'verification_code'=>'SIC-RPT001',
        ]);
        $this->appointment($staff,$patient,$secondPhysician,$date,[
            'appointment_number'=>'APT-REPORT-2',
            'appointment_time'=>'10:00',
            'verification_code'=>'SIC-RPT002',
        ]);
        $administrator = $this->user('Administrator','report-admin');

        $this->actingAs($administrator)->get('/reports?'.http_build_query(['from'=>$date,'to'=>$date]))
            ->assertOk()
            ->assertSee('data-physician-report="'.$firstPhysician->id.'"',false)
            ->assertSee('data-physician-report="'.$secondPhysician->id.'"',false)
            ->assertSee('Dr. Reyes')
            ->assertSee('Dr. Angela Lim')
            ->assertSee('Combined total for all physicians')
            ->assertSee('data-physician-total="2"',false);
    }

    public function test_one_receptionist_handles_both_physicians_while_physicians_only_see_their_patients(): void
    {
        [$receptionist,$firstPatient,$firstPhysician,$date,$firstPhysicianUser] = $this->clinicSetup();
        $secondPhysicianUser = $this->user('Physician','doctor2');
        $secondPhysician = Physician::create([
            'user_id'=>$secondPhysicianUser->id,
            'name'=>'Dr. Angela Lim',
            'specialization'=>'General Practice',
            'is_active'=>true,
        ]);
        PhysicianSchedule::create([
            'physician_id'=>$secondPhysician->id,
            'day_of_week'=>1,
            'start_time'=>'08:00',
            'end_time'=>'17:00',
            'slot_duration'=>30,
            'is_available'=>true,
        ]);
        $secondPatient = Patient::create($this->patientData([
            'patient_number'=>'P-SECOND',
            'first_name'=>'Ana',
            'last_name'=>'Garcia',
            'email'=>'ana@example.test',
        ]));

        $firstAppointment = $this->appointment($receptionist,$firstPatient,$firstPhysician,$date,[
            'appointment_number'=>'APT-FIRST',
            'verification_code'=>'SIC-FIRST1',
        ]);
        $secondAppointment = $this->appointment($receptionist,$secondPatient,$secondPhysician,$date,[
            'appointment_number'=>'APT-SECOND',
            'verification_code'=>'SIC-SECON2',
        ]);

        $this->actingAs($receptionist)->get('/appointments?period=upcoming')
            ->assertOk()->assertSee('APT-FIRST')->assertSee('APT-SECOND')
            ->assertSee('Dr. Reyes')->assertSee('Dr. Angela Lim');
        $this->actingAs($receptionist)->get("/appointments/{$secondAppointment->id}")->assertOk();
        $this->actingAs($receptionist)->get('/appointments/create')
            ->assertOk()->assertSee('Dr. Reyes')->assertSee('Dr. Angela Lim');
        $this->actingAs($receptionist)->get('/schedules')
            ->assertOk()->assertSee('Dr. Reyes')->assertSee('Dr. Angela Lim');

        $this->actingAs($firstPhysicianUser)->get('/appointments?period=upcoming')
            ->assertOk()->assertSee('My Appointments')->assertSee('APT-FIRST')
            ->assertDontSee('APT-SECOND')->assertDontSee('New appointment');
        $this->actingAs($secondPhysicianUser)->get('/appointments?period=upcoming')
            ->assertOk()->assertSee('My Appointments')->assertSee('APT-SECOND')
            ->assertDontSee('APT-FIRST')->assertDontSee('New appointment');

        $this->actingAs($firstPhysicianUser)->get('/patients')
            ->assertOk()->assertSee($firstPatient->full_name)->assertDontSee($secondPatient->full_name);
        $this->actingAs($secondPhysicianUser)->get('/patients')
            ->assertOk()->assertSee($secondPatient->full_name)->assertDontSee($firstPatient->full_name);
        $this->actingAs($firstPhysicianUser)->get("/patients/{$secondPatient->id}")->assertForbidden();
        $this->actingAs($secondPhysicianUser)->get("/patients/{$firstPatient->id}")->assertForbidden();
    }

    public function test_appointment_conflicts_are_rejected_and_rescheduling_keeps_history(): void
    {
        Mail::fake();
        [$staff,$patient,$physician,$date] = $this->clinicSetup();
        $payload = ['patient_id'=>$patient->id,'physician_id'=>$physician->id,'appointment_date'=>$date,'appointment_time'=>'09:00','reason'=>'Consultation'];

        $this->actingAs($staff)->post('/appointments',$payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('appointments',1);
        $appointment = Appointment::first();
        $this->assertMatchesRegularExpression('/^APT-\d{8}-\d{5}$/', $appointment->appointment_number);
        $this->assertMatchesRegularExpression('/^SIC-[A-Z0-9]{6}$/', $appointment->verification_code);
        $this->assertSame($patient->email, $appointment->notification_email);
        $this->assertNotNull($appointment->email_sent_at);
        Mail::assertSent(AppointmentConfirmation::class, fn($mail) => $mail->hasTo($patient->email));

        $this->actingAs($staff)->post('/appointments',$payload)->assertSessionHasErrors('appointment_time');
        $oldCode = $appointment->verification_code;
        $this->actingAs($staff)->patch("/appointments/{$appointment->id}/reschedule",['appointment_date'=>$date,'appointment_time'=>'09:30','reason'=>'Patient request'])->assertSessionHasNoErrors();
        $appointment->refresh();
        $this->assertSame('Scheduled', $appointment->status);
        $this->assertSame('09:30', substr($appointment->appointment_time, 0, 5));
        $this->assertNotSame($oldCode, $appointment->verification_code);
        $this->assertDatabaseHas('appointment_histories',['appointment_id'=>$appointment->id,'previous_time'=>'09:00','new_time'=>'09:30']);
    }

    public function test_appointment_stays_saved_when_confirmation_email_fails(): void
    {
        [$staff,$patient,$physician,$date] = $this->clinicSetup();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP unavailable'));

        $response = $this->actingAs($staff)->post('/appointments',[
            'patient_id'=>$patient->id,'physician_id'=>$physician->id,'appointment_date'=>$date,
            'appointment_time'=>'09:00','reason'=>'Consultation',
        ]);

        $response->assertSessionHasNoErrors()->assertSessionHas('warning');
        $this->assertDatabaseCount('appointments',1);
        $this->assertNotNull(Appointment::first()->verification_code);
        $this->assertNull(Appointment::first()->email_sent_at);
    }

    public function test_resend_succeeds_and_email_excludes_medical_details(): void
    {
        Mail::fake();
        [$staff,$patient,$physician,$date] = $this->clinicSetup();
        $appointment = $this->appointment($staff,$patient,$physician,$date, [
            'verification_code'=>'SIC-ABC123',
            'notification_email'=>$patient->email,
            'reason'=>'Sensitive medical complaint',
        ]);

        $this->actingAs($staff)->post("/appointments/{$appointment->id}/resend-confirmation")
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertNotNull($appointment->fresh()->email_sent_at);
        Mail::assertSent(AppointmentConfirmation::class, fn($mail) => $mail->hasTo($patient->email));

        $html = (new AppointmentConfirmation($appointment))->render();
        $this->assertStringContainsString('SIC-ABC123', $html);
        $this->assertStringNotContainsString('Sensitive medical complaint', $html);
    }

    public function test_verification_requires_the_right_code_and_precedes_arrival(): void
    {
        [$staff,$patient,$physician,$date] = $this->clinicSetup();
        $appointment = $this->appointment($staff,$patient,$physician,$date, [
            'verification_code'=>'SIC-ABC123',
            'notification_email'=>$patient->email,
        ]);

        $this->actingAs($staff)->post("/appointments/{$appointment->id}/arrive")
            ->assertSessionHasErrors('verification_code');
        $this->assertDatabaseCount('visits',0);

        $this->actingAs($staff)->post("/appointments/{$appointment->id}/verify",['verification_code'=>'SIC-WRONG1'])
            ->assertSessionHasErrors('verification_code');
        $this->assertNull($appointment->fresh()->verified_at);

        $this->actingAs($staff)->post("/appointments/{$appointment->id}/verify",['verification_code'=>'sic-abc123'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertNotNull($appointment->fresh()->verified_at);

        $this->actingAs($staff)->post("/appointments/{$appointment->id}/verify",['verification_code'=>'SIC-ABC123'])
            ->assertSessionHasErrors('verification_code');

        $this->actingAs($staff)->post("/appointments/{$appointment->id}/arrive")->assertSessionHasNoErrors();
        $this->assertDatabaseHas('appointments',['id'=>$appointment->id,'status'=>'Arrived']);
        $this->assertDatabaseHas('visits',['appointment_id'=>$appointment->id,'visit_type'=>'Scheduled','status'=>'Waiting']);
    }

    public function test_cancelled_completed_no_show_and_rescheduled_appointments_cannot_be_verified(): void
    {
        [$staff,$patient,$physician,$date] = $this->clinicSetup();

        foreach (['Cancelled','Completed','No-show','Rescheduled'] as $index => $status) {
            $appointment = $this->appointment($staff,$patient,$physician,$date, [
                'appointment_number'=>'APT-INVALID-'.$index,
                'appointment_time'=>sprintf('%02d:00', 10 + $index),
                'verification_code'=>'SIC-INV'.str_pad((string)$index, 3, '0', STR_PAD_LEFT),
                'notification_email'=>$patient->email,
                'status'=>$status,
            ]);
            $this->actingAs($staff)->post("/appointments/{$appointment->id}/verify",['verification_code'=>$appointment->verification_code])
                ->assertSessionHasErrors('verification_code');
            $this->assertNull($appointment->fresh()->verified_at);
        }
    }

    public function test_walk_in_creates_visit_without_appointment_or_reserved_slot(): void
    {
        [$staff,$patient,$physician] = $this->clinicSetup();
        $this->actingAs($staff)->post('/walk-ins',['patient_lookup'=>'P-TEST — Maria Santos','physician_lookup'=>'Dr. Reyes — Family Medicine','reason'=>'Walk-in complaint'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('appointments',0);
        $this->assertDatabaseHas('visits',['patient_id'=>$patient->id,'appointment_id'=>null,'visit_type'=>'Walk-in','status'=>'Waiting']);
    }

    public function test_arrival_vital_signs_and_consultation_complete_the_visit(): void
    {
        [$staff,$patient,$physician,$date,$physicianUser] = $this->clinicSetup();
        $appointment = $this->appointment($staff,$patient,$physician,$date, [
            'appointment_number'=>'APT-TEST-1',
            'appointment_time'=>'10:00',
            'verification_code'=>'SIC-TEST01',
            'notification_email'=>$patient->email,
            'verified_at'=>now(),
        ]);
        $this->actingAs($staff)->post("/appointments/{$appointment->id}/arrive")->assertSessionHasNoErrors();
        $visit = Visit::first();
        $this->assertNotNull($visit);
        $nurse = $this->user('Nurse','nurse');
        $this->actingAs($physicianUser)->post("/visits/{$visit->id}/consultation",['chief_complaint'=>'Cough','assessment'=>'Upper respiratory infection','action'=>'complete'])
            ->assertSessionHasErrors('vital_signs');
        $this->actingAs($physicianUser)->get("/visits/{$visit->id}")
            ->assertOk()->assertSee('Waiting for vital signs')
            ->assertDontSee('Save vital signs')
            ->assertDontSee('action="'.route('visits.vital-signs',$visit).'"',false);
        $this->actingAs($nurse)->get('/visits')
            ->assertOk()->assertSee('Vital Signs Queue')->assertSee('Record vital signs');
        $this->actingAs($nurse)->get("/visits/{$visit->id}")
            ->assertOk()->assertSee('Record vital signs')->assertDontSee('Assessment / diagnosis');
        $this->actingAs($nurse)->get("/patients/{$patient->id}")
            ->assertOk()->assertSee('Record vital signs')
            ->assertSee('action="'.route('visits.vital-signs',$visit).'"',false);
        $vitalSigns = ['blood_pressure'=>'120/80','temperature'=>36.7,'pulse'=>76,'respiratory_rate'=>18,'weight'=>58.5,'height'=>160,'notes'=>'Stable'];
        $this->actingAs($staff)->post("/visits/{$visit->id}/vital-signs",$vitalSigns)->assertForbidden();
        $this->actingAs($physicianUser)->post("/visits/{$visit->id}/vital-signs",$vitalSigns)->assertForbidden();
        $this->assertDatabaseMissing('vital_signs',['visit_id'=>$visit->id]);
        $this->actingAs($nurse)->post("/visits/{$visit->id}/vital-signs",$vitalSigns)->assertSessionHasNoErrors();
        $this->actingAs($physicianUser)->get("/visits/{$visit->id}")
            ->assertOk()->assertSee('Recorded vital signs')->assertSee('Read only')
            ->assertSee('Recorded by '.$nurse->name)->assertSee('120/80')->assertSee('Consultation')
            ->assertDontSee('Save vital signs');
        $this->actingAs($physicianUser)->get("/patients/{$patient->id}")
            ->assertOk()->assertSee('Vital Signs History')->assertSee('120/80')
            ->assertDontSee('Save vital signs');
        $this->actingAs($physicianUser)->post("/visits/{$visit->id}/consultation",['chief_complaint'=>'Cough','assessment'=>'Upper respiratory infection','notes'=>'Stable','recommendations'=>'Rest and fluids','action'=>'complete'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('visits',['id'=>$visit->id,'status'=>'Completed']);
        $this->assertDatabaseHas('appointments',['id'=>$appointment->id,'status'=>'Completed']);
        $this->assertDatabaseHas('consultations',['visit_id'=>$visit->id,'assessment'=>'Upper respiratory infection']);
        $this->actingAs($nurse)->get("/patients/{$patient->id}")
            ->assertOk()->assertSee('Vital Signs History')->assertSee('120/80')
            ->assertDontSee('Upper respiratory infection')->assertDontSee('Appointment history');
    }

    private function clinicSetup(): array
    {
        $staff = $this->user('Receptionist','reception');
        $physicianUser = $this->user('Physician','doctor');
        $patient = Patient::create($this->patientData(['patient_number'=>'P-TEST']));
        $physician = Physician::create(['user_id'=>$physicianUser->id,'name'=>'Dr. Reyes','specialization'=>'Family Medicine','is_active'=>true]);
        $date = Carbon::now()->next(Carbon::MONDAY)->toDateString();
        PhysicianSchedule::create(['physician_id'=>$physician->id,'day_of_week'=>1,'start_time'=>'08:00','end_time'=>'17:00','slot_duration'=>30,'is_available'=>true]);
        return [$staff,$patient,$physician,$date,$physicianUser];
    }

    private function appointment(User $staff, Patient $patient, Physician $physician, string $date, array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'appointment_number'=>'APT-TEST-DEFAULT',
            'patient_id'=>$patient->id,
            'physician_id'=>$physician->id,
            'appointment_date'=>$date,
            'appointment_time'=>'09:00',
            'reason'=>'Follow-up',
            'status'=>'Scheduled',
            'created_by'=>$staff->id,
        ], $overrides));
    }

    private function patientData(array $overrides = []): array
    {
        return array_merge([
            'patient_number'=>'P-UNUSED',
            'first_name'=>'Maria',
            'middle_name'=>null,
            'last_name'=>'Santos',
            'birth_date'=>'1990-01-01',
            'sex'=>'Female',
            'contact_number'=>'09170000000',
            'email'=>'maria@example.test',
            'address'=>'San Isidro',
            'emergency_contact'=>null,
            'emergency_contact_number'=>null,
        ], $overrides);
    }

    private function user(string $role,string $username): User
    {
        return User::create(['name'=>$username,'username'=>$username,'role'=>$role,'is_active'=>true,'password'=>Hash::make('password')]);
    }
}
