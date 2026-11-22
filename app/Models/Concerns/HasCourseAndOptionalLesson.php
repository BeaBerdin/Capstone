<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasCourseAndOptionalLesson
{
    public function scopeManageableBy(Builder $query, User $administrator): Builder
    {
        return $query
            ->whereHas('course', fn (Builder $course) => $course->manageableBy($administrator))
            ->when(! $administrator->hasRole('super_admin'), fn (Builder $records) => $records->withConsistentLesson());
    }

    public function scopeWithConsistentLesson(Builder $query): Builder
    {
        return $query->where(fn (Builder $records) => $records
            ->whereNull($this->qualifyColumn('lesson_id'))
            ->orWhereHas('lesson', fn (Builder $lesson) => $lesson
                ->whereColumn('lessons.course_id', $this->qualifyColumn('course_id'))));
    }

    public function hasConsistentLesson(): bool
    {
        return static::query()->whereKey($this->getKey())->withConsistentLesson()->exists();
    }
}
