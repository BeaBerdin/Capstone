<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
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

    /**
     * Public URL of a stored course cover, or null when there is none.
     * Covers live on the public disk; a full http(s) URL is used as is.
     * Every page that shows a course cover should resolve it through here.
     */
    public static function thumbnailUrlFor(?string $thumbnail): ?string
    {
        $thumbnail = trim((string) $thumbnail);

        if ($thumbnail === '') {
            return null;
        }

        if (str_starts_with($thumbnail, 'http://') || str_starts_with($thumbnail, 'https://')) {
            return $thumbnail;
        }

        return asset('storage/' . ltrim($thumbnail, '/'));
    }

    /**
     * $course->thumbnail_url
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return self::thumbnailUrlFor($this->thumbnail);
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

    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function category(): BelongsTo
 {
    return $this->belongsTo(CourseCategory::class, 'category_id');
 }

   /**
    * @return BelongsTo<CourseTemplate, $this>
    */
   public function template(): BelongsTo
 {
    return $this->belongsTo(CourseTemplate::class, 'template_id');
 }

   /**
    * @return BelongsTo<User, $this>
    */
   public function teacher(): BelongsTo
 {
    return $this->belongsTo(User::class, 'teacher_id');
 }
   /**
    * @return HasMany<Lesson, $this>
    */
   public function lessons(): HasMany
 {
    return $this->hasMany(Lesson::class);
 }

   /**
    * @return HasMany<Quiz, $this>
    */
   public function quizzes(): HasMany
 {
    return $this->hasMany(Quiz::class);
 }

 /**
  * @return HasMany<Enrollment, $this>
  */
 public function enrollments(): HasMany
{
    return $this->hasMany(Enrollment::class);
}

/**
 * @return HasMany<Transaction, $this>
 */
public function transactions(): HasMany
{
    return $this->hasMany(Transaction::class);
}

/**
 * @return HasMany<Certificate, $this>
 */
public function certificates(): HasMany
{
    return $this->hasMany(Certificate::class);
}

/**
 * @return BelongsToMany<LearningPath, $this>
 */
public function learningPaths(): BelongsToMany
{
    return $this->belongsToMany(
        LearningPath::class,
        'learning_path_courses'
    );
}

/**
 * Validation rules for the introduction video link, used by every form
 * that saves it. Only https links are accepted, so javascript:, data:
 * and other schemes can never be stored. 255 is the column length.
 *
 * @return list<string>
 */
public static function introVideoRules(): array
{
    return ['nullable', 'string', 'max:255', 'url:https'];
}

/**
 * The introduction video link, only when it is a plain http or https
 * address. Anything else (for example an old javascript: value) is
 * never printed as a link.
 */
public function safeIntroVideoUrl(): ?string
{
    $url = trim((string) $this->intro_video);

    if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

    return in_array($scheme, ['http', 'https'], true) ? $url : null;
}

/**
 * @return HasMany<CourseReview, $this>
 */
public function reviews(): HasMany
{
    return $this->hasMany(CourseReview::class);
}

/**
 * Reviews counted in the average and shown publicly (not hidden).
 *
 * @return HasMany<CourseReview, $this>
 */
public function visibleReviews(): HasMany
{
    return $this->hasMany(CourseReview::class)->where('is_hidden', false);
}
}