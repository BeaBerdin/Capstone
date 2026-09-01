<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
    protected $fillable = [
        'student_id',
        'quiz_id',
        'score',
        'total_items',
        'percentage',
        'remarks',
        'attempt_number',
        'completed_at',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }
}