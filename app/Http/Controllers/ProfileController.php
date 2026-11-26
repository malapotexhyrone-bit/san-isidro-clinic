<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
            'current_password' => ['required_with:password,password_confirmation', 'nullable', 'current_password'],
            'password' => ['required_with:password_confirmation', 'nullable', 'string', 'min:8'],
            'password_confirmation' => ['required_with:password', 'nullable', 'string', 'same:password'],
        ], [
            'current_password.required_with' => 'Enter your current password before changing your password.',
            'current_password.current_password' => 'Your current password is incorrect. Re-enter the password you use to sign in.',
            'password.required_with' => 'Enter a new password before confirming it.',
            'password.min' => 'Your new password must contain at least 8 characters.',
            'password_confirmation.required_with' => 'Re-enter your new password in Confirm new password.',
            'password_confirmation.same' => 'The confirmation does not match your new password. Re-enter the same password in both fields.',
        ]);

        unset($data['profile_photo'], $data['remove_profile_photo'], $data['current_password'], $data['password'], $data['password_confirmation']);

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

        if ($request->filled('password')) {
            $user->setLoginPassword((string) $request->string('password'));
        }

        $user->fill($data)->save();

        return redirect()->route('profile.show')->with('success', 'Your profile was updated successfully.');
    }
}
