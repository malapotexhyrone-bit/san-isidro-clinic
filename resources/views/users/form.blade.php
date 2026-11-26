@extends('layouts.app')
@section('title',$user->exists?'Edit User':'Add User')
@section('crumb','User Management')
@section('content')
<div class="title-row">
    <div><h1>{{ $user->exists?'Edit User':'Add User' }}</h1><p>Manage the staff member's profile, assigned role, and account security.</p></div>
    <div class="toolbar">
        <x-section-return :href="route('users.index')" label="user management" />
        @if($user->exists && auth()->user()->is($user))<a class="btn" href="{{ route('profile.show') }}">View my profile</a>@endif
    </div>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $user->exists?route('users.update',$user):route('users.store') }}" class="panel workflow-card" data-confirm="Save this staff account information? Role, password, and access changes take effect immediately.">
    @csrf
    @if($user->exists)@method('PUT')@endif
<section class="workflow-section">
        <x-workflow-heading step="1" title="Staff information" />
        <div class="form-grid">
        <div class="field"><label class="required">Full name</label><input name="name" value="{{ old('name',$user->name) }}" required></div>
        <div class="field"><label class="required">Username</label><input name="username" value="{{ old('username',$user->username) }}" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email',$user->email) }}"></div>
        <div class="field"><label for="staff-birthdate">Birthdate</label><x-date-picker id="staff-birthdate" name="birthdate" :value="old('birthdate',$user->birthdate?->format('Y-m-d'))" :max="now()->toDateString()" placeholder="Choose birthdate" /><span class="help">Age is calculated automatically from the birthdate.</span></div>
        <div class="field full"><label>Address</label><textarea name="address" rows="3">{{ old('address',$user->address) }}</textarea></div>
        </div>
    </section>
<section class="workflow-section">
        <x-workflow-heading step="2" title="Profile picture" />
        <div class="form-grid">
        <div class="field"><label>Profile picture</label><input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"><span class="help">JPG, PNG, or WEBP up to 2 MB.</span></div>
        @if($user->exists && $user->profile_photo_path)
            <div class="field"><label>Current picture</label><label class="profile-remove-option" style="justify-content:flex-start"><input type="checkbox" name="remove_profile_photo" value="1"> Remove current profile picture</label></div>
        @endif
        </div>
    </section>
<section class="workflow-section">
        <x-workflow-heading step="3" title="Access and security" />
        <div class="form-grid">
        <div class="field"><label class="required">Role</label><select name="role" required>@foreach(['Receptionist','Nurse','Physician','Administrator'] as $r)<option @selected(old('role',$user->role)===$r)>{{ $r }}</option>@endforeach</select></div>
        <div class="field"><label>Account status</label><select name="is_active"><option value="1" @selected(old('is_active',$user->exists?$user->is_active:1)==1)>Active</option><option value="0" @selected(old('is_active',$user->is_active)==0)>Inactive</option></select></div>
        <div class="field"><label for="password" class="{{ $user->exists?'':'required' }}">{{ $user->exists?'New password (leave blank to keep current)':'Temporary password' }}</label><x-password-input id="password" name="password" minlength="8" :required="!$user->exists" /></div>
        <div class="field"><label for="password_confirmation" class="{{ $user->exists?'':'required' }}">Confirm password</label><x-password-input id="password_confirmation" name="password_confirmation" minlength="8" :required="!$user->exists" /></div>
        </div>
    @if($user->exists)
        <div class="field full" style="margin-top:16px"><label>Current saved password</label><x-admin-password-reveal :user="$user" /></div>
    @endif
    </section>
    <div class="workflow-footer">
    <div class="notice"><strong>Role preview: {{ old('role',$user->role?:'Receptionist') }}</strong><br><span class="help">Accounts only see navigation and actions permitted for their assigned role. The saved password updates when the staff member or administrator changes it.</span></div>
    <div class="actions"><button class="btn btn-primary">Save user</button><a class="btn" href="{{ route('users.index') }}">Cancel</a></div>
    </div>
</form>
@endsection
