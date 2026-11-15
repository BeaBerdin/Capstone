<?php

namespace App\Models;

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
    | STUDENT APPLICATION (EDP approval)
    |--------------------------------------------------------------------------
    |
    | New student registrations upload a Study Load and wait for the Super
    | Admin (EDP). Every submission is a StudentApplication row; the newest
    | one decides the account's status. An account without any row (existing
    | accounts, teachers, accounts created by the Super Admin) is approved.
    |
    */

    /**
     * @return HasMany<StudentApplication, $this>
     */
    public function studentApplications(): HasMany
    {
        return $this->hasMany(StudentApplication::class)->orderBy('submission_no');
    }

    /**
     * @return HasOne<StudentApplication, $this>
     */
    public function latestStudentApplication(): HasOne
    {
        return $this->hasOne(StudentApplication::class)->latestOfMany('submission_no');
    }

    /**
     * pending | approved | rejected. No application row means approved.
     */
    public function applicationStatus(): string
    {
        return $this->latestStudentApplication?->status ?? StudentApplication::APPROVED;
    }

    /**
     * True while a student's newest application is pending or rejected: the
     * account may not verify its email or use student pages until approved.
     * Never true for teachers, admins or the Super Admin.
     */
    public function awaitingStudentApproval(): bool
    {
        return in_array($this->applicationStatus(), StudentApplication::AWAITING, true)
            && $this->hasRole('student');
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

        parent::sendEmailVerificationNotification();
    }
}
