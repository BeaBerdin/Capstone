<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\PersonNameRules;
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
    use PasswordValidationRules, PersonNameRules, ProfileValidationRules;

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
        // Curly apostrophes become straight ones (see PersonNameRules).
        $input = $this->normalizePersonName($input);

        Validator::make($input, [
            ...$this->personNameRules(),
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
            // Only these two roles can be chosen; anything else is refused.
            'role' => ['required', 'string', Rule::in(self::SELF_REGISTRABLE_ROLES)],
            // Students only: a Study Load for the EDP to verify. For a
            // teacher the field is excluded, so a file sent anyway is ignored.
            'study_load' => ['exclude_unless:role,student', ...StudentApplication::studyLoadRules()],
            // Teachers only: a Faculty Verification Document for the
            // EDP to verify. For a student the field is excluded and ignored.
            'teacher_id' => ['exclude_unless:role,teacher', ...StudentApplication::teacherIdRules()],
        ], [
            ...$this->personNameMessages(),
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'role.required' => 'Please select whether you are registering as a student or a teacher.',
            'role.in' => 'The selected role is invalid.',
            ...StudentApplication::studyLoadMessages(),
            ...StudentApplication::teacherIdMessages(),
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
        $application = null;

        /*
         * Save the account, role and first review before Fortify fires the
         * Registered event. Roll back the database changes and discard an
         * uploaded Study Load if any part of registration fails.
         */
        try {
            $user = DB::transaction(function () use ($input, $name, $isStudent, &$storedPath, &$application) {
                $user = User::create([
                    ...User::nameAttributes($input['first_name'], $input['middle_initial'] ?? null, $input['last_name']),
                    'email' => $input['email'],
                    'password' => $input['password'],
                ]);

                $role = Role::where('name', $input['role'])->firstOrFail();

                // Attach without detaching anything else (belongsToMany pivot).
                $user->roles()->syncWithoutDetaching([$role->id]);

                if ($isStudent) {
                    $application = StudyLoads::submit($user, $input['study_load'], 1, $storedPath);
                } else {
                    // A self-registered teacher waits for the EDP's approval
                    // in the same queue, with a Faculty Verification Document instead.
                    $application = StudentApplication::submitForTeacher(
                        $user,
                        'registration',
                        StudyLoads::storeTeacherId($user, $input['teacher_id'], $storedPath),
                    );
                }

                return $user;
            });
        } catch (Throwable $e) {
            StudyLoads::discard($storedPath);

            throw $e;
        }

        // Tell the EDP only once the application is safely saved.
        StudyLoads::notifyEdp($application, resubmission: false);

        return $user;
    }

    /** Roles a visitor may choose on the registration form. */
    public const SELF_REGISTRABLE_ROLES = ['student', 'teacher'];

    /**
     * "Juan", "d", "Dela Cruz" -> "Juan D. Dela Cruz"; no initial -> "Juan Dela Cruz".
     */
    public static function fullName(string $first, ?string $middleInitial, string $last): string
    {
        return User::composeName($first, $middleInitial, $last);
    }
}
