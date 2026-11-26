@extends('layouts.app')
@section('title','Patient Record')
@section('crumb','Patient Record')

@section('content')
<div class="title-row">
    <div><h1>{{ $patient->full_name }}</h1><p>{{ $patient->patient_number }} · Patient record and visit history</p></div>
    <div class="toolbar">
        <x-section-return :href="route('patients.index')" label="patients" />
        @if(auth()->user()->hasRole('Receptionist','Administrator'))
            <a class="btn" href="{{ route('patients.edit',$patient) }}">Edit patient</a>
            <a class="btn btn-primary" href="{{ route('appointments.create',['patient'=>$patient->id]) }}">Book appointment</a>
        @endif
        @if(auth()->user()->role === 'Administrator')
            <form class="inline-form" method="POST" action="{{ route('patients.destroy',$patient) }}" data-confirm="Permanently delete {{ $patient->full_name }}? This is allowed only when the patient has no appointment or visit history. This cannot be undone.">
                @csrf @method('DELETE')
                <button class="btn btn-danger" type="submit">Delete patient</button>
            </form>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Patient information</h2></div>
    <div class="panel-body record">
        <div><span>Date of birth</span>{{ $patient->birth_date->format('d F Y') }}</div>
        <div><span>Sex</span>{{ $patient->sex }}</div>
        <div><span>Contact</span>{{ $patient->contact_number }}</div>
        <div><span>Email</span>{{ $patient->email ?: 'Not yet recorded' }}</div>
        <div><span>Address</span>{{ $patient->address }}</div>
        <div><span>Emergency contact</span>{{ $patient->emergency_contact ?: '—' }}</div>
        <div><span>Emergency number</span>{{ $patient->emergency_contact_number ?: '—' }}</div>
    </div>
</div>

@if(auth()->user()->hasRole('Nurse','Physician','Administrator'))
    @php
        $vitalSignRecords = $patient->visits
            ->filter(fn($visit) => $visit->vitalSign)
            ->sortByDesc('arrived_at');
        $vitalSignsVisit = $patient->visits
            ->filter(fn($visit) => in_array($visit->status,['Waiting','Vital Signs Recorded'],true))
            ->sortByDesc('arrived_at')
            ->first();
    @endphp
    @if(auth()->user()->role === 'Nurse' && $vitalSignsVisit)
        @include('visits._vital-signs-form',['vitalVisit'=>$vitalSignsVisit])
    @elseif(auth()->user()->role === 'Nurse')
        <div class="notice" style="margin-bottom:18px">
            <strong>No active visit is ready for vital signs.</strong><br>
            <span class="help">Reception must first mark the scheduled appointment as Arrived or record the patient as a walk-in.</span>
        </div>
    @endif
    <div class="panel">
        <div class="panel-head">
            <h2>Vital Signs History</h2>
            <span class="help">Measurements recorded by the nurse for this patient</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>Physician</th><th>Blood pressure</th><th>Temperature</th><th>Pulse</th><th>Respiratory rate</th><th>Weight</th><th>Height</th><th>Recorded by</th></tr>
                </thead>
                <tbody>
                    @forelse($vitalSignRecords as $visit)
                        <tr>
                            <td><a class="btn btn-sm" href="{{ route('visits.show',$visit) }}">{{ $visit->arrived_at->format('d M Y g:i A') }}</a></td>
                            <td>{{ $visit->physician->name }}</td>
                            <td>{{ $visit->vitalSign->blood_pressure }} mmHg</td>
                            <td>{{ $visit->vitalSign->temperature }} °C</td>
                            <td>{{ $visit->vitalSign->pulse }} bpm</td>
                            <td>{{ $visit->vitalSign->respiratory_rate }}/min</td>
                            <td>{{ $visit->vitalSign->weight }} kg</td>
                            <td>{{ $visit->vitalSign->height }} cm</td>
                            <td>{{ $visit->vitalSign->recorder?->name ?: 'Nurse' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty">No vital signs have been recorded for this patient.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="panel">
    <div class="panel-head"><h2>Visit history</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Visit date</th><th>Physician</th><th>Visit type</th><th>Status</th><th>Summary</th></tr></thead>
            <tbody>
                @forelse($patient->visits as $visit)
                    <tr>
                        <td><a class="btn btn-sm" href="{{ route('visits.show',$visit) }}">{{ $visit->arrived_at->format('d M Y g:i A') }}</a></td>
                        <td>{{ $visit->physician->name }}</td>
                        <td>{{ $visit->visit_type }}</td>
                        <td>{{ $visit->status }}</td>
                        <td>
                            {{ auth()->user()->hasRole('Physician','Administrator')
                                ? ($visit->consultation?->assessment ?: ($visit->vitalSign ? 'Vital signs recorded' : 'Awaiting clinical record'))
                                : ($visit->vitalSign ? 'Vital signs recorded' : $visit->status) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No previous visits.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@unless(auth()->user()->role === 'Nurse')
    <div class="panel">
        <div class="panel-head"><h2>Appointment history</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Appointment ID</th><th>Date and time</th><th>Physician</th><th>Reason</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($patient->appointments->sortByDesc('appointment_date') as $appointment)
                        <tr>
                            <td>
                                @if(auth()->user()->hasRole('Receptionist','Administrator'))
                                    <a class="btn btn-sm" href="{{ route('appointments.show',$appointment) }}">{{ $appointment->appointment_number }}</a>
                                @else
                                    {{ $appointment->appointment_number }}
                                @endif
                            </td>
                            <td>{{ $appointment->appointment_date->format('d M Y') }} {{ date('g:i A',strtotime($appointment->appointment_time)) }}</td>
                            <td>{{ $appointment->physician->name }}</td>
                            <td>{{ $appointment->reason }}</td>
                            <td><span class="badge badge-{{ str_replace(' ','-',$appointment->status) }}">{{ $appointment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty">No appointment records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endunless
@endsection
