<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRemovalRequest extends Model
{
    protected $fillable = [
        'instructor_id',
        'student_id',
        'course_id',
        'reason',
        'status',
        'reviewed_by',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

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
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Limit the query to the requests this administrator may review.
     *
     * @param  Builder<StudentRemovalRequest>  $query
     * @return Builder<StudentRemovalRequest>
     */
    public function scopeReviewableBy(Builder $query, User $administrator): Builder
    {
        return $query->whereHas('course', fn (Builder $course) => $course->manageableBy($administrator));
    }

    public function isReviewableBy(User $administrator): bool
    {
        return static::whereKey($this->getKey())
            ->reviewableBy($administrator)
            ->exists();
    }
}
