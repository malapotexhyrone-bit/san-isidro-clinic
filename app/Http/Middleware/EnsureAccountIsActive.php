<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public const SESSION_VERSION = 1;

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Your clinic account is inactive. Contact an administrator.');
        }

        // Sessions created before this protection must authenticate once again.
        if ($request->session()->get('clinic_auth_session_version') !== self::SESSION_VERSION) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
