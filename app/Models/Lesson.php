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

    public function isAvailableForPreview(): bool
    {
        return (bool) $this->is_preview
            && (bool) $this->is_published
            && in_array($this->lesson_type, ['text', 'video', 'document'], true)
            && strtolower(trim((string) $this->course?->status)) === 'published';
    }
}
