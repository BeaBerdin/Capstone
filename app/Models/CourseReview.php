<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's rating (1-5) and optional comment for a course.
 *
 * One review per student per course. Hidden reviews (set by an
 * administrator, with a reason) are left out of the average, the count and
 * every public list; the student who wrote it still sees it, locked.
 */
class CourseReview extends Model
{
    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public const COMMENT_MAX_LENGTH = 1000;

    public const HIDDEN_REASON_MAX_LENGTH = 500;

    protected $fillable = [
        'course_id',
        'student_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Only a student with an active or completed enrollment in a published
     * course may review it. Teachers (including the course's own teacher),
     * administrators and the Super Admin may not, even if enrolled.
     */
    public static function studentCanReview(?User $user, Course $course): bool
    {
        if (! $user || strtolower(trim((string) $course->status)) !== 'published') {
            return false;
        }

        if ((int) $course->teacher_id === (int) $user->id) {
            return false;
        }

        $roles = $user->roles()->pluck('name');

        if (! $roles->contains('student') || $roles->intersect(['teacher', 'admin', 'super_admin'])->isNotEmpty()) {
            return false;
        }

        return Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->withAccess()
            ->exists();
    }
}
