<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    /**
     * Enrollment statuses that allow a student to open the course
     * (lessons, quizzes, assignments).
     *
     * "cancelled" (set by the Administrator under Enrollments) blocks
     * access but keeps the record, so progress and history are not lost.
     */
    public const ACCESS_STATUSES = [
        'active',
        'completed',
    ];

    protected $fillable = [
        'student_id',
        'course_id',
        'status',
        'enrolled_at',
        'completed_at',
        'progress_percentage',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Only enrollments that still give access to the course.
     */
    public function scopeWithAccess(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACCESS_STATUSES);
    }

    public function grantsAccess(): bool
    {
        return in_array($this->status, self::ACCESS_STATUSES, true);
    }
}
