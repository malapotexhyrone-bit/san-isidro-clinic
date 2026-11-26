<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', [
            'users' => User::where('is_active', true)
                ->orderByRaw("CASE role WHEN 'Receptionist' THEN 1 WHEN 'Nurse' THEN 2 WHEN 'Physician' THEN 3 WHEN 'Administrator' THEN 4 ELSE 5 END")
                ->orderBy('name')
                ->get(['name','username','role']),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $throttleKey = 'clinic-login:'.hash('sha256', Str::lower($credentials['username']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withErrors(['password' => "Too many sign-in attempts. Try again in {$seconds} seconds."])
                ->onlyInput('username');
        }
        $credentials['is_active'] = true;
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);
            return back()
                ->withErrors(['password' => 'The selected clinic account or password is incorrect. Check the account and re-enter your password.'])
                ->onlyInput('username');
        }
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->put('clinic_auth_session_version', EnsureAccountIsActive::SESSION_VERSION);
        // Record the login password now, including before the first protected page.
        $request->session()->put(
            'password_hash_'.Auth::getDefaultDriver(),
            Auth::guard()->hashPasswordForCookie(Auth::user()->getAuthPassword())
        );
        ActivityLogger::record(
            Auth::user(),
            'login',
            'Signed in to the clinic system',
            'login.attempt',
            'POST',
            'login',
        );
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
