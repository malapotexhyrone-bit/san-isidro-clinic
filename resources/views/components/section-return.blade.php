@props(['href', 'label'])

<a {{ $attributes->class(['btn', 'section-return-button']) }} href="{{ $href }}">
    <span aria-hidden="true">←</span>
    <span>Return to {{ $label }}</span>
</a>
