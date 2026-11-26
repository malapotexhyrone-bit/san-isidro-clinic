<?php
namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\Visit;
use App\Models\VitalSign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    public function index(Request $request)
    {
        $visits = Visit::with(['patient','physician','vitalSign'])
            ->when($request->user()->role === 'Physician', fn($q) => $q->where('physician_id',$request->user()->physician?->id ?? 0))
            ->when($request->date, fn($q,$date) => $q->whereDate('arrived_at',$date))
            ->latest('arrived_at')->paginate(20)->withQueryString();
        return view('visits.index', compact('visits'));
    }

    public function createWalkIn()
    {
        return view('visits.walkin', ['patients'=>Patient::orderBy('last_name')->get(), 'physicians'=>Physician::where('is_active',true)->orderBy('name')->get()]);
    }

    public function storeWalkIn(Request $request)
    {
        $request->merge([
            'patient_id' => $this->resolvePatientId($request->input('patient_id'), $request->input('patient_lookup')),
            'physician_id' => $this->resolvePhysicianId($request->input('physician_id'), $request->input('physician_lookup')),
        ]);
        $data = $request->validate(['patient_id'=>['required','exists:patients,id'],'physician_id'=>['required','exists:physicians,id'],'reason'=>['required','string','max:1000']]);
        $visit = Visit::create($data + ['visit_number'=>$this->nextNumber(),'appointment_id'=>null,'arrived_at'=>now(),'visit_type'=>'Walk-in','status'=>'Waiting','created_by'=>$request->user()->id]);
        return redirect()->route('visits.show',$visit)->with('success','Walk-in recorded without reserving or changing any appointment slot.');
    }

    public function show(Request $request, Visit $visit)
    {
        if ($request->user()->role === 'Physician' && $visit->physician_id !== $request->user()->physician?->id) abort(403);
        $visit->load(['patient','physician','appointment','vitalSign.recorder','consultation']);
        if ($request->user()->role === 'Physician') {
            $visit->patient->load(['visits' => fn($query) => $query
                ->where('physician_id',$visit->physician_id)
                ->with(['physician','vitalSign','consultation'])]);
        } elseif ($request->user()->role === 'Administrator') {
            $visit->patient->load(['visits.physician','visits.vitalSign','visits.consultation']);
        }
        return view('visits.show',compact('visit'));
    }

    public function vitalSigns(Request $request, Visit $visit)
    {
        abort_unless(
            $request->user()->role === 'Nurse',
            403,
            'Only a nurse can record or update vital signs.'
        );
        abort_unless(
            in_array($visit->status,['Waiting','Vital Signs Recorded'],true),
            422,
            'Vital signs can only be recorded before the physician starts the consultation.'
        );
        $data = $request->validate([
            'blood_pressure'=>['required','regex:/^\d{2,3}\/\d{2,3}$/'], 'temperature'=>['required','numeric','between:30,45'],
            'pulse'=>['required','integer','between:20,250'], 'respiratory_rate'=>['required','integer','between:5,80'],
            'weight'=>['required','numeric','between:1,500'], 'height'=>['required','numeric','between:30,250'], 'notes'=>['nullable','string','max:1000'],
        ]);
        VitalSign::updateOrCreate(['visit_id'=>$visit->id],$data+['recorded_by'=>$request->user()->id]);
        $visit->update(['status'=>'Vital Signs Recorded']);
        return back()->with('success','Vital signs saved.');
    }

    public function consultation(Request $request, Visit $visit)
    {
        $physician = $request->user()->physician;
        abort_if(!$physician || $visit->physician_id !== $physician->id,403,'This visit is assigned to another physician.');
        abort_if($visit->status === 'Completed',422,'Completed visits cannot be changed.');
        if (!$visit->vitalSign()->exists()) {
            throw ValidationException::withMessages([
                'vital_signs'=>'The nurse must record this patient’s vital signs before consultation can begin.',
            ]);
        }
        $data = $request->validate([
            'action' => ['required','in:draft,complete'],
            'chief_complaint' => ['required_if:action,complete','nullable','string','max:2000'],
            'assessment' => ['required_if:action,complete','nullable','string','max:3000'],
            'notes' => ['nullable','string','max:5000'],
            'recommendations' => ['nullable','string','max:3000'],
        ], [
            'chief_complaint.required_if' => 'Enter the chief complaint before completing the consultation.',
            'assessment.required_if' => 'Enter the assessment / diagnosis before completing the consultation.',
            'action.required' => 'Choose Save draft or Complete consultation.',
        ]);
        // Drafts can be incomplete; the existing non-null database columns store blank text.
        // Preserve previous text when a partial request omits one of these fields entirely.
        foreach (['chief_complaint','assessment'] as $field) {
            $data[$field] = array_key_exists($field,$data)
                ? (string)($data[$field] ?? '')
                : ($visit->consultation?->{$field} ?? '');
        }
        DB::transaction(function() use($visit,$physician,$data) {
            Consultation::updateOrCreate(['visit_id'=>$visit->id],collect($data)->except('action')->all()+['physician_id'=>$physician->id]);
            if ($data['action']==='complete') {
                $visit->update(['status'=>'Completed','completed_at'=>now()]);
                $visit->appointment?->update(['status'=>'Completed']);
            } else $visit->update(['status'=>'In Consultation']);
        });
        return back()->with('success',$data['action']==='complete'?'Visit completed.':'Consultation draft saved.');
    }

    private function nextNumber(): string { return 'VIS-'.now()->format('Ymd').'-'.str_pad((string)((Visit::max('id')??0)+1),5,'0',STR_PAD_LEFT); }

    private function resolvePatientId(mixed $id, ?string $lookup): mixed
    {
        if ($id && Patient::whereKey($id)->exists()) return $id;
        $needle = trim(explode(' — ', (string)$lookup)[0]);
        return Patient::all()->first(fn($patient) =>
            strcasecmp($patient->patient_number, $needle) === 0 || strcasecmp($patient->full_name, $needle) === 0
        )?->id;
    }

    private function resolvePhysicianId(mixed $id, ?string $lookup): mixed
    {
        if ($id && Physician::whereKey($id)->where('is_active', true)->exists()) return $id;
        $needle = trim(explode(' — ', (string)$lookup)[0]);
        return Physician::where('is_active', true)->get()->first(fn($physician) =>
            strcasecmp($physician->name, $needle) === 0
        )?->id;
    }
}
