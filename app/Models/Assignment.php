<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
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
    ];
}

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
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
