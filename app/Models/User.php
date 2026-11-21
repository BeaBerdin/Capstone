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
        });

        static::deleting(function (self $user): void {
            throw new LogicException(
                'PATHWISE accounts are deactivated, not deleted, so their records are kept.'
            );
        });
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
