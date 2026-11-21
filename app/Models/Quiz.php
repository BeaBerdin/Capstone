<?php

namespace App\Models;

use App\Models\Concerns\HasCourseAndOptionalLesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasCourseAndOptionalLesson;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'description',
        'passing_score',
        'time_limit_minutes',
        'is_published',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
   {
    return $this->belongsTo(Lesson::class);
   }
}