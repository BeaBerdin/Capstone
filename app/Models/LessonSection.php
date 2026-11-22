<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonSection extends Model
{
    protected $attributes = ['body_format' => 'plain'];

    protected $fillable = [
        'lesson_id',
        'type',
        'body',
        'body_format',
        'caption',
        'file_path',
        'original_name',
        'mime_type',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
