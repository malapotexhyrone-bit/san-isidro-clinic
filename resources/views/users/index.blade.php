@extends('layouts.app')
@section('title','User Management')
@section('crumb','User Management')
@section('content')
<div class="title-row">
    <div><h1>User Management</h1><p>Create staff accounts, review profiles, and assign a single role.</p></div>
    <a class="btn btn-primary" href="{{ route('users.create') }}">Add user</a>
</div>
<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Account status</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td><a class="btn btn-sm" href="{{ route('users.show',$u) }}">{{ $u->name }}</a></td>
                    <td>{{ $u->username }}</td>
                    <td>{{ $u->role }}</td>
                    <td><span class="badge badge-{{ $u->is_active?'Active':'Inactive' }}">{{ $u->is_active?'Active':'Inactive' }}</span></td>
                    <td>
                        <a class="btn btn-sm" href="{{ route('users.show',$u) }}">Profile</a> <a class="btn btn-sm" href="{{ route('users.edit',$u) }}">Edit</a>
                        @if($u->is_active && !auth()->user()->is($u))
                            <form class="inline-form" method="POST" action="{{ route('users.deactivate',$u) }}" data-confirm="Deactivate {{ $u->name }}? This user will no longer be able to sign in.">
                                @csrf @method('PATCH') · <button class="btn btn-sm btn-danger">Deactivate</button>
                            </form>
                        @endif
                        @unless(auth()->user()->is($u))
                            <form class="inline-form" method="POST" action="{{ route('users.destroy',$u) }}" data-confirm="Permanently delete the staff account for {{ $u->name }}? This is allowed only when the account has no clinic audit history. This cannot be undone.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Delete account</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="notice"><strong>Access follows the assigned role.</strong><br><span class="help">Receptionists manage registration and bookings; nurses record vital signs; physicians write consultations; administrators manage records and reports.</span></div>
@endsection
