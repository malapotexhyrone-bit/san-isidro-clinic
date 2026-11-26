@props([
    'name',
    'id' => null,
    'value' => null,
    'min' => null,
    'max' => null,
    'required' => false,
    'placeholder' => 'Choose a date',
])
@php
    $pickerId = $id ?: 'date-picker-'.str_replace(['[', ']'], '-', $name);
@endphp
<div
    class="clinic-date-picker"
    data-date-picker
    data-date-min="{{ $min }}"
    data-date-max="{{ $max }}"
    data-date-required="{{ $required ? 'true' : 'false' }}"
>
    <input id="{{ $pickerId }}-value" type="hidden" name="{{ $name }}" value="{{ $value }}">
    <button
        id="{{ $pickerId }}"
        class="clinic-date-trigger"
        type="button"
        aria-haspopup="dialog"
        aria-expanded="false"
        @if($required) aria-required="true" @endif
    >
        <span class="clinic-date-trigger-value" data-date-label>{{ $value ?: $placeholder }}</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 10h18"></path></svg>
    </button>
    <div class="clinic-date-popover" role="dialog" aria-modal="false" aria-label="Choose a date" hidden>
        <div class="clinic-date-header">
            <button type="button" data-calendar-previous aria-label="Previous month">‹</button>
            <strong data-calendar-title></strong>
            <button type="button" data-calendar-next aria-label="Next month">›</button>
        </div>
        <div class="clinic-date-weekdays" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
        <div class="clinic-date-days" data-calendar-days></div>
        <div class="clinic-date-actions">
            <button type="button" data-calendar-today>Today</button>
            @unless($required)<button type="button" data-calendar-clear>Clear</button>@endunless
        </div>
    </div>
</div>
