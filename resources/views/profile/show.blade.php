@extends('layouts.app')
@php
    $isAdminView = $isAdminView ?? false;
@endphp
@section('title',$isAdminView?'Staff Profile':'My Profile')
@section('crumb',$isAdminView?'Staff Profile':'My Profile')
@section('content')
@php
    $age = $user->birthdate?->age;
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn($part) => strtoupper(substr($part, 0, 1)))->join('');
    $interactiveProfileSlug = match ($user->username) {
        'physician' => 'doctor1',
        'physician2' => 'doctor2',
        default => null,
    };
    $usesInteractiveProfile = filled($interactiveProfileSlug) && blank($user->profile_photo_path);
@endphp

<div class="profile-page">
    <section class="profile-hero">
        <div class="profile-portrait {{ $usesInteractiveProfile ? 'interactive-profile' : '' }}" aria-label="Profile picture for {{ $user->name }}">
            @if($user->profile_photo_path)
                <img src="{{ route('profile.photo',$user) }}" alt="Profile picture of {{ $user->name }}">
            @elseif($usesInteractiveProfile)
                <img class="profile-state profile-state-light profile-state-normal" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-formal-light.jpeg') }}" alt="">
                <img class="profile-state profile-state-light profile-state-hover" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-shy-light.jpeg') }}" alt="">
                <img class="profile-state profile-state-dark profile-state-normal" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-formal-dark.jpeg') }}" alt="">
                <img class="profile-state profile-state-dark profile-state-hover" src="{{ asset('images/profiles/'.$interactiveProfileSlug.'-shy-dark.jpeg') }}" alt="">
            @else
                <span>{{ $initials ?: 'SI' }}</span>
            @endif
            <i class="profile-online" title="Active account"></i>
        </div>
        <div class="profile-identity">
            <p class="profile-eyebrow">San Isidro staff profile</p>
            <h1>{{ $user->name }}</h1>
            <div class="profile-badges">
                <span class="profile-role-badge">{{ $user->role }}</span>
                <span class="profile-status-badge"><i></i>{{ $user->is_active ? 'Active account' : 'Inactive account' }}</span>
            </div>
        </div>
        <a class="btn btn-primary profile-edit-button" href="{{ $isAdminView ? route('users.edit',$user) : route('profile.edit') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 16-.8 4.8L8 20l10.6-10.6a2 2 0 0 0-4-4Z"></path><path d="m13.5 6.5 4 4"></path></svg>
            {{ $isAdminView ? 'Edit staff profile' : 'Edit profile' }}
        </a>
    </section>

    <div class="profile-layout">
        <section class="profile-card profile-details-card">
            <div class="profile-card-heading">
                <span class="profile-heading-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg></span>
                <div><h2>Personal information</h2><p>Your identity and contact details.</p></div>
            </div>
            <dl class="profile-info-grid">
                <div><dt>Full name</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>Username</dt><dd>{{ $user->username }}</dd></div>
                <div><dt>Email address</dt><dd>{{ $user->email ?: 'Not provided yet' }}</dd></div>
                <div><dt>Age</dt><dd>{{ $age !== null ? $age.' years old' : 'Add your birthdate' }}</dd></div>
                <div><dt>Birthdate</dt><dd>{{ $user->birthdate?->format('F j, Y') ?: 'Not provided yet' }}</dd></div>
                <div class="profile-info-full"><dt>Home address</dt><dd>{{ $user->address ?: 'Not provided yet' }}</dd></div>
            </dl>
        </section>

        <aside class="profile-side-stack">
            <section class="profile-card profile-access-card">
                <div class="profile-card-heading">
                    <span class="profile-heading-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.7 3.2 8 7.5 9.5 4.3-1.5 7.5-4.8 7.5-9.5V6Z"></path><path d="m9 12 2 2 4-4"></path></svg></span>
                    <div><h2>Role and access</h2><p>Your clinic permissions.</p></div>
                </div>
                <div class="profile-role-display"><span>Assigned role</span><strong>{{ $user->role }}</strong></div>
                <p class="profile-access-copy">{{ $user->accessDescription() }}</p>
                <small>Only the administrator can change assigned roles and system access.</small>
            </section>

            <section class="profile-card profile-security-card">
                <div class="profile-card-heading">
                    <span class="profile-heading-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"></path></svg></span>
                    <div><h2>Password security</h2><p>Your password is securely protected.</p></div>
                </div>
                <div class="profile-password-row"><span>••••••••••••</span><a class="btn btn-sm" href="{{ $isAdminView ? route('users.edit',$user) : route('profile.edit').'#security' }}">Change password</a></div>
                <x-admin-password-reveal :user="$user" />
            </section>
        </aside>
    </div>
    @if($isAdminView)<div class="actions"><x-section-return :href="route('users.index')" label="user management" /></div>@endif
</div>
@endsection
