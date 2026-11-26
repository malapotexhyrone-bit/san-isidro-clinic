@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="title-row">
    <div><h1>Dashboard</h1><p>{{ now()->format('l, d F Y') }} · Today's clinic activity</p></div>
    <div class="toolbar">@if(auth()->user()->hasRole('Receptionist','Administrator'))<a class="btn btn-primary" href="{{ route('appointments.create') }}">New appointment</a><a class="btn" href="{{ route('patients.create') }}">Register patient</a><a class="btn" href="{{ route('walkins.create') }}">Record walk-in</a>@endif</div>
</div>
<div class="grid stats"><div class="stat"><span>Scheduled today</span><strong>{{ $stats['scheduled'] }}</strong></div><div class="stat"><span>Waiting</span><strong>{{ $stats['waiting'] }}</strong></div><div class="stat"><span>Completed</span><strong>{{ $stats['completed'] }}</strong></div><div class="stat"><span>Walk-ins</span><strong>{{ $stats['walkins'] }}</strong></div><div class="stat"><span>Active physicians</span><strong>{{ $stats['physicians'] }}</strong></div></div>
<div class="panel">
    <div class="panel-head"><h2>Today's appointments</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Appointment ID</th><th>Time</th><th>Patient</th><th>Physician</th><th>Status</th><th>Verification</th><th>Action</th></tr></thead><tbody>
    @forelse($appointments as $a)
    <tr>
        <td>{{ $a->appointment_number }}</td><td>{{ date('g:i A',strtotime($a->appointment_time)) }}</td><td>{{ $a->patient->full_name }}</td><td>{{ $a->physician->name }}</td><td><span class="badge badge-{{ str_replace(' ','-',$a->status) }}">{{ $a->status }}</span></td><td><span class="badge badge-{{ $a->verified_at?'Completed':'No-show' }}">{{ $a->verified_at?'Verified':'Unverified' }}</span></td>
        <td>@if(auth()->user()->hasRole('Receptionist','Administrator'))<a class="btn btn-sm" href="{{ route('appointments.show',$a) }}{{ $a->status==='Scheduled'&&!$a->verified_at?'#verification':'' }}">{{ $a->status==='Scheduled'&&!$a->verified_at?'Verify / Manage':'Manage' }}</a>@else<a class="btn btn-sm" href="{{ route('patients.show',$a->patient) }}">Open patient</a>@endif</td>
    </tr>
    @empty<tr><td colspan="7" class="empty">No appointments for today.</td></tr>@endforelse
    </tbody></table></div>
</div>
<div class="panel"><div class="panel-head"><h2>Today's patient visits</h2></div><div class="table-wrap"><table><thead><tr><th>Arrival</th><th>Patient</th><th>Type</th><th>Physician</th><th>Stage</th><th></th></tr></thead><tbody>@forelse($visits as $v)<tr><td>{{ $v->arrived_at->format('g:i A') }}</td><td>{{ $v->patient->full_name }}</td><td><span class="badge badge-{{ $v->visit_type }}">{{ $v->visit_type }}</span></td><td>{{ $v->physician->name }}</td><td><span class="badge badge-{{ str_replace(' ','-',$v->status) }}">{{ $v->status }}</span></td><td><a class="btn btn-sm" href="{{ route('visits.show',$v) }}">Open visit</a></td></tr>@empty<tr><td colspan="6" class="empty">No visits recorded today.</td></tr>@endforelse</tbody></table></div></div>
@endsection
