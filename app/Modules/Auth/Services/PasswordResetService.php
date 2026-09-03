<?php

namespace App\Modules\Auth\Services;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    /**
     * Send a reset link if the email is registered. Always behaves the same
     * way from the caller's point of view, regardless of the outcome, so the
     * UI never reveals whether the email exists.
     */
    public function sendResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);

        Log::info('auth.password_reset_requested', ['email' => $email]);
    }

    /**
     * @param  array{token: string, email: string, password: string}  $data
     */
    public function reset(array $data): void
    {
        $status = Password::reset(
            $data,
            function (CanResetPassword $user, string $password) {
                $user->forceFill(['password' => $password])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            // $status is one of Laravel's own status keys (e.g. "passwords.token"),
            // never the token or password itself.
            Log::warning('auth.password_reset_failed', [
                'email' => $data['email'],
                'status' => $status,
            ]);

            throw ValidationException::withMessages([
                'email' => [__('Ссылка для сброса пароля недействительна или устарела.')],
            ]);
        }

        Log::info('auth.password_reset_completed', ['email' => $data['email']]);
    }
}
