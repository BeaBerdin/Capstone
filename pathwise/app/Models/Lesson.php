<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}