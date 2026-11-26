<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Physician;
use App\Models\PhysicianSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $assignedPhysicianId = $request->user()->role === 'Physician'
            ? $request->user()->physician?->id
            : null;
        if ($request->user()->role === 'Physician') {
            abort_unless($assignedPhysicianId, 403, 'This physician account is not linked to a physician record.');
        }

        $physicians = Physician::with(['schedules' => fn ($query) => $query
            ->orderBy('day_of_week')
            ->orderBy('start_time')])
            ->when($assignedPhysicianId, fn ($query) => $query->whereKey($assignedPhysicianId))
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereHas('schedules');
            })
            ->orderBy('name')
            ->get();

        $today = today();
        $scheduleDates = $physicians->flatMap->schedules->mapWithKeys(function (PhysicianSchedule $schedule) use ($today): array {
            $daysUntilSchedule = ($schedule->day_of_week - $today->dayOfWeek + 7) % 7;

            return [$schedule->id => $today->copy()->addDays($daysUntilSchedule)->toDateString()];
        });
        $bookings = Appointment::query()
            ->whereIn('physician_id', $physicians->pluck('id'))
            ->whereBetween('appointment_date', [$today->toDateString(), $today->copy()->addDays(6)->toDateString()])
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->get(['physician_id', 'appointment_date', 'appointment_time'])
            ->groupBy(fn (Appointment $appointment): string => $appointment->physician_id.'|'.$appointment->appointment_date->toDateString())
            ->map(fn ($items) => $items->map(fn (Appointment $appointment) => substr($appointment->appointment_time, 0, 5))->flip());
        $scheduleAvailability = $physicians->flatMap->schedules->mapWithKeys(function (PhysicianSchedule $schedule) use ($scheduleDates, $bookings): array {
            $date = Carbon::parse($scheduleDates[$schedule->id]);
            if (!$schedule->is_available) {
                return [$schedule->id => [
                    'date' => $date,
                    'status' => 'unavailable',
                    'open_slots' => 0,
                    'times' => [],
                ]];
            }

            $bookedTimes = $bookings->get($schedule->physician_id.'|'.$date->toDateString(), collect());
            $cursor = Carbon::parse($date->toDateString().' '.$schedule->start_time);
            $end = Carbon::parse($date->toDateString().' '.$schedule->end_time);
            $times = [];
            while ($cursor->lt($end)) {
                $time = $cursor->format('H:i');
                if (!isset($bookedTimes[$time]) && !($date->isToday() && $cursor->lte(now()))) {
                    $times[] = ['time' => $time, 'label' => $cursor->format('g:i A')];
                }
                $cursor->addMinutes($schedule->slot_duration);
            }

            return [$schedule->id => [
                'date' => $date,
                'status' => $times === [] ? 'fully_booked' : 'available',
                'open_slots' => count($times),
                'times' => $times,
            ]];
        });

        return view('schedules.index', [
            'physicians' => $physicians,
            'activePhysicians' => $physicians->where('is_active', true)->values(),
            'scheduleAvailability' => $scheduleAvailability,
        ]);
    }
    public function store(Request $request) { PhysicianSchedule::create($this->validated($request)); return back()->with('success', 'Schedule saved.'); }
    public function update(Request $request, PhysicianSchedule $schedule) { $schedule->update($this->validated($request)); return back()->with('success', 'Schedule updated.'); }
    public function destroy(PhysicianSchedule $schedule) { $schedule->delete(); return back()->with('success', 'Schedule removed.'); }
    private function validated(Request $request): array
    {
        return $request->validate([
            'physician_id' => ['required','exists:physicians,id'], 'day_of_week' => ['required','integer','between:0,6'],
            'start_time' => ['required','date_format:H:i'], 'end_time' => ['required','date_format:H:i','after:start_time'],
            'slot_duration' => ['required','integer','in:60'], 'is_available' => ['required','boolean'],
            'unavailability_reason' => ['nullable','string','max:255'],
        ]);
    }
}
