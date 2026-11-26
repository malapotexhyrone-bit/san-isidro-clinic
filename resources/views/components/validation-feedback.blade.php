@if($errors->any())
<div class="alert alert-error validation-summary" data-validation-summary role="alert" tabindex="-1">
    <strong>Please correct the highlighted information.</strong>
    <ul class="errors">
        @foreach($errors->getMessages() as $field => $messages)
            @foreach($messages as $message)<li data-validation-key="{{ $field }}">{{ $message }}</li>@endforeach
        @endforeach
    </ul>
</div>
<script id="validation-errors" type="application/json" data-validation-scope="{{ old('_validation_form') }}">@json($errors->getMessages())</script>
@endif
