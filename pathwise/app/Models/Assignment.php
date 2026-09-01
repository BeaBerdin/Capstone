<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'description',
        'due_date',
        'max_score',
        'is_published',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}