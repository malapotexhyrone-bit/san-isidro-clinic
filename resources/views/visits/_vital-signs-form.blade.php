<form method="POST" action="{{ route('visits.vital-signs',$vitalVisit) }}" class="panel workflow-card" data-confirm="Save these vital signs for {{ $vitalVisit->patient->full_name }} and make them available to {{ $vitalVisit->physician->name }}?">
    @csrf
    <section class="workflow-section">
    <h2>{{ $vitalVisit->vitalSign ? 'Review or correct vital signs' : 'Record vital signs' }}</h2>
    <p class="subtitle" style="margin-bottom:16px">
        Current visit: {{ $vitalVisit->visit_number }} · {{ $vitalVisit->physician->name }} · {{ $vitalVisit->arrived_at->format('d M Y g:i A') }}
    </p>
    <x-workflow-heading step="1" title="Measurements" />
    <div class="form-grid">
        <div class="field"><label class="required">Blood pressure (mmHg)</label><input name="blood_pressure" value="{{ old('blood_pressure',$vitalVisit->vitalSign?->blood_pressure) }}" placeholder="120/80" required></div>
        <div class="field"><label class="required">Temperature (°C)</label><input type="number" step="0.1" name="temperature" value="{{ old('temperature',$vitalVisit->vitalSign?->temperature) }}" required></div>
        <div class="field"><label class="required">Pulse (bpm)</label><input type="number" name="pulse" value="{{ old('pulse',$vitalVisit->vitalSign?->pulse) }}" required></div>
        <div class="field"><label class="required">Respiratory rate (breaths/min)</label><input type="number" name="respiratory_rate" value="{{ old('respiratory_rate',$vitalVisit->vitalSign?->respiratory_rate) }}" required></div>
        <div class="field"><label class="required">Weight (kg)</label><input type="number" step="0.01" name="weight" value="{{ old('weight',$vitalVisit->vitalSign?->weight) }}" required></div>
        <div class="field"><label class="required">Height (cm)</label><input type="number" step="0.01" name="height" value="{{ old('height',$vitalVisit->vitalSign?->height) }}" required></div>
    </div>
    </section>
    <section class="workflow-section">
        <x-workflow-heading step="2" title="Nursing notes" />
        <div class="field full"><label>Nursing notes</label><textarea name="notes">{{ old('notes',$vitalVisit->vitalSign?->notes) }}</textarea></div>
    </section>
    <div class="workflow-footer"><div class="actions"><button class="btn btn-primary">Save vital signs</button></div></div>
</form>
