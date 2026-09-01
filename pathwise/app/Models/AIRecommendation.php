<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIRecommendation extends Model
{
    protected $table = 'ai_recommendations';

    protected $fillable = [
        'student_id',
        'course_id',
        'recommendation_score',
        'reason',
        'is_viewed',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}