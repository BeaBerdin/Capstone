<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
| Who reviews a removal request (Administrator = Department Head):
|
| - An administrator without a department sees and reviews every request
|   (campus-wide).
| - An administrator with a department sees and reviews the requests of
|   teachers in that department.
| - A request is never left without a reviewer: requests from teachers with
|   no department, or from a department that has no administrator, go to
|   every administrator.
*/
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
     * Departments that have at least one administrator.
     *
     * @return array<int, int>
     */
    public static function departmentsWithAdministrator(): array
    {
        return User::whereNotNull('department_id')
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->distinct()
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Limit the query to the requests this administrator may review.
     *
     * @param  Builder<StudentRemovalRequest>  $query
     * @return Builder<StudentRemovalRequest>
     */
    public function scopeReviewableBy(Builder $query, User $administrator): Builder
    {
        $departmentId = $administrator->department_id;

        if (! $departmentId) {
            return $query;
        }

        $covered = static::departmentsWithAdministrator();

        return $query->whereHas('instructor', function ($instructor) use ($departmentId, $covered) {
            $instructor->where(function ($scope) use ($departmentId, $covered) {
                $scope->where('department_id', $departmentId)
                    ->orWhereNull('department_id')
                    ->orWhereNotIn('department_id', $covered);
            });
        });
    }

    public function isReviewableBy(User $administrator): bool
    {
        return static::whereKey($this->getKey())
            ->reviewableBy($administrator)
            ->exists();
    }
}
