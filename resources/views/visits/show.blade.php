@extends('layouts.app')
@section('title','Patient Visit')
@section('crumb','Patient Visit')

@section('content')
@php $role = auth()->user()->role; @endphp
<div class="title-row">
    <div><h1>Patient Visit</h1><p>{{ $visit->visit_number }} · {{ $visit->patient->full_name }}</p></div>
    <div class="toolbar">
        <x-section-return :href="route('visits.index')" label="patient visits" />
        <span class="badge badge-{{ str_replace(' ','-',$visit->status) }}">{{ $visit->status }}</span>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Current visit information</h2></div>
    <div class="panel-body record">
        <div><span>Patient</span><a class="btn btn-sm" href="{{ route('patients.show',$visit->patient) }}">{{ $visit->patient->full_name }} · {{ $visit->patient->patient_number }}</a></div>
        <div><span>Arrival</span>{{ $visit->arrived_at->format('d F Y g:i A') }}</div>
        <div><span>Physician</span>{{ $visit->physician->name }}</div>
        <div><span>Visit type</span><span class="badge badge-{{ $visit->visit_type }}">{{ $visit->visit_type }}</span></div>
        <div><span>Reason</span>{{ $visit->reason }}</div>
        <div><span>Appointment</span>{{ $visit->appointment?->appointment_number ?: 'No appointment (walk-in)' }}</div>
    </div>
</div>

@if($role === 'Nurse' && in_array($visit->status,['Waiting','Vital Signs Recorded'],true))
    @include('visits._vital-signs-form',['vitalVisit'=>$visit])
@elseif($role === 'Nurse')
    <div class="notice" style="margin-bottom:18px">
        <strong>Vital signs are locked.</strong><br>
        <span class="help">The physician has started or completed this consultation. Contact the administrator if a correction is required.</span>
    </div>
@endif

@if($visit->vitalSign && auth()->user()->hasRole('Nurse','Physician','Administrator'))
    <div class="panel">
        <div class="panel-head">
            <h2>Recorded vital signs</h2>
            <span class="help">{{ $role === 'Physician' ? 'Read only · ' : '' }}Recorded by {{ $visit->vitalSign->recorder->name }}</span>
        </div>
        <div class="panel-body record">
            <div><span>Blood pressure</span>{{ $visit->vitalSign->blood_pressure }} mmHg</div>
            <div><span>Temperature</span>{{ $visit->vitalSign->temperature }} °C</div>
            <div><span>Pulse</span>{{ $visit->vitalSign->pulse }} bpm</div>
            <div><span>Respiratory rate</span>{{ $visit->vitalSign->respiratory_rate }}/min</div>
            <div><span>Weight</span>{{ $visit->vitalSign->weight }} kg</div>
            <div><span>Height</span>{{ $visit->vitalSign->height }} cm</div>
            @if($visit->vitalSign->notes)<div><span>Nursing notes</span>{{ $visit->vitalSign->notes }}</div>@endif
        </div>
    </div>
@endif

@if($role === 'Physician' && !$visit->vitalSign)
    <div class="notice" style="margin-bottom:18px">
        <strong>Waiting for vital signs</strong><br>
        <span class="help">The nurse must record the patient’s vital signs before consultation can begin.</span>
    </div>
@elseif($role === 'Physician' && $visit->status !== 'Completed')
    <form id="consultation-form" method="POST" action="{{ route('visits.consultation',$visit) }}" class="panel workflow-card">
        @csrf
        <section class="workflow-section">
        <h2>Consultation</h2>
        <p class="help">Save an unfinished consultation as a draft. Chief complaint and assessment are required when completing it.</p>
        <x-workflow-heading step="1" title="Clinical assessment" />
        <div class="form-grid">
            <div class="field"><label class="required" for="chief-complaint">Chief complaint</label><textarea id="chief-complaint" name="chief_complaint" maxlength="2000" required>{{ old('chief_complaint',$visit->consultation?->chief_complaint) }}</textarea></div>
            <div class="field"><label class="required" for="assessment">Assessment / diagnosis</label><textarea id="assessment" name="assessment" maxlength="3000" required>{{ old('assessment',$visit->consultation?->assessment) }}</textarea></div>
        </div>
        </section>
        <section class="workflow-section">
        <x-workflow-heading step="2" title="Notes and recommendations" />
        <div class="form-grid">
            <div class="field"><label for="physician-notes">Physician notes</label><textarea id="physician-notes" name="notes" maxlength="5000">{{ old('notes',$visit->consultation?->notes) }}</textarea></div>
            <div class="field"><label for="recommendations">Recommendations</label><textarea id="recommendations" name="recommendations" maxlength="3000">{{ old('recommendations',$visit->consultation?->recommendations) }}</textarea></div>
        </div>
        </section>
        <div class="workflow-footer"><div class="actions">
            <button type="submit" class="btn" name="action" value="draft" formnovalidate data-validation-mode="draft" data-confirm="Save this consultation as a draft? You can continue it later from the consultation queue.">Save draft</button>
            <button type="submit" class="btn btn-primary" name="action" value="complete" data-confirm="Complete this visit? The visit and linked appointment will be marked Completed.">Complete consultation</button>
            <x-section-return :href="route('visits.index')" label="patient visits" />
        </div></div>
    </form>
@elseif(in_array($role,['Physician','Administrator'],true) && $visit->consultation)
    <div class="panel">
        <div class="panel-head"><h2>Consultation record</h2><span class="help">{{ $visit->status === 'Completed' ? 'Completed · Read only' : 'Draft' }}</span></div>
        <div class="panel-body record">
            <div><span>Chief complaint</span>{{ $visit->consultation->chief_complaint }}</div>
            <div><span>Assessment / diagnosis</span>{{ $visit->consultation->assessment }}</div>
            <div><span>Physician notes</span>{{ $visit->consultation->notes ?: '—' }}</div>
            <div><span>Recommendations</span>{{ $visit->consultation->recommendations ?: '—' }}</div>
        </div>
    </div>
    <div class="actions"><x-section-return class="btn-primary" :href="route('visits.index')" label="patient visits" /></div>
@endif

@if(auth()->user()->hasRole('Physician','Administrator'))
    <div class="panel">
        <div class="panel-head"><h2>Patient Visit History</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Physician</th><th>Type</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($visit->patient->visits as $past)
                        <tr><td><a class="btn btn-sm" href="{{ route('visits.show',$past) }}">{{ $past->arrived_at->format('d M Y') }}</a></td><td>{{ $past->physician->name }}</td><td>{{ $past->visit_type }}</td><td>{{ $past->status }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="empty">No previous visits.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
