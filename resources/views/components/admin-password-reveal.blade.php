@props(['user'])
@if(auth()->user()->hasRole('Administrator') && $user->exists)
<div class="admin-password-reveal" data-password-reveal data-url="{{ route('users.password.reveal',$user) }}" data-token="{{ csrf_token() }}">
    <div class="password-control">
        <input type="password" value="" placeholder="Saved password" readonly autocomplete="off" aria-label="Saved password for {{ $user->name }}" data-password-output>
        <button type="button" class="password-eye" data-password-reveal-toggle aria-label="Show saved password" aria-pressed="false" title="Show saved password" hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path class="password-eye-slash" d="m3 3 18 18"></path></svg>
        </button>
    </div>
    <p class="help" data-password-reveal-status role="status">Click the eye to show the latest saved password. It hides after 30 seconds.</p>
</div>
@endif
