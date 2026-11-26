<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = Carbon::parse($request->input('from',now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to',now()->toDateString()))->endOfDay();
        $appointments = Appointment::with(['patient','physician'])
            ->whereDate('appointment_date','>=',$from->toDateString())
            ->whereDate('appointment_date','<=',$to->toDateString())
            ->orderBy('appointment_date')->get();
        $visits = Visit::with(['patient','physician'])->whereBetween('arrived_at',[$from,$to])->orderBy('arrived_at')->get();
        $physicianCounts = Physician::withCount(['appointments as total' => fn($query) => $query
            ->whereDate('appointment_date','>=',$from->toDateString())
            ->whereDate('appointment_date','<=',$to->toDateString())])
            ->orderBy('name')
            ->get();
        $physicianAppointmentTotal = $physicianCounts->sum('total');
        return view('reports.index',compact('from','to','appointments','visits','physicianCounts','physicianAppointmentTotal')+['stats'=>[
            'appointments'=>$appointments->count(), 'cancelled'=>$appointments->where('status','Cancelled')->count(),
            'no_show'=>$appointments->where('status','No-show')->count(), 'rescheduled'=>$appointments->where('status','Rescheduled')->count(),
            'walkins'=>$visits->where('visit_type','Walk-in')->count(), 'completed'=>$visits->where('status','Completed')->count(),
            'unique_patients'=>$visits->pluck('patient_id')->unique()->count(), 'patients_total'=>Patient::count(),
        ]]);
    }
}
