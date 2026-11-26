@extends('layouts.app')

@section('title', 'Physician Schedules')
@section('crumb', 'Physician Schedules')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/schedule-availability.css') }}">
@endpush

@section('content')
<div class="title-row">
    <div>
        <h1>Physician Schedules</h1>
        <p>Each physician has a separate weekly schedule for faster availability checking.</p>
    </div>
    @if(auth()->user()->role === 'Administrator')
        <a class="btn" href="{{ route('physicians.index') }}">Manage physicians</a>
    @endif
</div>

@if(auth()->user()->role === 'Administrator')
    <form method="POST" action="{{ route('schedules.store') }}" class="panel workflow-card" data-confirm="Save this physician schedule?">
        @csrf
        <section class="workflow-section">
        <x-workflow-heading step="1" title="Physician and day" />
        <div class="form-grid">
            <div class="field">
                <label class="required">Physician</label>
                <select name="physician_id" required>
                    @foreach($activePhysicians as $physician)
                        <option value="{{ $physician->id }}" @selected((string) old('physician_id') === (string) $physician->id)>
                            {{ $physician->name }} — {{ $physician->specialization }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="required">Day</label>
                <select name="day_of_week" required>
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index => $day)
                        <option value="{{ $index }}" @selected((string) old('day_of_week') === (string) $index)>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        </section>
        <section class="workflow-section">
        <x-workflow-heading step="2" title="Hours and availability" />
        <div class="form-grid">
            <div class="field">
                <label class="required">Start time</label>
                <input type="time" name="start_time" value="{{ old('start_time') }}" required>
            </div>
            <div class="field">
                <label class="required">End time</label>
                <input type="time" name="end_time" value="{{ old('end_time') }}" required>
            </div>
            <div class="field">
                <label class="required">Slot duration (minutes)</label>
                <input type="hidden" name="slot_duration" value="60">
                <input type="text" value="60 minutes (1 hour per patient)" disabled>
            </div>
            <div class="field">
                <label class="required">Availability</label>
                <select name="is_available" required>
                    <option value="1" @selected(old('is_available', '1') === '1')>Available</option>
                    <option value="0" @selected(old('is_available') === '0')>Unavailable</option>
                </select>
            </div>
            <div class="field full">
                <label>Unavailability reason</label>
                <input name="unavailability_reason" value="{{ old('unavailability_reason') }}">
            </div>
        </div>
        </section>
        <div class="workflow-footer"><div class="actions">
            <button class="btn btn-primary">Save schedule</button>
        </div></div>
    </form>
@endif

<div id="schedule-overview" class="schedule-overview">
    @forelse($physicians as $physician)
        @php
            $availableSchedules = $physician->schedules->where('is_available', true);
            $availableDays = $availableSchedules->pluck('day_of_week')->unique()->count();
            $unavailablePeriods = $physician->schedules->where('is_available', false)->count();
        @endphp
        <section class="panel" data-physician-schedule="{{ $physician->id }}">
            <div class="panel-body" style="border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
                <div>
                    <h2 style="margin-bottom:4px">{{ $physician->name }}</h2>
                    <div class="help">{{ $physician->specialization ?: 'General physician' }}</div>
                </div>
                <div class="actions" style="margin:0">
                    <span class="badge badge-{{ $physician->is_active ? 'Active' : 'Inactive' }}">
                        {{ $physician->is_active ? 'Active physician' : 'Inactive physician' }}
                    </span>
                    <span class="badge badge-Active">{{ $availableDays }} working {{ \Illuminate\Support\Str::plural('day', $availableDays) }}</span>
                    @if($unavailablePeriods)
                        <span class="badge badge-Inactive">{{ $unavailablePeriods }} unavailable {{ \Illuminate\Support\Str::plural('period', $unavailablePeriods) }}</span>
                    @endif
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Hours</th>
                            <th>Slot length</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($physician->schedules as $schedule)
                            @php($slotAvailability = $scheduleAvailability->get($schedule->id))
                            <tr>
                                <td><strong>{{ ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$schedule->day_of_week] }}</strong></td>
                                <td>{{ date('g:i A', strtotime($schedule->start_time)) }}–{{ date('g:i A', strtotime($schedule->end_time)) }}</td>
                                <td>{{ $schedule->slot_duration }} minutes</td>
                                <td>
                                    @if($slotAvailability['status'] === 'available')
                                        <span class="badge badge-Completed" data-availability-status="available">
                                            Available · {{ $slotAvailability['open_slots'] }} open {{ \Illuminate\Support\Str::plural('slot', $slotAvailability['open_slots']) }}
                                        </span>
                                    @elseif($slotAvailability['status'] === 'fully_booked')
                                        <span class="badge badge-Scheduled" data-availability-status="fully-booked">Fully booked</span>
                                    @else
                                        <span class="badge badge-Inactive" data-availability-status="unavailable">Unavailable</span>
                                    @endif
                                    @if($schedule->unavailability_reason)
                                        <div class="help">{{ $schedule->unavailability_reason }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if(auth()->user()->role === 'Administrator')
                                        <details>
                                            <summary>Edit hours</summary>
                                            <form method="POST" action="{{ route('schedules.update', $schedule) }}" style="min-width:260px;padding:10px" data-confirm="Save these schedule changes for {{ $physician->name }}? Existing appointments may need individual review.">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="physician_id" value="{{ $schedule->physician_id }}">
                                                <input type="hidden" name="day_of_week" value="{{ $schedule->day_of_week }}">
                                                <div class="field">
                                                    <label>Start</label>
                                                    <input type="time" name="start_time" value="{{ substr($schedule->start_time, 0, 5) }}" required>
                                                </div>
                                                <div class="field">
                                                    <label>End</label>
                                                    <input type="time" name="end_time" value="{{ substr($schedule->end_time, 0, 5) }}" required>
                                                </div>
                                                <div class="field">
                                                    <label>Slot</label>
                                                    <input type="hidden" name="slot_duration" value="60">
                                                    <input type="text" value="60 minutes (1 hour per patient)" disabled>
                                                </div>
                                                <div class="field">
                                                    <label>Availability</label>
                                                    <select name="is_available" required>
                                                        <option value="1" @selected($schedule->is_available)>Available</option>
                                                        <option value="0" @selected(!$schedule->is_available)>Unavailable</option>
                                                    </select>
                                                </div>
                                                <div class="field">
                                                    <label>Reason</label>
                                                    <input name="unavailability_reason" value="{{ $schedule->unavailability_reason }}">
                                                </div>
                                                <button class="btn btn-primary btn-sm">Save changes</button>
                                            </form>
                                        </details>
                                    @elseif(auth()->user()->role === 'Receptionist')
                                        @if($slotAvailability['status'] === 'available')
                                            <button
                                                class="btn btn-sm slot-open-button"
                                                type="button"
                                                data-slot-action="available"
                                                data-slot-open
                                                data-physician="{{ $physician->name }}"
                                                data-specialization="{{ $physician->specialization ?: 'General physician' }}"
                                                data-date="{{ $slotAvailability['date']->format('l, F j, Y') }}"
                                                data-hours="{{ date('g:i A', strtotime($schedule->start_time)) }}–{{ date('g:i A', strtotime($schedule->end_time)) }}"
                                                data-slots='@json($slotAvailability['times'])'
                                            >View available slots</button>
                                        @elseif($slotAvailability['status'] === 'fully_booked')
                                            <span class="help" data-slot-action="disabled" aria-disabled="true">View available slots · Fully booked</span>
                                        @else
                                            <span class="help" data-slot-action="disabled" aria-disabled="true">View available slots · Unavailable</span>
                                        @endif
                                    @else
                                        <span class="help">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty">No weekly schedule has been recorded for this physician.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="panel panel-body empty">No physicians are available.</div>
    @endforelse
</div>

<section id="schedule-slot-viewer" class="slot-screen panel" hidden aria-labelledby="slot-screen-title">
    <header class="slot-screen-header">
        <div>
            <span class="slot-screen-kicker">Available checkup times</span>
            <h2 id="slot-screen-title" tabindex="-1"></h2>
            <p id="slot-screen-specialization" class="help"></p>
        </div>
        <span class="badge badge-Completed" id="slot-screen-count"></span>
    </header>
    <div class="slot-screen-meta" aria-label="Schedule details">
        <div><span>Date</span><strong id="slot-screen-date"></strong></div>
        <div><span>Clinic hours</span><strong id="slot-screen-hours"></strong></div>
        <div><span>Checkup length</span><strong>1 hour per patient</strong></div>
    </div>
    <div id="slot-screen-times" class="slot-screen-times" aria-label="Available times"></div>
    <footer class="slot-screen-footer">
        <button class="btn btn-primary" id="slot-screen-return" type="button">Return to schedules</button>
    </footer>
</section>

<div class="notice schedule-overview-support">
    <strong>Schedule change check</strong><br>
    <span class="help">Review booked appointments before changing hours. Existing appointments remain in history and should be rescheduled individually when needed.</span>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/schedule-availability.js') }}" defer></script>
@endpush
