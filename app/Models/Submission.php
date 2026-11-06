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

    /**
     * @return BelongsTo<Assignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Minutes after which an AI evaluation still marked "processing" is
     * treated as interrupted (the request takes at most about two minutes).
     */
    public const AI_PROCESSING_TIMEOUT_MINUTES = 5;

    /**
     * True while an AI evaluation request is genuinely in progress.
     */
    public function aiEvaluationInProgress(): bool
    {
        return $this->ai_evaluation_status === 'processing'
            && $this->updated_at !== null
            && $this->updated_at->gt(now()->subMinutes(self::AI_PROCESSING_TIMEOUT_MINUTES));
    }

    /**
     * True when the last AI evaluation did not finish: it failed, or it
     * was interrupted while processing. The teacher can retry or grade
     * manually; no AI score is stored.
     */
    public function aiEvaluationDidNotFinish(): bool
    {
        return $this->ai_evaluation_status === 'failed'
            || ($this->ai_evaluation_status === 'processing' && ! $this->aiEvaluationInProgress());
    }
}
