<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SeedsAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatter;

/*
| Super Admin (EDP) account (Manuscript Fig. 22/35). Without it a fresh
| install has nobody who can manage users, departments or transactions.
*/
class SuperAdminSeeder extends Seeder
{
    use SeedsAccounts;

    public const EMAIL = 'superadmin@pathwise.test';

    public function run(): void
    {
        $isNew = ! User::where('email', self::EMAIL)->exists();
        $generatedPassword = null;

        $this->seedAccount(
            self::EMAIL,
            'Super Administrator',
            function () use (&$generatedPassword) {
                $configured = (string) config('auth.seed_super_admin_password');

                if ($configured !== '') {
                    return $configured;
                }

                return $generatedPassword = $this->generatePassword();
            },
            'super_admin'
        );

        if ($isNew && $generatedPassword !== null) {
            // Escaped so symbols such as "</>" in the password are printed
            // as they are instead of being read as console colour tags.
            $this->command?->warn(
                'Super Admin '.self::EMAIL.' was created with this password (shown only once): '
                .OutputFormatter::escape($generatedPassword)
            );
        }
    }

    /**
     * A random 20-character password (letters, numbers and symbols).
     */
    protected function generatePassword(): string
    {
        return Str::password(20);
    }
}
