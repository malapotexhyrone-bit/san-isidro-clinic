@extends('layouts.app')
@section('title',$patient->exists?'Edit Patient':'Register Patient')
@section('crumb','Patient Record')
@section('content')
<div class="title-row">
    <div>
        <h1>{{ $patient->exists?'Edit Patient Record':'Patient Record' }}</h1>
        <p>{{ $patient->exists?$patient->patient_number:'Register or edit patient information · Required fields are marked' }}</p>
    </div>
    <x-section-return :href="route('patients.index')" label="patients" />
</div>
<form method="POST" action="{{ $patient->exists?route('patients.update',$patient):route('patients.store') }}" class="panel workflow-card" data-confirm="Save this patient information?">
    @csrf
    @if($patient->exists) @method('PUT') @endif
<section class="workflow-section">
        <x-workflow-heading step="1" title="Patient information" />
        <div class="form-grid">
        <div class="field"><label class="required">First name</label><input name="first_name" value="{{ old('first_name',$patient->first_name) }}" required></div>
        <div class="field"><label>Middle name</label><input name="middle_name" value="{{ old('middle_name',$patient->middle_name) }}"></div>
        <div class="field"><label class="required">Last name</label><input name="last_name" value="{{ old('last_name',$patient->last_name) }}" required></div>
        <div class="field"><label class="required" for="patient-birth-date">Date of birth</label><x-date-picker id="patient-birth-date" name="birth_date" :value="old('birth_date',$patient->birth_date?->format('Y-m-d'))" :max="now()->toDateString()" required placeholder="Choose date of birth" /></div>
        <div class="field"><label class="required">Sex</label><select name="sex" required><option value="">Select</option>@foreach(['Male','Female'] as $v)<option @selected(old('sex',$patient->sex)===$v)>{{ $v }}</option>@endforeach</select></div>
        </div>
    </section>
<section class="workflow-section">
        <x-workflow-heading step="2" title="Contact details" />
        <div class="form-grid">
        <div class="field"><label class="required">Contact number</label><input name="contact_number" value="{{ old('contact_number',$patient->contact_number) }}" required></div>
        <div class="field"><label class="required">Email address</label><input type="email" name="email" value="{{ old('email',$patient->email) }}" autocomplete="email" required><span class="help">Appointment confirmations and verification codes are sent here.</span></div>
        <div class="field full"><label class="required">Address</label><textarea name="address" required>{{ old('address',$patient->address) }}</textarea></div>
        </div>
    </section>
<section class="workflow-section">
        <x-workflow-heading step="3" title="Emergency contact" />
        <div class="form-grid">
        <div class="field"><label>Emergency contact</label><input name="emergency_contact" value="{{ old('emergency_contact',$patient->emergency_contact) }}"></div>
        <div class="field"><label>Emergency contact number</label><input name="emergency_contact_number" value="{{ old('emergency_contact_number',$patient->emergency_contact_number) }}"></div>
        </div>
    </section>
    <div class="workflow-footer">
    <div class="notice" ><strong>Check for an existing record.</strong><br><span class="help">Search first when names, email, or contact details may already be registered.</span></div>
    <div class="actions">
        <button class="btn btn-primary">Save patient</button>
        <a class="btn" href="{{ $patient->exists?route('patients.show',$patient):route('patients.index') }}">Cancel</a>
        @if(!$patient->exists)<a class="btn" href="{{ route('appointments.create') }}">Book appointment</a>@endif
    </div>
    </div>
</form>
@endsection
