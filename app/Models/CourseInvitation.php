<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseInvitation extends Model
{
    protected $fillable = [
        'course_id',
        'created_by',
        'student_id',
        'is_private',
        'parent_invitation_id',
        'code',
        'expires_at',
        'deactivated_at',
        'accepted_at',
        'accepted_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_private' => 'boolean',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function parentInvitation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_invitation_id');
    }

    public function redeemedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_invitation_redemptions', 'course_invitation_id', 'student_id')
            ->withPivot('enrollment_id')
            ->withTimestamps();
    }

    public function scopeRedeemedFor(Builder $query, int $studentId, int $courseId): Builder
    {
        return $query->where('course_id', $courseId)->where(fn (Builder $invitations) => $invitations
            ->whereHas('redeemedBy', fn (Builder $students) => $students->whereKey($studentId))
            ->orWhere(fn (Builder $legacy) => $legacy->where('accepted_by', $studentId)->whereNotNull('accepted_at')));
    }

    public static function normalizeCode(string $input): ?string
    {
        $input = trim($input);
        $path = parse_url($input, PHP_URL_PATH);
        if (is_string($path) && preg_match('~/invitations/([^/]+)/?$~iD', $path, $match)) {
            $input = rawurldecode($match[1]);
        }

        $code = strtoupper((string) preg_replace('/[\s-]+/', '', $input));

        return preg_match('/^[A-Z0-9]{12}$/D', $code) ? $code : null;
    }

    /**
     * Invitation writes and redemptions lock the owning course first.
     */
    public static function findForLockedCourse(Course $course, int $invitationId): ?self
    {
        $invitation = self::where('course_id', $course->id)->lockForUpdate()->find($invitationId);

        if ($invitation) {
            $invitation->setRelation('course', $course);
            $invitation->setRelation(
                'parentInvitation',
                $invitation->parent_invitation_id ? $invitation->parentInvitation()->lockForUpdate()->first() : null
            );
        }

        return $invitation;
    }

    public function canBeOpenedBy(int $studentId): bool
    {
        return (! $this->is_private && $this->student_id === null && $this->parent_invitation_id === null)
            || ($this->student_id !== null && (int) $this->student_id === $studentId);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte(now());
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isValid(): bool
    {
        return $this->status() === 'Active';
    }

    public function courseIsAvailable(): bool
    {
        return $this->course && $this->course->status === 'published'
            && (int) $this->course->teacher_id === (int) $this->created_by;
    }

    public function status(): string
    {
        if ($this->deactivated_at || $this->parentInvitation?->deactivated_at) {
            return 'Deactivated';
        }

        if ($this->isExpired() || $this->parentInvitation?->isExpired()) {
            return 'Expired';
        }

        if ($this->isAccepted() || $this->parentInvitation?->isAccepted()) {
            return 'Used';
        }

        if (! $this->courseIsAvailable()
            || ($this->is_private && $this->student_id === null)
            || ($this->parent_invitation_id !== null && ! $this->hasValidParent())) {
            return 'Unavailable';
        }

        return 'Active';
    }

    public function unavailableMessage(): ?string
    {
        return match ($this->status()) {
            'Active' => null,
            'Deactivated' => 'This invitation has been deactivated. Ask your teacher for a new code.',
            'Expired' => 'This invitation has expired. Ask your teacher for a new code.',
            'Used' => 'This older invitation has already been used. Ask your teacher for a new code.',
            default => 'This course is currently unavailable through this invitation.',
        };
    }

    private function hasValidParent(): bool
    {
        return $this->parentInvitation
            && $this->parentInvitation->parent_invitation_id === null
            && ! $this->parentInvitation->is_private
            && $this->parentInvitation->student_id === null
            && (int) $this->parentInvitation->course_id === (int) $this->course_id
            && (int) $this->parentInvitation->created_by === (int) $this->created_by;
    }
}