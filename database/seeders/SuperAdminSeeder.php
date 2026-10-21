<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SeedsAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

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
            // Printed raw so every character (for example "\<" or "<<") is
            // shown exactly; escaping could drop a backslash.
            $this->command?->getOutput()->writeln(
                'Super Admin '.self::EMAIL.' was created with this password (shown only once): '
                .$generatedPassword,
                OutputInterface::OUTPUT_RAW
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
