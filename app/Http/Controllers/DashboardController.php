<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\Visit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $today = now()->toDateString();
        $visiblePhysicianId = $request->user()->role === 'Physician'
            ? ($request->user()->physician?->id ?? 0)
            : null;
        $appointments = Appointment::with(['patient', 'physician'])
            ->whereDate('appointment_date', $today)
            ->when($visiblePhysicianId !== null, fn($q) => $q->where('physician_id', $visiblePhysicianId))
            ->orderBy('appointment_time')->get();
        $visits = Visit::with(['patient', 'physician', 'vitalSign'])
            ->whereDate('arrived_at', $today)
            ->when($visiblePhysicianId !== null, fn($q) => $q->where('physician_id', $visiblePhysicianId))
            ->latest('arrived_at')->get();
        return view('dashboard', [
            'appointments' => $appointments,
            'visits' => $visits,
            'stats' => [
                'patients' => $request->user()->role === 'Physician'
                    ? Patient::where(function($query) use ($visiblePhysicianId) {
                        $query->whereHas('appointments', fn($appointments) => $appointments->where('physician_id',$visiblePhysicianId))
                            ->orWhereHas('visits', fn($visits) => $visits->where('physician_id',$visiblePhysicianId));
                    })->count()
                    : Patient::count(),
                'scheduled' => $appointments->whereIn('status', ['Scheduled', 'Rescheduled'])->count(),
                'waiting' => $visits->where('status', '!=', 'Completed')->count(),
                'completed' => $visits->where('status', 'Completed')->count(),
                'walkins' => $visits->where('visit_type', 'Walk-in')->count(),
                'physicians' => $visiblePhysicianId !== null
                    ? (int) Physician::whereKey($visiblePhysicianId)->where('is_active',true)->exists()
                    : Physician::where('is_active', true)->count(),
            ],
        ]);
    }
}
