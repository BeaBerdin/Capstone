<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    /**
     * Languages a code assignment can use (Manuscript Scope: HTML/CSS/
     * JavaScript, Java, C and PHP; Python is kept so older assignments still
     * work). The stored key "javascript" covers HTML, CSS and JavaScript.
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
     * Largest source file a student may upload for a code assignment, in KB.
     */
    public const SOURCE_FILE_MAX_KB = 256;

    protected $fillable = [
    'course_id',
    'lesson_id',
    'title',
    'description',
    'submission_type',
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
