<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningPath extends Model
{
    public function scopeManageableBy(Builder $query, User $administrator): Builder
    {
        if ($administrator->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereHas('courses')
            ->whereDoesntHave('courses', fn (Builder $courses) => $courses
                ->whereNotIn('courses.id', Course::manageableBy($administrator)->select('courses.id')));
    }

    protected $fillable = [
        'name',
        'description',
        'student_id',
        'difficulty_level',
        'is_generated',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses')
            ->withPivot('course_order')
            ->orderBy('learning_path_courses.course_order');
    }
}