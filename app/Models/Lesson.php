<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    public function hasReferencesOutsideCourse(int $courseId): bool
    {
        return Quiz::where('lesson_id', $this->getKey())->where('course_id', '<>', $courseId)->exists()
            || Assignment::where('lesson_id', $this->getKey())->where('course_id', '<>', $courseId)->exists();
    }

    protected $fillable = [
        'course_id',
        'title',
        'content',
        'lesson_type',
        'video_url',
        'file_path',
        'lesson_order',
        'duration_minutes',
        'is_preview',
        'is_published',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<LessonSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(LessonSection::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<Assignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
