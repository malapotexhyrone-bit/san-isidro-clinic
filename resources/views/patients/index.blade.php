@extends('layouts.app')
@section('title','Patients')
@section('crumb','Patients')
@section('content')
<div class="title-row">
    <div><h1>Patients</h1><p>Search before registering to help prevent duplicate patient records.</p></div>
    @if(auth()->user()->hasRole('Receptionist','Administrator'))<a class="btn btn-primary" href="{{ route('patients.create') }}">Register patient</a>@endif
</div>
<form class="toolbar panel-body panel" method="GET"><div class="field" style="flex:1"><label>Search by patient ID, name, or email</label><input name="search" value="{{ $search }}" placeholder="Enter ID, name, or email"></div><button class="btn btn-primary">Search</button>@if($search)<a class="btn" href="{{ route('patients.index') }}">Clear</a>@endif</form>
<div class="panel"><div class="table-wrap"><table><thead><tr><th>Patient ID</th><th>Name</th><th>Date of birth</th><th>Contact</th><th>Email</th><th>Action</th></tr></thead><tbody>
@forelse($patients as $p)<tr><td>{{ $p->patient_number }}</td><td>{{ $p->full_name }}</td><td>{{ $p->birth_date->format('d M Y') }}</td><td>{{ $p->contact_number }}</td><td>{{ $p->email?:'Not recorded' }}</td><td><a class="btn btn-sm" href="{{ route('patients.show',$p) }}">{{ auth()->user()->role==='Nurse'?'Open / Record vital signs':'Open' }}</a></td></tr>
@empty<tr><td colspan="6" class="empty">No matching patients found.</td></tr>@endforelse
</tbody></table></div></div>
{{ $patients->links('pagination::simple-default') }}
@endsection
