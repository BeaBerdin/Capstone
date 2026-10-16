<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsAccounts;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    use SeedsAccounts;

    public function run(): void
    {
        // Demo account with a known password: local and testing only.
        if (! $this->isLocalEnvironment()) {
            $this->command?->info('Skipping the demo admin account outside local/testing.');

            return;
        }

        $this->seedAccount(
            'admin@pathwise.test',
            'System Administrator',
            fn () => 'password',
            'admin'
        );
    }
}
