<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
   protected $fillable = [
    'assignment_id',
    'student_id',
    'answer_text',
    'file_path',
    'score',
    'feedback',
    'status',
    'submitted_at',

    // AI Evaluation
    'ai_score',
    'ai_feedback',
    'ai_strengths',
    'ai_improvements',
    'ai_evaluation_status',
    'ai_evaluated_at',

    // Code Execution
    'execution_output',
    'execution_error',
    'execution_status',
    'code_language',
];

   protected function casts(): array
{
    return [
        'score' => 'integer',
        'ai_score' => 'integer',
        'submitted_at' => 'datetime',
        'ai_evaluated_at' => 'datetime',
    ];
}

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
