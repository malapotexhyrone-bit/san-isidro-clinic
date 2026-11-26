<?php

namespace Tests;

use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        // Existing fixtures switch users directly; emulate a fresh authenticated
        // session for each explicit actingAs call. Real login tests do not use it.
        $guardName = $guard ?? $this->app['auth']->getDefaultDriver();
        $this->withSession([
            'clinic_auth_session_version' => EnsureAccountIsActive::SESSION_VERSION,
            'password_hash_'.$guardName => $this->app['auth']->guard($guardName)
                ->hashPasswordForCookie($user->getAuthPassword()),
        ]);

        return parent::actingAs($user, $guard);
    }
}
