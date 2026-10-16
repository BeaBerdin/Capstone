<?php

namespace Database\Seeders\Concerns;

use App\Models\Role;
use App\Models\User;

trait SeedsAccounts
{
    /**
     * Create a seeded account if it does not exist yet and give it a role.
     *
     * An existing account keeps its name and password, so running the
     * seeders on a live database can never reset anyone's password.
     * Seeded accounts are marked as email-verified only in the local and
     * testing environments.
     *
     * @param  callable(): string  $password  Called only when the account is created.
     */
    protected function seedAccount(string $email, string $name, callable $password, string $role): User
    {
        $verify = $this->isLocalEnvironment();

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => $password(),
                'email_verified_at' => $verify ? now() : null,
            ])->save();
        } elseif ($verify && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $roleModel = Role::where('name', $role)->first();

        if ($roleModel) {
            $user->roles()->syncWithoutDetaching([$roleModel->id]);
        }

        return $user;
    }

    /**
     * Demo accounts (password "password") and email verification are only
     * allowed in the local and testing environments.
     */
    protected function isLocalEnvironment(): bool
    {
        return app()->environment(['local', 'testing']);
    }
}
