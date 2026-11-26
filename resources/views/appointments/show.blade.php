@extends('layouts.app')
@section('title','Manage Appointment')
@section('crumb','Manage Appointment')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointment-management.css') }}">
@endpush
@section('content')
@php
    $isEarlier = $appointment->appointment_date->isBefore(today());
    $rescheduleOpen = $errors->hasAny(['appointment_date', 'appointment_time', 'reason']) || session()->hasOldInput('appointment_date');
@endphp
<div class="appointment-management">
<div class="title-row">
    <div><h1>Manage Appointment</h1><p>{{ $appointment->appointment_number }} · Current status: {{ $appointment->status }}</p></div>
    <div class="toolbar">
        <x-section-return :href="route('appointments.index')" label="appointments" />
        <span class="badge badge-{{ str_replace(' ','-',$appointment->status) }}">{{ $appointment->status }}</span>
        <span class="badge badge-{{ $appointment->verified_at?'Completed':'No-show' }}">{{ $appointment->verified_at?'Verified':'Unverified' }}</span>
    </div>
</div>

@if($appointment->status === 'Scheduled')
<section id="verification" class="panel verification-card {{ $isEarlier ? 'needs-review' : ($appointment->verified_at ? 'is-verified' : '') }}" aria-labelledby="verification-heading">
    <div class="verification-content">
        <div class="verification-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"></path><path d="m8 12 3 3 5-6"></path></svg>
        </div>
        <p class="verification-eyebrow">Appointment verification</p>
        <h2 id="verification-heading">{{ $isEarlier ? 'Review this appointment' : ($appointment->verified_at ? 'Appointment verified' : 'Verify appointment') }}</h2>
        <p class="verification-description">{{ $isEarlier ? 'The scheduled date has passed. Update the booking before checking the patient in.' : ($appointment->verified_at ? 'The confirmation code has been accepted. You can now record the patient’s arrival.' : 'Check the patient’s confirmation code before recording their arrival.') }}</p>
        <div class="verification-context">
            <strong>{{ $appointment->patient->full_name }}</strong>
            <span>{{ $appointment->appointment_number }} · {{ $appointment->physician->name }}</span>
            <span>{{ $appointment->appointment_date->format('l, d F Y') }} · {{ date('g:i A',strtotime($appointment->appointment_time)) }}</span>
        </div>
        <form method="POST" action="{{ route('appointments.verify',$appointment) }}" class="verification-form" data-confirm="Verify this code for {{ $appointment->patient->full_name }} and appointment {{ $appointment->appointment_number }}?">
            @csrf
            @if(!$isEarlier && !$appointment->verified_at)
                <div class="field">
                    <label class="required" for="verification-code">Verification code</label>
                    <div class="verification-entry">
                        <input id="verification-code" name="verification_code" value="{{ old('verification_code') }}" placeholder="SIC-XXXXXX" maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="verification-code-help" @if($errors->has('verification_code')) aria-invalid="true" @endif required>
                        <button class="btn btn-primary verification-submit">Verify code</button>
                    </div>
                    <span id="verification-code-help" class="help">Enter the code from the patient’s appointment confirmation email.</span>
                </div>
            @endif
        </form>
        @if($isEarlier)
            <div class="verification-next"><a class="btn btn-primary" href="#reschedule">Reschedule appointment</a><span class="help">If the patient missed this booking, use Mark No-show below.</span></div>
        @elseif($appointment->verified_at)
            <p class="verification-confirmed">Verified {{ $appointment->verified_at->format('d M Y g:i A') }}</p>
            <form method="POST" action="{{ route('appointments.arrive',$appointment) }}" class="verification-next" data-confirm="Mark {{ $appointment->patient->full_name }} as Arrived and open a clinic visit?">@csrf<button class="btn btn-primary verification-submit">Mark as Arrived</button></form>
        @else
            <div class="verification-support">
                <span class="help">Confirmation email: {{ $appointment->notification_email ?: $appointment->patient->email ?: 'Not recorded' }}</span>
                <form method="POST" action="{{ route('appointments.resend-confirmation',$appointment) }}" data-confirm="Send another confirmation email to {{ $appointment->patient->email }}?">@csrf<button class="btn btn-sm">Resend Confirmation</button></form>
            </div>
        @endif
    </div>
</section>
@endif

<div class="panel">
    <div class="panel-head"><h2>Current booking</h2></div>
    <div class="panel-body record">
        <div><span>Appointment ID</span>{{ $appointment->appointment_number }}</div>
        <div><span>Patient ID</span>{{ $appointment->patient->patient_number }}</div>
        <div><span>Patient</span><a class="btn btn-sm" href="{{ route('patients.show',$appointment->patient) }}">{{ $appointment->patient->full_name }}</a></div>
        <div><span>Patient email</span>{{ $appointment->notification_email?:$appointment->patient->email?:'Not recorded' }}</div>
        <div><span>Physician</span>{{ $appointment->physician->name }}</div>
        <div><span>Date and time</span>{{ $appointment->appointment_date->format('l, d F Y') }} · {{ date('g:i A',strtotime($appointment->appointment_time)) }}</div>
        <div><span>Reason</span>{{ $appointment->reason }}</div>
        <div><span>Status</span>{{ $appointment->status }}</div>
        <div><span>Verification</span>{{ $appointment->verified_at?'Verified '.$appointment->verified_at->format('d M Y g:i A'):'Not yet verified' }}</div>
        <div><span>Email status</span>{{ $appointment->email_sent_at?'Sent '.$appointment->email_sent_at->format('d M Y g:i A'):'Not sent' }}</div>
        <div><span>Created</span>{{ $appointment->created_at->format('d M Y g:i A') }}</div>
    </div>
</div>

@if($appointment->status === 'Scheduled')
<details id="reschedule" class="panel appointment-disclosure" @if($rescheduleOpen) open @endif>
    <summary>
        <span><strong>Reschedule appointment</strong><small>Change the date or time when needed</small></span>
        <svg class="disclosure-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
    </summary>
    <div class="panel-body">
        <form method="POST" action="{{ route('appointments.reschedule',$appointment) }}" data-confirm="Reschedule this appointment, replace its verification code, and email the updated confirmation?">
            @csrf @method('PATCH')
            <div class="form-grid">
                <div class="field full"><label for="reschedule-email">Patient email</label><input id="reschedule-email" type="email" value="{{ $appointment->patient->email }}" readonly><span class="help">Change this only from Patient Edit.</span></div>
                <div class="field"><label class="required" for="reschedule-date">New date</label><x-date-picker id="reschedule-date" name="appointment_date" :value="old('appointment_date')" :min="now()->toDateString()" required placeholder="Choose a new date" /></div>
                <div class="field"><label class="required" for="reschedule-time">New time</label><input id="reschedule-time" type="time" name="appointment_time" value="{{ old('appointment_time') }}" required></div>
                <div class="field full"><label class="required" for="reschedule-reason">Reason for rescheduling</label><input id="reschedule-reason" name="reason" value="{{ old('reason') }}" maxlength="500" required></div>
            </div>
            <div class="actions"><button class="btn btn-primary">Save new slot</button></div>
        </form>
    </div>
</details>

<div class="grid two-col appointment-secondary">
    <div>
        <div class="panel panel-body">
            <h2>Other actions</h2>
            <p class="muted">If the patient missed this booking, mark it No-show.</p>
            @if($appointment->verified_at || $isEarlier)
                <form method="POST" action="{{ route('appointments.resend-confirmation',$appointment) }}" data-confirm="Send another confirmation email to {{ $appointment->patient->email }}?">@csrf<button class="btn">Resend Confirmation</button></form>
            @endif
            <div class="actions">
                <form method="POST" action="{{ route('appointments.no-show',$appointment) }}" data-confirm="Mark {{ $appointment->patient->full_name }} as No-show? This appointment will leave the active schedule.">@csrf @method('PATCH')<button class="btn">Mark No-show</button></form>
            </div>
        </div>
    </div>
    <div>
        <form method="POST" action="{{ route('appointments.cancel',$appointment) }}" class="panel panel-body" data-confirm="Cancel appointment {{ $appointment->appointment_number }}? This will remove it from the active schedule.">
            @csrf @method('PATCH')
            <h2>Cancel appointment</h2>
            <div class="field"><label class="required">Cancellation reason</label><input name="cancellation_reason" required></div>
            <div class="actions"><button class="btn btn-danger">Confirm cancellation</button></div>
        </form>
    </div>
</div>
@endif

@if($appointment->cancellation_reason)<div class="notice"><strong>Cancellation reason:</strong> {{ $appointment->cancellation_reason }}</div>@endif
<div class="panel" style="margin-top:18px">
    <div class="panel-head"><h2>Change history</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Previous slot</th><th>New slot</th><th>Reason</th><th>Changed by</th></tr></thead><tbody>
    @forelse($appointment->histories as $h)<tr><td>{{ $h->previous_date->format('d M Y') }} {{ date('g:i A',strtotime($h->previous_time)) }}</td><td>{{ $h->new_date->format('d M Y') }} {{ date('g:i A',strtotime($h->new_time)) }}</td><td>{{ $h->reason }}</td><td>{{ $h->changedBy->name }}</td></tr>
    @empty<tr><td colspan="4" class="empty">No schedule changes.</td></tr>@endforelse
    </tbody></table></div>
</div>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('js/appointment-management.js') }}" defer></script>
@endpush
