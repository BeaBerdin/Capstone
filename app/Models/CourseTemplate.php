<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A built-in curriculum template that gives teachers a ready-made lesson
 * outline when they create a course.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array<string, mixed>|null $structure
 * @property bool $is_active
 */
class CourseTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'structure',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'structure' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'template_id');
    }

    /**
     * @param  Builder<CourseTemplate>  $query
     * @return Builder<CourseTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The lesson outline as a clean list. Invalid entries are skipped.
     *
     * @return array<int, array{title: string, duration_minutes: int|null, guidance: string}>
     */
    public function lessonOutline(): array
    {
        $lessons = $this->structure['lessons'] ?? [];

        if (! is_array($lessons)) {
            return [];
        }

        return collect($lessons)
            ->filter(fn ($lesson) => is_array($lesson) && filled($lesson['title'] ?? null))
            ->map(fn ($lesson) => [
                'title' => mb_substr(trim((string) $lesson['title']), 0, 255),
                'duration_minutes' => isset($lesson['duration_minutes'])
                    ? max(1, (int) $lesson['duration_minutes'])
                    : null,
                'guidance' => trim((string) ($lesson['guidance'] ?? '')),
            ])
            ->values()
            ->all();
    }
}
