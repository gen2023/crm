<?php

namespace App\Modules\Auth\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt to authenticate the given credentials for the current request.
     *
     * On failure, always raises the same generic error regardless of whether
     * the email exists, the password is wrong, or the account is inactive.
     *
     * @param  array{email: string, password: string, remember_me?: bool}  $credentials
     */
    public function attempt(array $credentials, Request $request): void
    {
        $remember = (bool) ($credentials['remember_me'] ?? false);

        $authenticated = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember);

        if ($authenticated && ! Auth::user()->isActive()) {
            Auth::logout();
            $authenticated = false;
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => __('Неверный email или пароль.'),
            ]);
        }

        $request->session()->regenerate();

        Auth::user()->forceFill(['last_login_at' => now()])->save();
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
