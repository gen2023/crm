<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates (or updates) one local development administrator, from
     * ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD in .env. Never runs outside the
     * local environment — production admin accounts are a deliberate,
     * separate action, not something a routine `db:seed` should be able to
     * produce with a guessable/default password.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('AdminUserSeeder skipped: only runs in the local environment.');

            return;
        }

        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => env('ADMIN_PASSWORD', 'change-me-before-running'),
                'status' => 'active',
            ]
        );

        $user->assignRole('admin');
    }
}
