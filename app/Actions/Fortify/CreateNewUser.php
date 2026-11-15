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
        // Letters (any language), spaces, hyphens, apostrophes and periods,
        // e.g. "Ma. Clara", "Dela Cruz", "O'Neil".
        $namePattern = "/^[\pL\pM][\pL\pM .'\-]*$/u";

        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:100', 'regex:'.$namePattern],
            'middle_initial' => ['nullable', 'string', 'regex:/^\pL\.?$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:'.$namePattern],
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', Rule::in(['student', 'teacher'])],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, apostrophes and periods.',
            'middle_initial.regex' => 'The middle initial must be a single letter, for example "D" or "D.".',
            'last_name.required' => 'Please enter your last name.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, apostrophes and periods.',
            'role.required' => 'Please select whether you are registering as a student or a teacher.',
            'role.in' => 'The selected role is invalid.',
        ])->validate();

        // Stored in the existing single "name" column as "First M. Last",
        // so every page that shows a user's name keeps working unchanged.
        $name = self::fullName(
            $input['first_name'],
            $input['middle_initial'] ?? null,
            $input['last_name'],
        );

        // Wrap user creation and role assignment in a single transaction so
        // we never end up with a user that has no role attached.
        return DB::transaction(function () use ($input, $name) {
            $user = User::create([
                'name' => $name,
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $role = Role::where('name', $input['role'])->firstOrFail();

            // Attach without detaching anything else (belongsToMany pivot).
            $user->roles()->syncWithoutDetaching([$role->id]);

            return $user;
        });
    }

    /**
     * "Juan", "d", "Dela Cruz" -> "Juan D. Dela Cruz"; no initial -> "Juan Dela Cruz".
     */
    public static function fullName(string $first, ?string $middleInitial, string $last): string
    {
        $tidy = fn (string $part) => trim(preg_replace('/\s+/u', ' ', $part));

        $initial = $tidy((string) $middleInitial);
        $initial = $initial === '' ? '' : mb_strtoupper(mb_substr($initial, 0, 1)).'.';

        return implode(' ', array_filter([$tidy($first), $initial, $tidy($last)], fn ($part) => $part !== ''));
    }
}