@if(session('success'))
@php
    $queueName = match(auth()->user()->role) {
        'Physician' => 'consultation queue',
        'Nurse' => 'vital signs queue',
        default => 'patient visits',
    };
@endphp
<dialog id="success-modal" class="success-dialog" open aria-labelledby="success-title" aria-describedby="success-message success-next">
    <div class="success-dialog-body">
        <span class="success-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg></span>
        <p class="success-kicker">Action completed</p>
        <h2 id="success-title">Successful</h2>
        <p id="success-message" class="success-message">{{ session('success') }}</p>
        @if(session('warning'))
            <div class="success-warning"><strong>Please note</strong><p>{{ session('warning') }}</p></div>
        @endif
        <p id="success-next" class="success-next">Select Okay to return to the {{ $queueName }}.</p>
        <a id="success-continue" class="btn btn-primary" href="{{ route('visits.index') }}" autofocus>Okay</a>
    </div>
</dialog>
@endif
