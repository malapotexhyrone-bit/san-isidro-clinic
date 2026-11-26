@extends('layouts.app')
@section('title','Appointments')
@section('crumb','Appointments')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/needs-review.css') }}">
@endpush
@section('content')
@php
    $isPhysicianView = auth()->user()->role === 'Physician';
    $usesReviewOverview = $period === 'needs-review' && !$isPhysicianView;
    $requestedReviewPhysician = $usesReviewOverview && request()->integer('review_physician')
        ? $physicians->firstWhere('id', request()->integer('review_physician'))
        : null;
    $requestedReviewActionCount = $requestedReviewPhysician
        ? $appointmentsByPhysician->get($requestedReviewPhysician->id, collect())->whereIn('status', ['Scheduled', 'Rescheduled'])->count()
        : 0;
    $reviewPhysician = $requestedReviewActionCount > 0 ? $requestedReviewPhysician : null;
    $displayPhysicians = $reviewPhysician ? collect([$reviewPhysician]) : $physicians;
    $periodDetails = [
        'needs-review' => [
            'label' => 'Needs Review',
            'description' => 'Earlier appointment records. Unresolved bookings should be rescheduled or marked No-show.',
        ],
        'clinic-day' => [
            'label' => 'Clinic Day',
            'description' => now()->format('l, d F Y').' · Current clinic schedule and physician availability.',
        ],
        'upcoming' => [
            'label' => 'Upcoming Schedule',
            'description' => 'Appointments scheduled from '.now()->addDay()->format('d F Y').' onward.',
        ],
    ];
@endphp

@unless($usesReviewOverview && $reviewPhysician)
<div class="title-row">
    <div><h1>{{ $isPhysicianView ? 'My Appointments' : 'Appointments' }}</h1><p>{{ $periodDetails[$period]['description'] }}</p></div>
    @unless($isPhysicianView)
        <div class="toolbar"><a class="btn btn-primary" href="{{ route('appointments.create') }}">New appointment</a><a class="btn" href="{{ route('walkins.create') }}">Record walk-in</a></div>
    @endunless
</div>

<div class="toolbar" style="margin-bottom:18px">
    @foreach($periodDetails as $key => $details)
        <a class="btn {{ $period===$key?'btn-primary':'' }}" href="{{ route('appointments.index',array_filter(['period'=>$key,'status'=>$status,'physician'=>request('physician')])) }}">
            {{ $details['label'] }} <span class="badge" style="margin-left:4px">{{ $periodCounts[$key] }}</span>
        </a>
    @endforeach
</div>
@endunless

@if($usesReviewOverview && !$reviewPhysician)
    <section class="review-overview" aria-labelledby="review-overview-title">
        <div class="review-overview-heading">
            <div>
                <span class="review-eyebrow">Physician review queue</span>
                <h2 id="review-overview-title">Choose a physician</h2>
                <p>Open one queue at a time to review unresolved reservations.</p>
            </div>
            <span class="review-total">{{ $periodCounts['needs-review'] }} earlier reservations</span>
        </div>
        <div class="review-physician-grid">
            @forelse($physicians as $physician)
                @php
                    $doctorAppointments = $appointmentsByPhysician->get($physician->id, collect());
                    $needsActionCount = $doctorAppointments->whereIn('status', ['Scheduled', 'Rescheduled'])->count();
                @endphp
                <article class="review-physician-card" data-review-physician="{{ $physician->id }}">
                    <div>
                        <span class="review-card-label">Physician</span>
                        <h3>{{ $physician->name }}</h3>
                        <p>{{ $physician->specialization }}</p>
                    </div>
                    <div class="review-card-count {{ $needsActionCount > 0 ? 'has-actions' : 'is-clear' }}">
                        <strong>{{ $needsActionCount }}</strong>
                        <span>{{ \Illuminate\Support\Str::plural('reservation', $needsActionCount) }} need action</span>
                    </div>
                    @if($needsActionCount > 0)
                        <a class="btn btn-primary" href="{{ route('appointments.index', ['period' => 'needs-review', 'review_physician' => $physician->id]) }}">View reservations</a>
                    @else
                        <button class="btn review-disabled-button" type="button" disabled aria-disabled="true">No reservations need action</button>
                    @endif
                </article>
            @empty
                <div class="panel panel-body empty">No physician has earlier reservations to review.</div>
            @endforelse
        </div>
    </section>
@else
@if($usesReviewOverview && $reviewPhysician)
    <section class="review-detail-heading" aria-labelledby="review-detail-title">
        <div class="review-detail-copy">
            <span class="review-eyebrow">Unprocessed appointment review</span>
            <div class="review-detail-title-row">
                <h2 id="review-detail-title">{{ $reviewPhysician->name }}</h2>
                <span class="review-detail-count">{{ $requestedReviewActionCount }} {{ Str::plural('appointment', $requestedReviewActionCount) }} need action</span>
            </div>
            <p>{{ $reviewPhysician->specialization }} · Resolve the red unprocessed reservations below by rescheduling them or marking them No-show.</p>
        </div>
        <a class="btn" href="{{ route('appointments.index', ['period' => 'needs-review']) }}">Return to physicians</a>
    </section>
@endif

@unless($usesReviewOverview)
<form class="panel panel-body toolbar" method="GET">
    <input type="hidden" name="period" value="{{ $period }}">
    @unless($isPhysicianView)
        <div class="field">
            <label>Physician</label>
            <select name="physician">
                <option value="">All physicians</option>
                @foreach($physicianOptions as $option)
                    <option value="{{ $option->id }}" @selected((string)request('physician')===(string)$option->id)>{{ $option->name }} — {{ $option->specialization }}{{ $option->is_active?'':' (Inactive)' }}</option>
                @endforeach
            </select>
        </div>
    @endunless
    <div class="field">
        <label>Status</label>
        <select name="status"><option value="">All statuses</option>@foreach(\App\Models\Appointment::STATUSES as $option)<option @selected($status===$option)>{{ $option }}</option>@endforeach</select>
    </div>
    <button class="btn btn-primary">Apply filters</button>
    <a class="btn" href="{{ route('appointments.index',['period'=>$period]) }}">Clear filters</a>
</form>
@endunless

@forelse($displayPhysicians as $physician)
    @php
        $doctorAppointments = $appointmentsByPhysician->get($physician->id,collect());
        $physicianAvailability = $availability->get($physician->id,['scheduled'=>false,'open_slots'=>0]);
        $unresolved = $doctorAppointments->whereIn('status',['Scheduled','Rescheduled'])->count();
    @endphp
    <div class="panel">
        @unless($usesReviewOverview)
        <div class="panel-head">
            <div>
                <h2>{{ $physician->name }}</h2>
                <span class="help">{{ $physician->specialization }} · {{ $doctorAppointments->count() }} {{ \Illuminate\Support\Str::plural('appointment',$doctorAppointments->count()) }} in this view</span>
            </div>
            <div class="toolbar">
                @if($period==='clinic-day')
                    @if(!$physicianAvailability['scheduled'])
                        <span class="badge">Not scheduled</span>
                    @elseif($physicianAvailability['open_slots'] > 0)
                        <span class="badge badge-Completed">Available · {{ $physicianAvailability['open_slots'] }} open {{ \Illuminate\Support\Str::plural('slot',$physicianAvailability['open_slots']) }}</span>
                    @else
                        <span class="badge badge-No-show">Fully booked</span>
                    @endif
                @elseif($period==='needs-review' && $unresolved)
                    <span class="badge badge-No-show">{{ $unresolved }} {{ \Illuminate\Support\Str::plural('booking',$unresolved) }} need action</span>
                @endif
                @if(!$physician->is_active)<span class="badge badge-Inactive">Inactive physician</span>@endif
                <a class="btn btn-sm" href="{{ route('schedules.index') }}">View schedule</a>
            </div>
        </div>
        @endunless
        <div class="table-wrap">
            <table>
                <thead><tr><th>Appointment ID</th><th>Date</th><th>Time</th><th>Patient</th><th>Status</th><th>Verification</th><th>{{ $isPhysicianView ? 'Access' : 'Action' }}</th></tr></thead>
                <tbody>
                @forelse($doctorAppointments as $appointment)
                    @php $needsAction = $period==='needs-review' && in_array($appointment->status,['Scheduled','Rescheduled'],true); @endphp
                    <tr>
                        <td>{{ $appointment->appointment_number }}</td>
                        <td>{{ $appointment->appointment_date->format('d M Y') }}</td>
                        <td>{{ date('g:i A',strtotime($appointment->appointment_time)) }}</td>
                        <td class="appointment-patient-name"><a href="{{ route('patients.show',$appointment->patient) }}">{{ $appointment->patient->full_name }}</a></td>
                        <td>
                            @if($needsAction)
                                <span class="review-status review-status-warning">Needs action</span>
                            @elseif($appointment->status === 'Completed')
                                <span class="review-status review-status-success">Completed</span>
                            @else
                                <span class="badge badge-{{ str_replace(' ','-',$appointment->status) }}">{{ $appointment->status }}</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ $appointment->verified_at?'Completed':'No-show' }}">{{ $appointment->verified_at?'Verified':'Unverified' }}</span></td>
                        <td>
                            @if($isPhysicianView)
                                <a class="btn btn-sm" href="{{ route('patients.show',$appointment->patient) }}">Open patient</a>
                                <div class="help">Read-only schedule</div>
                            @elseif($needsAction)
                                <div class="toolbar">
                                    <a class="btn btn-sm" href="{{ route('appointments.show',$appointment) }}#reschedule">Reschedule</a>
                                    <form method="POST" action="{{ route('appointments.no-show',$appointment) }}" data-confirm="Mark {{ $appointment->patient->full_name }} as No-show for appointment {{ $appointment->appointment_number }}?">@csrf @method('PATCH')<button class="btn btn-sm">Mark No-show</button></form>
                                </div>
                            @else
                                <a class="btn btn-sm" href="{{ route('appointments.show',$appointment) }}{{ $appointment->status==='Scheduled'&&!$appointment->verified_at?'#verification':'' }}">{{ $appointment->status==='Scheduled'&&!$appointment->verified_at?'Verify / Manage':'Manage' }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No appointments for this physician in {{ strtolower($periodDetails[$period]['label']) }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="panel panel-body empty">No appointments match this schedule group and the selected filters.</div>
@endforelse
@endif
@endsection
