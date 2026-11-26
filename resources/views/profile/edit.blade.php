@extends('layouts.app')
@section('title','Edit Profile')
@section('crumb','Edit Profile')
@section('content')
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn($part) => strtoupper(substr($part, 0, 1)))->join('');
    $interactiveProfileSlug = match ($user->username) {
        'physician' => 'doctor1',
        'physician2' => 'doctor2',
        default => null,
    };
    $usesInteractiveProfile = filled($interactiveProfileSlug) && blank($user->profile_photo_path);
@endphp

<div class="profile-page">
    <div class="title-row profile-edit-title">
        <div><p class="profile-eyebrow">Personal settings</p><h1>Edit your profile</h1><p>Keep your staff information current and your account secure.</p></div>
        <x-section-return :href="route('profile.show')" label="profile" />
    </div>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-confirm="Save these profile changes? Please confirm that your information is correct.">
        @csrf
        @method('PUT')
        <div class="profile-edit-layout">
            <aside class="profile-card profile-photo-editor">
                <div id="profile-photo-preview" class="profile-portrait profile-portrait-large {{ $usesInteractiveProfile ? 'interactive-profile' : '' }}">
                    @if($user->profile_photo_path)
                        <img src="{{ route('profile.photo',$user) }}" alt="Current profile picture">
                    @elseif($usesInteractiveProfile)
                        <img class="profile-state profile-state-light profile-state-normal" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-formal-light.jpeg') }}" alt="">
                        <img class="profile-state profile-state-light profile-state-hover" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-shy-light.jpeg') }}" alt="">
                        <img class="profile-state profile-state-dark profile-state-normal" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-formal-dark.jpeg') }}" alt="">
                        <img class="profile-state profile-state-dark profile-state-hover" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-shy-dark.jpeg') }}" alt="">
                    @else
                        <span>{{ $initials ?: 'SI' }}</span>
                    @endif
                </div>
                <h2>Profile picture</h2>
                <p>Use a clear JPG, PNG, or WEBP image up to 2 MB.</p>
                <label class="btn profile-upload-button" for="profile_photo">Choose picture</label>
                <input class="profile-file-input" id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                @if($user->profile_photo_path)
                    <label class="profile-remove-option"><input type="checkbox" name="remove_profile_photo" value="1"> Remove current picture</label>
                @endif

            </aside>

            <div class="profile-edit-stack">
                <section class="profile-card profile-form-card">
                    <div class="profile-card-heading">
                        <div><x-workflow-heading step="1" title="Personal information" /><p>Fields marked with an asterisk are required.</p></div>
                    </div>
                    <div class="form-grid">
                        <div class="field"><label class="required" for="name">Full name</label><input id="name" name="name" value="{{ old('name',$user->name) }}" required></div>
                        <div class="field"><label for="username">Username</label><input id="username" value="{{ $user->username }}" readonly><span class="help">The administrator manages usernames.</span></div>
                        <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email',$user->email) }}" placeholder="name@example.com"></div>
                        <div class="field"><label for="birthdate">Birthdate</label><x-date-picker id="birthdate" name="birthdate" :value="old('birthdate',$user->birthdate?->format('Y-m-d'))" :max="now()->toDateString()" placeholder="Choose your birthdate" /><span class="help">Your age is calculated automatically.</span></div>
                        <div class="field full"><label for="address">Home address</label><textarea id="address" name="address" rows="3" placeholder="House number, street, barangay, city, province">{{ old('address',$user->address) }}</textarea></div>
                    </div>
                </section>

                <section class="profile-card profile-form-card">
                    <div class="profile-card-heading">
                        <div><x-workflow-heading step="2" title="Role and access" /><p>Assigned by the clinic administrator.</p></div>
                    </div>
                    <div class="profile-readonly-grid">
                        <div><span>Role</span><strong>{{ $user->role }}</strong></div>
                        <div><span>Access</span><p>{{ $user->accessDescription() }}</p></div>
                    </div>
                </section>

                <section id="security" class="profile-card profile-form-card">
                    <div class="profile-card-heading">
                        <div><x-workflow-heading step="3" title="Change password" /><p>Leave these fields blank to keep your current password.</p></div>
                    </div>
                    <div class="form-grid">
                        <div class="field full"><label for="current_password">Current password</label><x-password-input id="current_password" name="current_password" autocomplete="current-password" /></div>
                        <div class="field"><label for="password">New password</label><x-password-input id="password" name="password" minlength="8" /></div>
                        <div class="field"><label for="password_confirmation">Confirm new password</label><x-password-input id="password_confirmation" name="password_confirmation" minlength="8" /></div>
                    </div>
                    <p class="profile-security-note">Enter your current password to change it. Use the eye buttons to check what you typed. Administrators can view saved passwords for account support.</p>
                </section>

                <div class="profile-save-bar">
                    <a class="btn" href="{{ route('profile.show') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save profile</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('profile-photo-preview');
    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;
        const image = document.createElement('img');
        image.alt = 'Selected profile picture preview';
        image.src = URL.createObjectURL(file);
        preview.replaceChildren(image);
    });
})();
</script>
@endsection
