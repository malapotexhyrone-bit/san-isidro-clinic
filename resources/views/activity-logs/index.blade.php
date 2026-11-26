@extends('layouts.app')
@section('title','Activity Logs')
@section('crumb','Activity Logs')

@section('content')
<div class="title-row">
    <div><h1>Activity Logs</h1><p>Review staff access and completed system actions.</p></div>
</div>

<form class="panel panel-body toolbar" method="GET">
    <div class="field">
        <label for="activity-user">Staff member</label>
        <select id="activity-user" name="user">
            <option value="">All staff</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('user') === (string) $user->id)>{{ $user->name }} · {{ $user->username }} · {{ $user->role }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="activity-action">Activity</label>
        <select id="activity-action" name="action">
            <option value="">All activities</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ \Illuminate\Support\Str::headline($action) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label for="activity-from">From</label><x-date-picker id="activity-from" name="from" :value="request('from')" placeholder="Start date" /></div>
    <div class="field"><label for="activity-to">To</label><x-date-picker id="activity-to" name="to" :value="request('to')" placeholder="End date" /></div>
    <button class="btn btn-primary" type="submit">Apply filters</button>
    <a class="btn" href="{{ route('activity-logs.index') }}">Clear filters</a>
</form>

<div class="notice" style="margin-bottom:16px">
    <strong>Privacy-protected audit trail</strong><br>
    <span class="help">Logs identify the staff member, screen or action, target record number, and time. Passwords, verification codes, and submitted medical details are never stored here.</span>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date and time</th><th>Staff member</th><th>Role</th><th>Activity</th><th>Description</th><th>Target</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M Y g:i:s A') }}</td>
                    <td><strong>{{ $log->actor_name }}</strong><div class="help">{{ $log->actor_username }}</div></td>
                    <td><span class="badge">{{ $log->actor_role }}</span></td>
                    <td><span class="badge badge-{{ $log->action === 'delete' ? 'No-show' : ($log->action === 'login' ? 'Completed' : 'Scheduled') }}">{{ \Illuminate\Support\Str::headline($log->action) }}</span></td>
                    <td>{{ $log->description }}<div class="help">{{ $log->method }} · {{ $log->route_name }}</div></td>
                    <td>{{ $log->subject_type ? $log->subject_type.' #'.$log->subject_id : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No activity matches the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $logs->links() }}
@endsection
