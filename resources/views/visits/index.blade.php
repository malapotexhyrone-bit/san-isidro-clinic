@extends('layouts.app')
@section('title','Patient Visits')
@section('crumb','Patient Visits')

@section('content')
@php $role = auth()->user()->role; @endphp
<div class="title-row">
    <div>
        <h1>{{ $role === 'Nurse' ? 'Vital Signs Queue' : ($role === 'Physician' ? 'Consultation Queue' : 'Patient Visits') }}</h1>
        <p>
            @if($role === 'Nurse')
                Record vital signs for registered patients after reception records their arrival.
            @elseif($role === 'Physician')
                Open assigned patients after their vital signs have been recorded.
            @else
                Review arrivals, walk-ins, vital signs, consultations, and completed visits.
            @endif
        </p>
    </div>
    @if(auth()->user()->hasRole('Receptionist','Administrator'))
        <a class="btn btn-primary" href="{{ route('walkins.create') }}">Record walk-in</a>
    @endif
</div>

<form class="panel panel-body toolbar" method="GET">
    <div class="field"><label for="visit-date">Visit date</label><x-date-picker id="visit-date" name="date" :value="request('date')" placeholder="Choose visit date" /></div>
    <button class="btn btn-primary">Filter</button>
    <a class="btn" href="{{ route('visits.index') }}">Clear</a>
</form>

@if($role === 'Nurse')
    <div class="notice" style="margin-bottom:18px">
        <strong>Nursing workflow</strong><br>
        <span class="help">Open a patient marked “Vital signs needed,” complete every required measurement, and save. The physician will then see the measurements and may begin consultation.</span>
    </div>
@endif

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Arrival</th><th>Patient</th><th>Physician</th><th>Type</th><th>Stage</th><th>Vital signs</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($visits as $visit)
                    <tr>
                        <td>{{ $visit->arrived_at->format('d M Y g:i A') }}</td>
                        <td>{{ $visit->patient->full_name }}<div class="help">{{ $visit->patient->patient_number }}</div></td>
                        <td>{{ $visit->physician->name }}</td>
                        <td><span class="badge badge-{{ $visit->visit_type }}">{{ $visit->visit_type }}</span></td>
                        <td><span class="badge badge-{{ str_replace(' ','-',$visit->status) }}">{{ $visit->status }}</span></td>
                        <td>
                            <span class="badge badge-{{ $visit->vitalSign ? 'Completed' : 'No-show' }}">
                                {{ $visit->vitalSign ? 'Recorded' : 'Needed' }}
                            </span>
                        </td>
                        <td>
                            @if($role === 'Nurse' && !$visit->vitalSign && $visit->status === 'Waiting')
                                <a class="btn btn-primary btn-sm" href="{{ route('visits.show',$visit) }}">Record vital signs</a>
                            @elseif($role === 'Nurse' && $visit->status === 'Vital Signs Recorded')
                                <a class="btn btn-sm" href="{{ route('visits.show',$visit) }}">Review vital signs</a>
                            @elseif($role === 'Physician' && !$visit->vitalSign)
                                <span class="help">Waiting for nurse</span>
                            @elseif($role === 'Physician')
                                <a class="btn btn-primary btn-sm" href="{{ route('visits.show',$visit) }}">{{ $visit->status === 'Completed' ? 'View completed record' : ($visit->status === 'In Consultation' ? 'Resume draft' : 'Open consultation') }}</a>
                            @else
                                <a class="btn btn-sm" href="{{ route('visits.show',$visit) }}">Open visit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No patient visits found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $visits->links('pagination::simple-default') }}
@endsection
