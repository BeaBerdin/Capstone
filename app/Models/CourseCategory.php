<?php

namespace App\Models;

use App\Exceptions\DeletionBlockedException;
use App\Services\RecordRetentionService;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (self $category): void {
            if (($reason = $category->deletionBlockReason()) !== null) {
                throw new DeletionBlockedException($reason);
            }
        });
    }

    public function deletionBlockReason(): ?string
    {
        return app(RecordRetentionService::class)->categoryDeletionBlockReason($this);
    }

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }
}
