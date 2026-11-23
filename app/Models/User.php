<?php

namespace App\Models;

use App\Notifications\VerifyTeacherEmail;
use App\Support\StudyLoads;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use LogicException;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $profile_photo_path
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_active
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'first_name',
    'middle_initial',
    'last_name',
    'email',
    'password',
    'profile_photo_path',
    'department_id',
])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Accounts are deactivated (is_active = false), never deleted.
     * Deleting a user would also delete their enrollments, payments,
     * submissions and certificates through the database's cascade rules.
     */
    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            // The name was changed as one field (e.g. in profile settings):
            // the separate parts no longer match, so they are cleared and the
            // forms fall back to the full name until it is edited in parts.
            if ($user->isDirty('name') && ! $user->isDirty(['first_name', 'middle_initial', 'last_name'])) {
                $user->first_name = null;
                $user->middle_initial = null;
                $user->last_name = null;
            }
        });

        static::deleting(function (self $user): void {
            throw new LogicException(
                'PATHWISE accounts are deactivated, not deleted, so their records are kept.'
            );
        });
    }

    /**
     * Best-effort split of a full name into first name, middle initial and
     * last name, for accounts created before the separate fields existed.
     * A middle initial is a single letter (with or without a period) or two
     * letters followed by a period between the first and last names, so
     * "Juan D. Dela Cruz" -> Juan / D / Dela Cruz, while "Juan De La Cruz"
     * keeps "De" in the last name. The last word is the last name otherwise.
     *
     * @return array{first_name: string, middle_initial: string, last_name: string}
     */
    public static function splitLegacyName(string $name): array
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) <= 1) {
            return ['first_name' => $words[0] ?? '', 'middle_initial' => '', 'last_name' => ''];
        }

        for ($i = 1; $i < count($words) - 1; $i++) {
            if (preg_match('/^(\pL\.?|\pL{2}\.)$/u', $words[$i])) {
                return [
                    'first_name' => implode(' ', array_slice($words, 0, $i)),
                    'middle_initial' => rtrim($words[$i], '.'),
                    'last_name' => implode(' ', array_slice($words, $i + 1)),
                ];
            }
        }

        return [
            'first_name' => implode(' ', array_slice($words, 0, -1)),
            'middle_initial' => '',
            'last_name' => end($words),
        ];
    }

    /**
     * "Juan", "d", "Dela Cruz" -> "Juan D. Dela Cruz"; no initial -> "Juan Dela Cruz".
     * Extra spaces are collapsed; the initial is upper case with one period.
     */
    public static function composeName(string $first, ?string $middleInitial, string $last): string
    {
        $tidy = fn (string $part) => trim((string) preg_replace('/\s+/u', ' ', $part));

        $initial = rtrim($tidy((string) $middleInitial), '.');
        $initial = $initial === '' ? '' : mb_strtoupper($initial).'.';

        return implode(' ', array_filter([$tidy($first), $initial, $tidy($last)], fn ($part) => $part !== ''));
    }

    /**
     * The separate name parts (tidied) for storing, plus the combined name.
     *
     * @return array{name: string, first_name: string, middle_initial: string|null, last_name: string}
     */
    public static function nameAttributes(string $first, ?string $middleInitial, string $last): array
    {
        $tidy = fn (string $part) => trim((string) preg_replace('/\s+/u', ' ', $part));
        $initial = mb_strtoupper(rtrim($tidy((string) $middleInitial), '.'));

        return [
            'name' => self::composeName($first, $middleInitial, $last),
            'first_name' => $tidy($first),
            'middle_initial' => $initial === '' ? null : $initial,
            'last_name' => $tidy($last),
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        // Timestamps record when a role was assigned (shown in the Super
        // Admin dashboard's Recent Activity).
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @param  string  $role
     */
    public function hasRole($role)
    {
        return $this->roles()
            ->where('name', $role)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRATION APPLICATIONS (EDP approval)
    |--------------------------------------------------------------------------
    |
    | Student Study Load decisions and teacher approvals apply to separate
    | roles. Only students retain the existing no-application exception.
    |
    */

    /**
     * @return HasMany<StudentApplication, $this>
     */
    public function studentApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class)
            ->where('applicant_role', StudentApplication::STUDENT)
            ->orderBy('submission_no');
    }

    /**
     * @return HasOne<StudentApplication, $this>
     */
    public function latestStudentApplication(): HasOne
    {
        return $this->hasOne(StudentApplication::class)->ofMany(
            ['submission_no' => 'max'],
            fn ($query) => $query->where('applicant_role', StudentApplication::STUDENT),
        );
    }

    /** @return HasMany<StudentApplication, $this> */
    public function teacherApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class)->where('applicant_role', StudentApplication::TEACHER);
    }

    public function currentTeacherApplication(): ?StudentApplication
    {
        $application = $this->teacherApplications()->orderByDesc('submission_no')->first();

        if (! $application?->reviewsEmail($this->email)) {
            return null;
        }

        if ($application->status === StudentApplication::APPROVED
            && ($application->reviewed_by === null || $application->reviewed_at === null)) {
            return null;
        }

        return $application;
    }

    public function approvedTeacherApplication(): ?StudentApplication
    {
        $application = $this->currentTeacherApplication();

        return $application?->status === StudentApplication::APPROVED ? $application : null;
    }

    public function applicationStatus(): string
    {
        if ($this->hasRole('teacher')) {
            return $this->currentTeacherApplication()?->status ?? StudentApplication::PENDING;
        }

        return $this->latestStudentApplication?->status ?? StudentApplication::APPROVED;
    }

    /**
     * True while a student's newest application is pending or rejected: the
     * account may not verify its email or use student pages until approved.
     * Never true for teachers, admins or the Super Admin.
     */
    public function awaitingStudentApproval(): bool
    {
        return $this->hasRole('student')
            && in_array($this->latestStudentApplication?->status, StudentApplication::AWAITING, true);
    }

    /**
     * The latest review must explicitly approve this role and email address.
     */
    public function awaitingTeacherApproval(): bool
    {
        return $this->hasRole('teacher') && $this->approvedTeacherApplication() === null;
    }

    /** A student or teacher whose registration the EDP has not approved. */
    public function awaitingApproval(): bool
    {
        return $this->awaitingTeacherApproval() || $this->awaitingStudentApproval();
    }

    /** Keep email changes and the new teacher review in the same transaction. */
    public function updateAccountIdentity(array $attributes): void
    {
        $review = DB::transaction(function () use ($attributes) {
            $user = static::query()->lockForUpdate()->findOrFail($this->getKey());
            $user->fill($attributes)->save();

            if ($user->wasChanged('email') && $user->hasRole('teacher')) {
                return StudentApplication::submitForTeacher($user, 'email_change');
            }

            return null;
        });

        $this->refresh();
        StudyLoads::notifyEdp($review, resubmission: false);
    }

    /**
     * No verification email (Laravel's automatic one after registration, a
     * resend, or any other caller) while the application awaits approval.
     * After approval the EDP's acceptance email carries the link instead.
     */
    public function sendEmailVerificationNotification()
    {
        if ($this->awaitingStudentApproval()) {
            return;
        }

        if ($this->hasRole('teacher')) {
            if ($application = $this->approvedTeacherApplication()) {
                $this->notify(new VerifyTeacherEmail($application->id));
            }

            return;
        }

        parent::sendEmailVerificationNotification();
    }
}
