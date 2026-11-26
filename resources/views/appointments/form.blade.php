@extends('layouts.app')
@section('title','New Appointment')
@section('crumb','New Appointment')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointment-booking.css') }}">
@endpush
@section('content')
<div class="appointment-booking">
    <div class="title-row">
        <div><h1>New Appointment</h1><p>Choose a patient, a date, then an available time.</p></div>
        <x-section-return :href="route('appointments.index')" label="appointments" />
    </div>
    <form id="appointment-form" method="POST" action="{{ route('appointments.store') }}" class="panel booking-card" data-confirm="Create this appointment and send the confirmation email to the selected patient?">
        @csrf
        <section class="booking-patient" aria-labelledby="booking-patient-heading">
            <h2 id="booking-patient-heading" class="booking-heading"><span>1</span> Patient</h2>
            <div class="form-grid">
                <div class="field">
                    <label class="required" for="patient-lookup">Patient name or ID</label>
                    <input id="patient-lookup" name="patient_lookup" list="patient-options" value="{{ old('patient_lookup') }}" placeholder="Search registered patients" autocomplete="off" required>
                    <input id="patient-id" type="hidden" name="patient_id" value="{{ old('patient_id', request('patient')) }}">
                    <datalist id="patient-options">
                        @foreach($patients as $patient)
                            <option value="{{ $patient->patient_number }} — {{ $patient->full_name }}" data-id="{{ $patient->id }}" data-email="{{ $patient->email }}"></option>
                        @endforeach
                    </datalist>
                    <span class="help">New patient? <a class="btn btn-sm" href="{{ route('patients.create') }}">Register first</a></span>
                </div>
                <div class="field">
                    <label class="required" for="physician-lookup">Physician</label>
                    <input id="physician-lookup" name="physician_lookup" list="physician-options" value="{{ old('physician_lookup') }}" placeholder="Choose a physician" autocomplete="off" required>
                    <input id="physician-id" type="hidden" name="physician_id" value="{{ old('physician_id') }}">
                    <datalist id="physician-options">
                        @foreach($physicians as $physician)
                            <option value="{{ $physician->name }} — {{ $physician->specialization }}" data-id="{{ $physician->id }}"></option>
                        @endforeach
                    </datalist>
                    <span class="help">Dates follow this physician’s schedule.</span>
                </div>
            </div>
            <div class="field booking-email">
                <label for="notification-email">Confirmation email</label>
                <input id="notification-email" name="notification_email" type="email" value="{{ old('notification_email') }}" placeholder="Filled from the selected patient" readonly required aria-describedby="email-help">
                <span id="email-help" class="help">Manage this email in Patient Edit.</span>
            </div>
        </section>
        <div id="availability-picker" class="booking-schedule" data-url="{{ route('appointments.availability') }}" data-minimum-date="{{ now()->toDateString() }}" data-maximum-date="{{ now()->addMonthsNoOverflow(6)->endOfMonth()->toDateString() }}" data-restore-date="{{ old('appointment_date') }}" data-restore-time="{{ old('appointment_time') }}">
            <section class="booking-date" aria-labelledby="booking-date-heading">
                <h2 id="booking-date-heading" class="booking-heading"><span>2</span> Date</h2>
                <div class="field booking-selection">
                    <label class="required" for="appointment-date">Selected date</label>
                    <input id="appointment-date" type="date" name="appointment_date" value="{{ old('appointment_date') }}" readonly required>
                </div>
                <div class="booking-calendar" aria-label="Available appointment dates">
                    <div class="booking-calendar-head">
                        <button id="calendar-previous" type="button" aria-label="Previous month" disabled>‹</button>
                        <strong id="calendar-month">Select patient first</strong>
                        <button id="calendar-next" type="button" aria-label="Next month" disabled>›</button>
                    </div>
                    <div class="booking-week" aria-hidden="true"><span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span></div>
                    <div id="calendar-days" class="booking-days"></div>
                    <p id="calendar-message" class="booking-empty" role="status">Choose a patient and physician to see available dates.</p>
                    <div class="booking-legend"><i aria-hidden="true"></i> Available <span>Faded dates are unavailable</span></div>
                </div>
            </section>
            <section class="booking-time" aria-labelledby="booking-time-heading">
                <h2 id="booking-time-heading" class="booking-heading"><span>3</span> Time <small id="time-count"></small></h2>
                <div class="field booking-selection">
                    <label class="required" for="appointment-time">Selected time</label>
                    <input id="appointment-time" type="time" name="appointment_time" value="{{ old('appointment_time') }}" readonly required>
                </div>
                <div id="time-area" class="booking-time-area">
                    <p id="time-message" class="booking-empty" role="status">Choose a date to see its available times.</p>
                    <div id="time-slots" class="booking-time-slots" role="group" aria-label="Available appointment times"></div>
                </div>
                <p class="booking-time-note">Philippine time · Asia/Manila</p>
            </section>
        </div>
        <div class="booking-footer">
            <div class="field">
                <label class="required" for="appointment-reason">Reason for visit</label>
                <input id="appointment-reason" name="reason" value="{{ old('reason') }}" placeholder="e.g. Follow-up consultation" maxlength="500" required>
            </div>
            <div class="booking-save">
                <p class="help">A confirmation email will be sent to the patient.</p>
                <button class="btn btn-primary" type="submit">Save appointment</button>
            </div>
        </div>
    </form>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('js/appointment-booking.js') }}" defer></script>
@endpush
