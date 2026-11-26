<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User]);
    }

    public function show(User $user)
    {
        return view('profile.show', ['user' => $user, 'isAdminView' => true]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $password = $data['password'];
        unset($data['profile_photo'], $data['remove_profile_photo'], $data['password'], $data['password_confirmation']);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'local');
        }

        $user = new User($data);
        $user->setLoginPassword($password);
        $user->save();

        return redirect()->route('users.index')->with('success', 'User account created.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $password = $data['password'] ?? null;
        unset($data['profile_photo'], $data['remove_profile_photo'], $data['password'], $data['password_confirmation']);

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            Storage::disk('local')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            $newPath = $request->file('profile_photo')->store('profile-photos', 'local');
            if ($user->profile_photo_path) {
                Storage::disk('local')->delete($user->profile_photo_path);
            }
            $data['profile_photo_path'] = $newPath;
        }

        if (filled($password)) {
            $user->setLoginPassword($password);
        }
        $user->fill($data)->save();

        return redirect()->route('users.index')->with('success', 'User account updated.');
    }

    public function deactivate(Request $request, User $user)
    {
        abort_if($request->user()->is($user), 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => false]);

        return back()->with('success', 'User account deactivated.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'delete' => 'You cannot delete the administrator account that is currently signed in.',
            ]);
        }

        $hasAuditHistory = DB::table('appointments')->where('created_by', $user->id)->exists()
            || DB::table('appointment_histories')->where('changed_by', $user->id)->exists()
            || DB::table('visits')->where('created_by', $user->id)->exists()
            || DB::table('vital_signs')->where('recorded_by', $user->id)->exists();

        if ($hasAuditHistory) {
            throw ValidationException::withMessages([
                'delete' => 'This staff account is referenced by clinic audit records and cannot be deleted. Deactivate the account instead.',
            ]);
        }

        $profilePhotoPath = $user->profile_photo_path;
        $userName = $user->name;

        DB::transaction(function () use ($user): void {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        if ($profilePhotoPath) {
            Storage::disk('local')->delete($profilePhotoPath);
        }

        return redirect()->route('users.index')
            ->with('success', "Staff account {$userName} was permanently deleted.");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users')->ignore($user?->id)],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
            'role' => ['required', 'in:Receptionist,Nurse,Physician,Administrator'],
            'is_active' => ['required', 'boolean'],
            'password' => [$user ? 'nullable' : 'required', 'required_with:password_confirmation', 'string', 'min:8'],
            'password_confirmation' => ['required_with:password', 'nullable', 'string', 'same:password'],
        ], [
            'password.required_with' => 'Enter a new password before confirming it.',
            'password.min' => 'The password must contain at least 8 characters.',
            'password_confirmation.required_with' => 'Re-enter the password in Confirm password.',
            'password_confirmation.same' => 'The confirmation does not match the password. Re-enter the same password in both fields.',
        ]);
    }
}
