<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Role;
use App\Models\StudentApplication;
use App\Models\User;
use App\Support\StudyLoads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Throwable;

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
        // Phone keyboards often type a curly apostrophe (’); it is accepted
        // and saved as a straight one, so "O’Neil" and "O'Neil" are the same.
        foreach (['first_name', 'last_name'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = str_replace(["\u{2019}", "\u{2018}"], "'", $input[$field]);
            }
        }

        // Letters (any language), spaces, hyphens, apostrophes and periods,
        // e.g. "Ma. Clara", "Dela Cruz", "O'Neil".
        $namePattern = "/^[\pL\pM][\pL\pM .'\-]*$/u";

        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:100', 'regex:'.$namePattern],
            'middle_initial' => ['nullable', 'string', 'regex:/^\pL\.?$/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:'.$namePattern],
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            // Only these two roles can be chosen; anything else is refused.
            'role' => ['required', 'string', Rule::in(self::SELF_REGISTRABLE_ROLES)],
            // Students only: a Study Load for the EDP to verify. For a
            // teacher the field is excluded, so a file sent anyway is ignored.
            'study_load' => ['exclude_unless:role,student', ...StudentApplication::studyLoadRules()],
        ], [
            'first_name.required' => 'Please enter your first name.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, apostrophes and periods.',
            'middle_initial.regex' => 'The middle initial must be a single letter, for example "D" or "D.".',
            'last_name.required' => 'Please enter your last name.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, apostrophes and periods.',
            'role.required' => 'Please select whether you are registering as a student or a teacher.',
            'role.in' => 'The selected role is invalid.',
            ...StudentApplication::studyLoadMessages(),
        ])->validate();

        // Stored in the existing single "name" column as "First M. Last",
        // so every page that shows a user's name keeps working unchanged.
        $name = self::fullName(
            $input['first_name'],
            $input['middle_initial'] ?? null,
            $input['last_name'],
        );

        $isStudent = $input['role'] === 'student';
        $storedPath = null;

        /*
         * The account, its role and (for a student) the first application
         * row are created in ONE transaction, before Fortify fires the
         * Registered event. A student account can therefore never exist
         * without its application row (which would count as approved).
         * If anything fails, both rows are rolled back and the stored Study
         * Load file is deleted.
         */
        try {
            $user = DB::transaction(function () use ($input, $name, $isStudent, &$storedPath) {
                $user = User::create([
                    'name' => $name,
                    'email' => $input['email'],
                    'password' => $input['password'],
                ]);

                $role = Role::where('name', $input['role'])->firstOrFail();

                // Attach without detaching anything else (belongsToMany pivot).
                $user->roles()->syncWithoutDetaching([$role->id]);

                if ($isStudent) {
                    StudyLoads::submit($user, $input['study_load'], 1, $storedPath);
                } else {
                    // A self-registered teacher also waits for the EDP's
                    // approval (no Study Load), in the same queue.
                    StudentApplication::submitForTeacher($user, 1);
                }

                return $user;
            });
        } catch (Throwable $e) {
            StudyLoads::discard($storedPath);

            throw $e;
        }

        // Tell the EDP only once the application is safely saved.
        StudyLoads::notifyEdp($user->latestStudentApplication()->first(), resubmission: false);

        return $user;
    }

    /** Roles a visitor may choose on the registration form. */
    public const SELF_REGISTRABLE_ROLES = ['student', 'teacher'];

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