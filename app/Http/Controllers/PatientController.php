<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->query('search'));
        $physicianId = $request->user()->role === 'Physician'
            ? ($request->user()->physician?->id ?? 0)
            : null;
        $patients = Patient::when($physicianId !== null, fn($q) => $q->where(function($q) use ($physicianId) {
            $q->whereHas('appointments', fn($appointments) => $appointments->where('physician_id',$physicianId))
                ->orWhereHas('visits', fn($visits) => $visits->where('physician_id',$physicianId));
        }))->when($search, fn($q) => $q->where(function($q) use ($search) {
            $q->where('patient_number', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        }))->orderBy('last_name')->paginate(15)->withQueryString();
        return view('patients.index', compact('patients', 'search'));
    }

    public function create() { return view('patients.form', ['patient' => new Patient]); }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['email'] = strtolower($data['email']);
        $patient = Patient::create($data + ['patient_number' => $this->nextNumber()]);
        return redirect()->route('patients.show', $patient)->with('success', 'Patient registered successfully.');
    }

    public function show(Request $request, Patient $patient)
    {
        if ($request->user()->role === 'Physician') {
            $physicianId = $request->user()->physician?->id ?? 0;
            $isAssigned = $patient->appointments()->where('physician_id',$physicianId)->exists()
                || $patient->visits()->where('physician_id',$physicianId)->exists();
            abort_unless($isAssigned,403,'This patient was assigned to another physician.');
            $patient->load([
                'visits' => fn($query) => $query->where('physician_id',$physicianId)
                    ->with(['physician','vitalSign.recorder','consultation']),
                'appointments' => fn($query) => $query->where('physician_id',$physicianId)
                    ->with('physician'),
            ]);
        } elseif ($request->user()->role === 'Nurse') {
            $patient->load(['visits.physician','visits.vitalSign.recorder']);
            $patient->setRelation('appointments',collect());
        } elseif ($request->user()->role === 'Receptionist') {
            $patient->load(['visits.physician','visits.vitalSign','appointments.physician']);
        } else {
            $patient->load(['visits.physician', 'visits.vitalSign.recorder', 'visits.consultation', 'appointments.physician']);
        }
        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient) { return view('patients.form', compact('patient')); }

    public function update(Request $request, Patient $patient)
    {
        $data = $this->validated($request, $patient);
        $data['email'] = strtolower($data['email']);
        $patient->update($data);
        return redirect()->route('patients.show', $patient)->with('success', 'Patient information updated.');
    }

    public function destroy(Patient $patient)
    {
        if ($patient->appointments()->exists() || $patient->visits()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'This patient has appointment or visit history and cannot be permanently deleted. The clinical record must be preserved.',
            ]);
        }

        $patientName = $patient->full_name;
        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', "Patient {$patientName} was permanently deleted.");
    }

    private function validated(Request $request, ?Patient $patient = null): array
    {
        return $request->validate([
            'first_name' => ['required','string','max:100'], 'middle_name' => ['nullable','string','max:100'],
            'last_name' => ['required','string','max:100'], 'birth_date' => ['required','date','before_or_equal:today'],
            'sex' => ['required','in:Male,Female'], 'contact_number' => ['required','string','max:30'],
            'email' => ['required','email:rfc','max:255', Rule::unique('patients', 'email')->ignore($patient)],
            'address' => ['required','string','max:1000'], 'emergency_contact' => ['nullable','string','max:150'],
            'emergency_contact_number' => ['nullable','string','max:30'],
        ]);
    }

    private function nextNumber(): string
    {
        $next = (Patient::max('id') ?? 0) + 1;
        return 'P-'.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}
