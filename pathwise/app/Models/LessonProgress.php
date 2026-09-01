<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = [
        'student_id',
        'lesson_id',
        'status',
        'started_at',
        'completed_at',
        'time_spent_minutes',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}