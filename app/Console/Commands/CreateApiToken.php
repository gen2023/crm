<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    protected $signature = 'api-token:create {email} {name=integration}';

    protected $description = 'Issue a Sanctum API token for a user, identified by email';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $token = $user->createToken($this->argument('name'));

        $this->info('Token created. This value is shown once — store it now:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
