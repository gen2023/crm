<?php

namespace App\Modules\Auth\Services;

use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

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
            // Never log the submitted password — only enough to spot brute-force patterns.
            Log::warning('auth.login_failed', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('Неверный email или пароль.'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        // Powers the Dashboard's "recent logins" card — reuses the existing
        // audit trail rather than a dedicated login-history table.
        $this->auditLogger->log('auth.login', $user);
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
