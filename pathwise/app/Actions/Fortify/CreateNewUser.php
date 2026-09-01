<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * Self-registration is restricted to the "student" and "teacher"
     * roles. The "admin" role is intentionally excluded and can only be
     * granted through seeding or by an existing administrator.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', Rule::in(['student', 'teacher'])],
        ], [
            'role.required' => 'Please select whether you are registering as a student or a teacher.',
            'role.in' => 'The selected role is invalid.',
        ])->validate();

        // Wrap user creation and role assignment in a single transaction so
        // we never end up with a user that has no role attached.
        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $role = Role::where('name', $input['role'])->firstOrFail();

            // Attach without detaching anything else (belongsToMany pivot).
            $user->roles()->syncWithoutDetaching([$role->id]);

            return $user;
        });
    }
}