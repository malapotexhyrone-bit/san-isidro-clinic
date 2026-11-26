@props(['id', 'name', 'autocomplete' => 'new-password'])
<div class="password-control" data-password-control>
    <input id="{{ $id }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}" {{ $attributes }}>
    <button type="button" class="password-eye" data-password-toggle aria-controls="{{ $id }}" aria-label="Show password" aria-pressed="false" title="Show password" hidden>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path class="password-eye-slash" d="m3 3 18 18"></path></svg>
    </button>
</div>
