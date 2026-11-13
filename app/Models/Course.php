<?php

namespace App\Models;

use App\Exceptions\DeletionBlockedException;
use App\Services\RecordRetentionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (self $course): void {
            if (($reason = $course->deletionBlockReason()) !== null) {
                throw new DeletionBlockedException($reason);
            }
        });
    }

    public function deletionBlockReason(): ?string
    {
        return app(RecordRetentionService::class)->courseDeletionBlockReason($this);
    }

    /** @return HasMany<Assignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Course statuses in which the teacher may add, edit or delete
     * assignments. Only a course waiting for admin review (pending) is
     * locked. Used by both the assignment pages and AssignmentController.
     */
    public const ASSIGNMENT_EDITABLE_STATUSES = [
        'draft',
        'rejected',
        'published',
    ];

    public function allowsAssignmentChanges(): bool
    {
        return in_array(
            strtolower((string) $this->status),
            self::ASSIGNMENT_EDITABLE_STATUSES,
            true
        );
    }

    protected $fillable = [
        'teacher_id',
        'category_id',
        'template_id',
        'title',
        'description',
        'thumbnail',
        'intro_video',
        'difficulty_level',
        'price',
        'status',
        'review_remarks',
        'estimated_hours',
        'certificate_available',
    ];

    public function category()
 {
    return $this->belongsTo(CourseCategory::class, 'category_id');
 }

   public function template()
 {
    return $this->belongsTo(CourseTemplate::class, 'template_id');
 }

   public function teacher()
 {
    return $this->belongsTo(User::class, 'teacher_id');
 }
   public function lessons()
 {
    return $this->hasMany(Lesson::class);
 }

   public function quizzes()
 {
    return $this->hasMany(Quiz::class);
 }

 public function enrollments()
{
    return $this->hasMany(Enrollment::class);
}

public function transactions()
{
    return $this->hasMany(Transaction::class);
}

public function certificates()
{
    return $this->hasMany(Certificate::class);
}

public function learningPaths()
{
    return $this->belongsToMany(
        LearningPath::class,
        'learning_path_courses'
    );
}

public function reviews()
{
    return $this->hasMany(CourseReview::class);
}

/**
 * Reviews counted in the average and shown publicly (not hidden).
 */
public function visibleReviews()
{
    return $this->hasMany(CourseReview::class)->where('is_hidden', false);
}
}
