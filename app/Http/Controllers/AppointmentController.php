<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\PhysicianSchedule;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $viewerPhysicianId = $request->user()->role === 'Physician'
            ? $request->user()->physician?->id
            : null;
        if ($request->user()->role === 'Physician') {
            abort_unless($viewerPhysicianId,403,'This physician account is not linked to a physician record.');
        }
        $period = in_array($request->query('period'), ['needs-review','clinic-day','upcoming'], true)
            ? $request->query('period')
            : 'clinic-day';
        $status = in_array($request->query('status'), Appointment::STATUSES, true)
            ? $request->query('status')
            : null;
        $physicianId = $viewerPhysicianId ?: ($request->integer('physician') ?: null);
        $today = now()->toDateString();

        $appointments = Appointment::with(['patient','physician'])
            ->when($status, fn($query) => $query->where('status',$status))
            ->when($physicianId, fn($query) => $query->where('physician_id',$physicianId))
            ->when($period === 'needs-review', fn($query) => $query->whereDate('appointment_date','<',$today))
            ->when($period === 'clinic-day', fn($query) => $query->whereDate('appointment_date',$today))
            ->when($period === 'upcoming', fn($query) => $query->whereDate('appointment_date','>',$today))
            ->when($period === 'needs-review', fn($query) => $query->orderByDesc('appointment_date'), fn($query) => $query->orderBy('appointment_date'))
            ->orderBy('appointment_time')
            ->get();

        $countFor = function (string $selectedPeriod) use ($status, $physicianId, $today): int {
            return Appointment::query()
                ->when($status, fn($query) => $query->where('status',$status))
                ->when($physicianId, fn($query) => $query->where('physician_id',$physicianId))
                ->when($selectedPeriod === 'needs-review', fn($query) => $query->whereDate('appointment_date','<',$today))
                ->when($selectedPeriod === 'clinic-day', fn($query) => $query->whereDate('appointment_date',$today))
                ->when($selectedPeriod === 'upcoming', fn($query) => $query->whereDate('appointment_date','>',$today))
                ->count();
        };

        $periodCounts = [
            'needs-review' => $countFor('needs-review'),
            'clinic-day' => $countFor('clinic-day'),
            'upcoming' => $countFor('upcoming'),
        ];
        $appointmentsByPhysician = $appointments->groupBy('physician_id');
        $appointmentPhysicianIds = $appointments->pluck('physician_id')->unique();

        $physicians = Physician::with('schedules')
            ->when($physicianId, fn($query) => $query->whereKey($physicianId))
            ->when(!$physicianId && $period === 'clinic-day', fn($query) => $query->where(function ($query) use ($appointmentPhysicianIds) {
                $query->where('is_active',true)->orWhereIn('id',$appointmentPhysicianIds);
            }))
            ->when(!$physicianId && $period !== 'clinic-day', fn($query) => $query->whereIn('id',$appointmentPhysicianIds))
            ->orderBy('name')
            ->get();

        $bookedToday = Appointment::whereDate('appointment_date',$today)
            ->whereIn('status',Appointment::ACTIVE_STATUSES)
            ->selectRaw('physician_id, count(*) as total')
            ->groupBy('physician_id')
            ->pluck('total','physician_id');
        $availability = $physicians->mapWithKeys(function (Physician $physician) use ($bookedToday): array {
            $daySchedules = $physician->schedules
                ->where('day_of_week',now()->dayOfWeek)
                ->where('is_available',true);
            $totalSlots = $daySchedules->sum(function (PhysicianSchedule $schedule): int {
                $minutes = Carbon::parse($schedule->start_time)->diffInMinutes(Carbon::parse($schedule->end_time));
                return $schedule->slot_duration > 0 ? intdiv($minutes,$schedule->slot_duration) : 0;
            });
            $openSlots = max($totalSlots - (int)($bookedToday[$physician->id] ?? 0), 0);

            return [$physician->id => [
                'scheduled' => $totalSlots > 0,
                'open_slots' => $openSlots,
            ]];
        });

        $physicianOptions = Physician::query()
            ->when($viewerPhysicianId, fn($query) => $query->whereKey($viewerPhysicianId))
            ->orderBy('name')->get(['id','name','specialization','is_active']);

        return view('appointments.index', compact(
            'appointments','appointmentsByPhysician','physicians','physicianOptions',
            'availability','period','periodCounts','status'
        ));
    }

    public function create()
    {
        return view('appointments.form', [
            'appointment' => new Appointment,
            'patients' => Patient::orderBy('last_name')->get(),
            'physicians' => Physician::where('is_active',true)->orderBy('name')->get(),
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'physician_id' => ['required','exists:physicians,id'],
            'month' => ['required','date_format:Y-m'],
        ]);
        $physician = Physician::where('is_active',true)->findOrFail($data['physician_id']);
        $month = Carbon::createFromFormat('Y-m',$data['month'])->startOfMonth();
        $earliest = today();
        $latest = today()->addMonthsNoOverflow(6)->endOfMonth();
        if ($month->copy()->endOfMonth()->isBefore($earliest) || $month->copy()->startOfMonth()->isAfter($latest)) {
            throw ValidationException::withMessages(['month'=>'Choose a month within the next six months.']);
        }

        $rangeStart = $month->copy()->startOfMonth()->max($earliest);
        $rangeEnd = $month->copy()->endOfMonth()->min($latest);
        $schedules = $physician->schedules()->where('is_available',true)->get()->groupBy('day_of_week');
        $bookings = Appointment::where('physician_id',$physician->id)
            ->whereBetween('appointment_date',[$rangeStart->toDateString(),$rangeEnd->toDateString()])
            ->whereIn('status',Appointment::ACTIVE_STATUSES)
            ->get(['appointment_date','appointment_time'])
            ->groupBy(fn(Appointment $appointment) => $appointment->appointment_date->toDateString())
            ->map(fn($items) => $items->map(fn(Appointment $appointment) => substr($appointment->appointment_time,0,5))->flip());

        $dates = [];
        for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
            $daySchedules = $schedules->get($date->dayOfWeek,collect());
            if ($daySchedules->isEmpty()) continue;

            $slots = [];
            foreach ($daySchedules as $schedule) {
                $cursor = Carbon::parse($date->toDateString().' '.$schedule->start_time);
                $end = Carbon::parse($date->toDateString().' '.$schedule->end_time);
                while ($cursor->lt($end)) {
                    $time = $cursor->format('H:i');
                    $booked = isset($bookings[$date->toDateString()][$time]);
                    $elapsed = $date->isToday() && $cursor->lte(now());
                    $slots[$time] = [
                        'time' => $time,
                        'label' => $cursor->format('g:i A'),
                        'available' => !$booked && !$elapsed,
                    ];
                    $cursor->addMinutes($schedule->slot_duration);
                }
            }
            ksort($slots);
            $slots = array_values($slots);
            $dates[$date->toDateString()] = [
                'open_slots' => collect($slots)->where('available',true)->count(),
                'times' => $slots,
            ];
        }

        return response()->json([
            'month' => $month->format('Y-m'),
            'month_label' => $month->format('F Y'),
            'dates' => $dates,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'patient_id' => $this->resolvePatientId($request->input('patient_id'), $request->input('patient_lookup')),
            'physician_id' => $this->resolvePhysicianId($request->input('physician_id'), $request->input('physician_lookup')),
        ]);

        $data = $this->validated($request);
        $patient = Patient::findOrFail($data['patient_id']);
        $this->assertPatientHasEmail($patient);
        $this->assertSlotAvailable($data);

        $appointment = DB::transaction(function () use ($request, $data, $patient): Appointment {
            $appointment = Appointment::create($data + [
                'appointment_number' => $this->nextNumber(),
                'status' => 'Scheduled',
                'created_by' => $request->user()->id,
            ]);

            // Generate only after the slot checks pass and the appointment row exists.
            $appointment->update([
                'verification_code' => $this->uniqueVerificationCode(),
                'notification_email' => $patient->email,
            ]);

            return $appointment->fresh(['patient', 'physician']);
        });

        $mailSent = $this->sendConfirmation($appointment);
        $response = redirect()->route('appointments.show',$appointment)
            ->with('success', "Appointment {$appointment->appointment_number} saved. The slot is now reserved.");

        return $mailSent
            ? $response
            : $response->with('warning', 'The appointment was saved, but the confirmation email could not be sent. Check the mail settings and use Resend Confirmation.');
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient','physician','histories.changedBy','visit']);
        return view('appointments.show', compact('appointment'));
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        abort_if(in_array($appointment->status, ['Cancelled','Completed','No-show'], true), 422, 'This appointment can no longer be rescheduled.');
        $data = $request->validate([
            'appointment_date' => ['required','date','after_or_equal:today'],
            'appointment_time' => ['required','date_format:H:i'],
            'reason' => ['required','string','max:500'],
        ]);
        $this->assertPatientHasEmail($appointment->patient);
        $slot = ['physician_id' => $appointment->physician_id] + $data;
        $this->assertSlotAvailable($slot, $appointment);

        DB::transaction(function() use ($request,$appointment,$data) {
            AppointmentHistory::create([
                'appointment_id'=>$appointment->id, 'previous_date'=>$appointment->appointment_date,
                'previous_time'=>$appointment->appointment_time, 'new_date'=>$data['appointment_date'],
                'new_time'=>$data['appointment_time'], 'reason'=>$data['reason'], 'changed_by'=>$request->user()->id,
            ]);
            $appointment->update([
                'appointment_date'=>$data['appointment_date'],
                'appointment_time'=>$data['appointment_time'],
                'status'=>'Scheduled',
                'verification_code'=>$this->uniqueVerificationCode(),
                'notification_email'=>$appointment->patient->email,
                'verified_at'=>null,
                'email_sent_at'=>null,
            ]);
        });

        $appointment->refresh()->load(['patient', 'physician']);
        $mailSent = $this->sendConfirmation($appointment);
        $response = redirect()->route('appointments.show',$appointment)
            ->with('success','Appointment rescheduled. A new verification code was generated and the previous slot remains in change history.');

        return $mailSent
            ? $response
            : $response->with('warning', 'The new slot was saved, but the updated confirmation email could not be sent. Use Resend Confirmation.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $data = $request->validate(['cancellation_reason'=>['required','string','max:500']]);
        $appointment->update(['status'=>'Cancelled','cancellation_reason'=>$data['cancellation_reason']]);
        return redirect()->route('appointments.show',$appointment)->with('success','Appointment cancelled and retained in history.');
    }

    public function noShow(Appointment $appointment)
    {
        abort_if(!in_array($appointment->status,['Scheduled','Rescheduled'],true),422,'Only waiting appointments can be marked no-show.');
        $appointment->update(['status'=>'No-show']);
        return back()->with('success','Appointment marked as No-show.');
    }

    public function verify(Request $request, Appointment $appointment)
    {
        if ($appointment->status !== 'Scheduled') {
            throw ValidationException::withMessages([
                'verification_code' => "Appointments with status {$appointment->status} cannot be verified.",
            ]);
        }
        if ($appointment->appointment_date->isBefore(today())) {
            throw ValidationException::withMessages([
                'verification_code' => 'An earlier appointment must be rescheduled or marked No-show before it can continue.',
            ]);
        }
        if ($appointment->verified_at) {
            throw ValidationException::withMessages([
                'verification_code' => 'This appointment has already been verified.',
            ]);
        }

        $data = $request->validate([
            'verification_code' => ['required','string','max:20'],
        ]);
        $submitted = strtoupper(trim($data['verification_code']));
        if (!$appointment->verification_code || !hash_equals($appointment->verification_code, $submitted)) {
            throw ValidationException::withMessages([
                'verification_code' => 'The verification code is invalid for this appointment and patient.',
            ]);
        }

        $appointment->update(['verified_at' => now()]);

        return back()->with('success', "Appointment {$appointment->appointment_number} for {$appointment->patient->full_name} was verified. You may now mark the patient as Arrived.");
    }

    public function resendConfirmation(Appointment $appointment)
    {
        if ($appointment->status !== 'Scheduled') {
            throw ValidationException::withMessages([
                'appointment' => "Confirmation cannot be resent for an appointment with status {$appointment->status}.",
            ]);
        }
        $this->assertPatientHasEmail($appointment->patient);

        $changes = ['notification_email' => $appointment->patient->email];
        if (!$appointment->verification_code) {
            $changes['verification_code'] = $this->uniqueVerificationCode();
        }
        $appointment->update($changes);
        $appointment->loadMissing(['patient', 'physician']);

        if (!$this->sendConfirmation($appointment)) {
            return back()->with('warning', 'The confirmation email could not be sent. The appointment is still saved; check the mail settings and try again.');
        }

        return back()->with('success', "Confirmation resent to {$appointment->notification_email}.");
    }

    public function arrive(Request $request, Appointment $appointment)
    {
        if ($appointment->status !== 'Scheduled') {
            throw ValidationException::withMessages([
                'appointment' => 'Only a scheduled appointment may be marked as Arrived.',
            ]);
        }
        if ($appointment->appointment_date->isBefore(today())) {
            throw ValidationException::withMessages([
                'appointment' => 'An earlier appointment must be rescheduled or marked No-show before arrival can be recorded.',
            ]);
        }
        if (!$appointment->verified_at) {
            throw ValidationException::withMessages([
                'verification_code' => 'Verify the appointment code before marking the patient as Arrived.',
            ]);
        }

        DB::transaction(function() use ($request,$appointment) {
            $appointment->update(['status'=>'Arrived']);
            Visit::firstOrCreate(['appointment_id'=>$appointment->id], [
                'visit_number'=>$this->nextVisitNumber(), 'patient_id'=>$appointment->patient_id,
                'physician_id'=>$appointment->physician_id, 'arrived_at'=>now(), 'visit_type'=>'Scheduled',
                'status'=>'Waiting', 'reason'=>$appointment->reason, 'created_by'=>$request->user()->id,
            ]);
        });

        return redirect()->route('visits.index')->with('success','Patient arrival recorded and visit opened.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'patient_id'=>['required','exists:patients,id'], 'physician_id'=>['required','exists:physicians,id'],
            'appointment_date'=>['required','date','after_or_equal:today'], 'appointment_time'=>['required','date_format:H:i'],
            'reason'=>['required','string','max:1000'],
        ]);
    }

    private function assertPatientHasEmail(Patient $patient): void
    {
        if (!$patient->email) {
            throw ValidationException::withMessages([
                'patient_id' => 'This legacy patient has no email address. Add one from Patient Edit before scheduling or resending a confirmation.',
            ]);
        }
    }

    private function assertSlotAvailable(array $data, ?Appointment $ignore = null): void
    {
        $date = Carbon::parse($data['appointment_date']);
        $time = substr($data['appointment_time'],0,5);
        if (Carbon::parse($date->toDateString().' '.$time)->lte(now())) {
            throw ValidationException::withMessages(['appointment_time'=>'Choose an available time that has not already passed.']);
        }
        $schedule = PhysicianSchedule::where('physician_id',$data['physician_id'])->where('day_of_week',$date->dayOfWeek)
            ->where('is_available',true)->whereTime('start_time','<=',$time)->whereTime('end_time','>',$time)->first();
        if (!$schedule) throw ValidationException::withMessages(['appointment_time'=>'The physician is unavailable at this date and time.']);
        $start = Carbon::parse($schedule->start_time); $selected = Carbon::parse($time);
        if ($start->diffInMinutes($selected) % $schedule->slot_duration !== 0) throw ValidationException::withMessages(['appointment_time'=>"Choose a {$schedule->slot_duration}-minute schedule slot."]);
        $conflict = Appointment::where('physician_id',$data['physician_id'])->whereDate('appointment_date',$data['appointment_date'])
            ->whereRaw('substr(appointment_time, 1, 5) = ?', [$time])->whereIn('status',Appointment::ACTIVE_STATUSES)
            ->when($ignore,fn($q)=>$q->whereKeyNot($ignore->id))->exists();
        if ($conflict) throw ValidationException::withMessages(['appointment_time'=>'Appointment conflict: this physician already has an appointment in the selected slot.']);
    }

    private function sendConfirmation(Appointment $appointment): bool
    {
        try {
            Mail::to($appointment->notification_email)->send(new AppointmentConfirmation($appointment));
            $appointment->update(['email_sent_at' => now()]);
            return true;
        } catch (Throwable $exception) {
            Log::error('Appointment confirmation email failed.', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'recipient' => $appointment->notification_email,
                'exception' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    private function uniqueVerificationCode(): string
    {
        do {
            $code = 'SIC-'.Str::upper(Str::random(6));
        } while (Appointment::where('verification_code', $code)->exists());

        return $code;
    }

    private function nextNumber(): string
    {
        return 'APT-'.now()->format('Ymd').'-'.str_pad((string)((Appointment::max('id')??0)+1),5,'0',STR_PAD_LEFT);
    }

    private function nextVisitNumber(): string
    {
        return 'VIS-'.now()->format('Ymd').'-'.str_pad((string)((Visit::max('id')??0)+1),5,'0',STR_PAD_LEFT);
    }

    private function resolvePatientId(mixed $id, ?string $lookup): mixed
    {
        if ($id && Patient::whereKey($id)->exists()) return $id;
        $needle = trim(explode(' — ', (string)$lookup)[0]);
        return Patient::all()->first(fn($patient) =>
            strcasecmp($patient->patient_number, $needle) === 0 || strcasecmp($patient->full_name, $needle) === 0
        )?->id;
    }

    private function resolvePhysicianId(mixed $id, ?string $lookup): mixed
    {
        if ($id && Physician::whereKey($id)->where('is_active', true)->exists()) return $id;
        $needle = trim(explode(' — ', (string)$lookup)[0]);
        return Physician::where('is_active', true)->get()->first(fn($physician) =>
            strcasecmp($physician->name, $needle) === 0
        )?->id;
    }
}
