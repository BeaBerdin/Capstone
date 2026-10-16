<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Certificate template settings (one row), edited by the Administrator and
 * used by the certificate PDF. Until the Administrator saves them, the
 * defaults below reproduce the original certificate wording.
 */
class CertificateSetting extends Model
{
    public const DEFAULTS = [
        'title' => 'Certificate of Completion',
        'body_text' => 'This certificate is proudly awarded in recognition of the learner’s dedication, commitment, and successful completion of all course requirements, assessments, and learning activities under the academic standards of the PathWise AI-Powered E-Learning Platform.',
        'signatory_name' => 'PATHWISE',
        'signatory_position' => 'AI-Powered E-Learning Platform',
    ];

    protected $fillable = [
        'title',
        'body_text',
        'signatory_name',
        'signatory_position',
        'updated_by',
    ];

    /**
     * The saved settings, or an unsaved instance with the defaults.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->first()
            ?? new static(self::DEFAULTS);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
