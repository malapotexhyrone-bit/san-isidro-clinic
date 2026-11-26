@extends('layouts.app')
@section('title','Record Walk-in')
@section('crumb','Record Walk-in')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/appointment-booking.css') }}">
    <link rel="stylesheet" href="{{ asset('css/walkin-booking.css') }}">
@endpush
@section('content')
<div class="appointment-booking walkin-booking">
    <div class="title-row">
        <div><h1>Record Walk-in</h1><p>Choose a patient and physician, then record the reason for arrival.</p></div>
        <x-section-return :href="route('appointments.index')" label="appointments" />
    </div>
    <form id="walkin-form" method="POST" action="{{ route('walkins.store') }}" class="panel booking-card" data-confirm="Record this patient as a walk-in arrival and open a clinic visit?">
        @csrf
        <section class="booking-patient" aria-labelledby="walkin-patient-heading">
            <h2 id="walkin-patient-heading" class="booking-heading"><span>1</span> Patient</h2>
            <div class="form-grid">
                <div class="field">
                    <label class="required" for="patient-lookup">Patient name or ID</label>
                    <input id="patient-lookup" name="patient_lookup" list="patient-options" value="{{ old('patient_lookup') }}" placeholder="Search registered patients" autocomplete="off" required>
                    <input id="patient-id" type="hidden" name="patient_id" value="{{ old('patient_id') }}">
                    <datalist id="patient-options">
                        @foreach($patients as $patient)
                            <option value="{{ $patient->patient_number }} — {{ $patient->full_name }}" data-id="{{ $patient->id }}"></option>
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
                    <span class="help">Select the physician for this visit.</span>
                </div>
            </div>
        </section>
        <section class="walkin-arrival" aria-labelledby="walkin-arrival-heading">
            <h2 id="walkin-arrival-heading" class="booking-heading"><span>2</span> Arrival <small>Philippine time</small></h2>
            @php($arrival = now())
            <div class="walkin-arrival-grid">
                <div class="walkin-arrival-detail"><span>Date</span><strong>{{ $arrival->format('d F Y') }}</strong></div>
                <div class="walkin-arrival-detail"><span>Time</span><strong>{{ $arrival->format('g:i A') }}</strong></div>
            </div>
            <p class="booking-time-note">The exact arrival time is recorded when you save.</p>
        </section>
        <div class="booking-footer">
            <section class="field" aria-labelledby="walkin-reason-heading">
                <h2 id="walkin-reason-heading" class="booking-heading"><span>3</span> Visit details</h2>
                <label class="required" for="walkin-reason">Reason for visit</label>
                <textarea id="walkin-reason" name="reason" rows="2" maxlength="1000" placeholder="Briefly describe the reason for this visit" required>{{ old('reason') }}</textarea>
            </section>
            <div class="booking-save">
                <p class="help">Ready to record this walk-in?</p>
                <button type="submit" class="btn btn-primary">Record arrival</button>
            </div>
        </div>
    </form>
</div>
<script>
    (() => {
        function bindClinicLookup(inputId, hiddenId, listId) {
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const options = [...document.getElementById(listId).options];
            const sync = () => {
                const match = options.find(option => option.value === input.value);
                hidden.value = match ? match.dataset.id : '';
                input.setCustomValidity(!input.value || match ? '' : 'Select one of the matching records shown.');
            };
            if (!input.value && hidden.value) {
                const selected = options.find(option => option.dataset.id === hidden.value);
                if (selected) input.value = selected.value;
            }
            sync();
            input.addEventListener('input', sync);
            input.addEventListener('change', sync);
            return sync;
        }
        const syncPatient = bindClinicLookup('patient-lookup', 'patient-id', 'patient-options');
        const syncPhysician = bindClinicLookup('physician-lookup', 'physician-id', 'physician-options');
        document.getElementById('walkin-form').addEventListener('submit', function (event) {
            syncPatient(); syncPhysician();
            if (!this.checkValidity()) event.preventDefault();
        });
    })();
</script>
@endsection
