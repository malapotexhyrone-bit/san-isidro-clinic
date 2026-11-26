<?php
namespace App\Http\Controllers;

use App\Models\Physician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhysicianController extends Controller
{
    public function index() { return view('physicians.index', ['physicians' => Physician::with('user')->orderBy('name')->get()]); }
    public function create() { return view('physicians.form', ['physician' => new Physician, 'users' => $this->users()]); }
    public function store(Request $request) { $p = Physician::create($this->validated($request)); return redirect()->route('physicians.index')->with('success', 'Physician added.'); }
    public function edit(Physician $physician) { return view('physicians.form', ['physician' => $physician, 'users' => $this->users($physician)]); }
    public function update(Request $request, Physician $physician) { $physician->update($this->validated($request, $physician)); return redirect()->route('physicians.index')->with('success', 'Physician updated.'); }
    public function deactivate(Physician $physician) { $physician->update(['is_active' => false]); return back()->with('success', 'Physician deactivated.'); }

    private function users(?Physician $physician = null) { return User::where('role', 'Physician')->where('is_active', true)->where(fn($q) => $q->whereDoesntHave('physician')->when($physician?->user_id, fn($q,$id) => $q->orWhere('id',$id)))->orderBy('name')->get(); }
    private function validated(Request $request, ?Physician $physician = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:150'], 'specialization' => ['required','string','max:150'],
            'contact_number' => ['nullable','string','max:30'], 'is_active' => ['required','boolean'],
            'user_id' => ['nullable', Rule::exists('users','id')->where(fn($q) => $q->where('role','Physician')),
                Rule::unique('physicians','user_id')->ignore($physician?->id)],
        ]);
    }
}
