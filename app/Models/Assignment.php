<?php

namespace App\Models;

use App\Models\Concerns\HasCourseAndOptionalLesson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasCourseAndOptionalLesson;

    /**
     * Languages a code assignment can use (Manuscript Scope, with MR-17:
     * HTML/CSS/JavaScript, Java, C, PHP and Python). The stored key
     * "javascript" covers HTML, CSS and JavaScript.
     * "extensions" lists the source files a student may upload instead of
     * typing the code (.txt is always allowed as well).
     */
    public const PROGRAMMING_LANGUAGES = [
        'javascript' => [
            'label' => 'HTML/CSS/JavaScript',
            'extensions' => ['html', 'htm', 'css', 'js'],
        ],
        'java' => [
            'label' => 'Java',
            'extensions' => ['java'],
        ],
        'c' => [
            'label' => 'C',
            'extensions' => ['c', 'h'],
        ],
        'php' => [
            'label' => 'PHP',
            'extensions' => ['php'],
        ],
        'python' => [
            'label' => 'Python',
            'extensions' => ['py'],
        ],
    ];

    /**
     * How students may answer an essay or code assignment (stored values).
     */
    public const ANSWER_FORMATS = ['either', 'text', 'file'];

    /**
     * Labels for each answer format, worded for the submission type.
     * Used by the teacher form, the student page and the error messages.
     */
    public const ANSWER_FORMAT_LABELS = [
        'essay' => [
            'either' => 'Typed answer or file upload',
            'text' => 'Typed answer only',
            'file' => 'File upload only',
        ],
        'code' => [
            'either' => 'Pasted code or file upload',
            'text' => 'Pasted code only',
            'file' => 'File upload only',
        ],
    ];

    /**
     * Largest file a student may upload, in KB (25 MB).
     */
    public const UPLOAD_MAX_KB = 25600;

    /**
     * Largest amount of file text sent to Gemini for AI evaluation, in KB.
     * Larger files are graded manually.
     */
    public const AI_TEXT_MAX_KB = 256;

    protected $fillable = [
    'course_id',
    'lesson_id',
    'is_activity',
    'title',
    'description',
    'submission_type',
    'answer_format',
    'evaluation_criteria',
    'programming_language',
    'ai_evaluation_enabled',
    'due_date',
    'max_score',
    'is_published',
];

  protected function casts(): array
{
    return [
        'due_date' => 'datetime',
        'max_score' => 'integer',
        'is_published' => 'boolean',
        'ai_evaluation_enabled' => 'boolean',
        'is_activity' => 'boolean',
    ];
}

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function scopePublishedForStudents(Builder $query): Builder
    {
        return $query->withConsistentLesson()
            ->where('is_published', true)
            ->whereHas('course', fn (Builder $course) => $course->where('status', 'published'))
            ->where(fn (Builder $assignments) => $assignments
                ->where('is_activity', false)
                ->orWhereHas('lesson', fn (Builder $lesson) => $lesson
                    ->where('lesson_type', 'activity')
                    ->where('is_published', true)));
    }

    public function scopeForEnrolledStudent(Builder $query, int $studentId): Builder
    {
        return $query->whereHas('course.enrollments', fn (Builder $enrollments) => $enrollments
            ->where('student_id', $studentId)
            ->withAccess());
    }

    public function authorizeStudentAccess(int $studentId, string $enrollmentMessage): void
    {
        $assignment = static::query()->whereKey($this->getKey());

        abort_unless((clone $assignment)->publishedForStudents()->exists(), 404);
        abort_unless($assignment->forEnrolledStudent($studentId)->exists(), 403, $enrollmentMessage);
    }

    /**
     * Load the current Activity for a student write inside a transaction.
     * Keep publication and enrollment stable through submission and completion.
     */
    public static function lockStudentActivity(
        Lesson $lesson,
        int $studentId,
        string $enrollmentMessage,
        ?int $assignmentId = null
    ): self {
        $course = Course::query()->lockForUpdate()->findOrFail($lesson->course_id);
        $lesson = $course->lessons()->lockForUpdate()->findOrFail($lesson->getKey());

        $activity = $lesson->assignments()
            ->where('course_id', $course->getKey())
            ->where('is_activity', true)
            ->when($assignmentId !== null, fn ($query) => $query->whereKey($assignmentId))
            ->lockForUpdate()
            ->firstOrFail();

        $course->enrollments()->where('student_id', $studentId)->lockForUpdate()->get();
        $activity->authorizeStudentAccess($studentId, $enrollmentMessage);

        $lesson->setRelation('course', $course);
        $activity->setRelation('lesson', $lesson);
        $activity->setRelation('course', $course);

        return $activity;
    }

    /**
     * Display name of the assignment's programming language.
     */
    public function programmingLanguageLabel(): ?string
    {
        if (blank($this->programming_language)) {
            return null;
        }

        return self::PROGRAMMING_LANGUAGES[$this->programming_language]['label']
            ?? ucfirst($this->programming_language);
    }

    /**
     * Answer-format labels for a submission type (essay wording by default).
     *
     * @return array<string, string>
     */
    public static function answerFormatLabels(?string $submissionType): array
    {
        return self::ANSWER_FORMAT_LABELS[$submissionType === 'code' ? 'code' : 'essay'];
    }

    /**
     * Label of this assignment's answer format, e.g. "Pasted code only".
     */
    public function answerFormatLabel(): string
    {
        return self::answerFormatLabels($this->submission_type)[$this->answer_format ?? 'either']
            ?? self::answerFormatLabels($this->submission_type)['either'];
    }

    /**
     * Whether students may type an answer or paste code.
     */
    public function acceptsTypedAnswer(): bool
    {
        return ($this->answer_format ?? 'either') !== 'file';
    }

    /**
     * Whether students may upload a file.
     */
    public function acceptsFileUpload(): bool
    {
        return ($this->answer_format ?? 'either') !== 'text';
    }

    /**
     * File extensions a student may upload for this code assignment.
     *
     * @return array<int, string>
     */
    public function sourceFileExtensions(): array
    {
        $extensions = self::PROGRAMMING_LANGUAGES[$this->programming_language]['extensions'] ?? [];

        return [...$extensions, 'txt'];
    }
}
