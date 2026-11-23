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

    /** Lesson types a student may preview before enrolling. */
    public const PREVIEW_TYPES = ['text', 'video'];

    /**
     * True when any student may open this lesson before enrolling: a
     * published Reading or Video lesson marked as a preview, in a published
     * course (not a draft, under review or rejected). Activities, quizzes
     * and documents are never previewable.
     */
    public function isPreviewable(): bool
    {
        $course = $this->course;

        return $course !== null
            && strtolower(trim((string) $course->status)) === 'published'
            && (bool) $this->is_published
            && (bool) $this->is_preview
            && in_array(strtolower(trim((string) $this->lesson_type)), self::PREVIEW_TYPES, true);
    }
}
