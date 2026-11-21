<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const ENROLLMENT_PAYMENT_CONFLICT = 'A payment for this course is already on record or may still be payable. Contact support with your transaction number before joining or paying again.';

  protected $fillable = [
    'student_id',
    'course_id',
    'course_invitation_id',
    'transaction_no',
    'amount',
    'payment_method',
    'paymongo_method',
    'status',
    'payment_proof',
    'checkout_url',
    'payment_reference',
    'remarks',
    'approved_by',
    'approved_at',
];

    protected $casts = [
        'payment_review_required_at' => 'datetime',
    ];

    public function scopeForStudentCourse(Builder $query, int $studentId, int $courseId): Builder
    {
        return $query->where('student_id', $studentId)->where('course_id', $courseId);
    }

    public function scopeBlockingCoursePayment(Builder $query, int $studentId, int $courseId): Builder
    {
        return $query->forStudentCourse($studentId, $courseId)->where(fn (Builder $payments) => $payments
            ->whereIn('status', ['pending', 'approved'])
            ->orWhereNotNull('payment_review_required_at')
            // Local cancellation or rejection does not expire a PayMongo session.
            ->orWhere(fn (Builder $checkout) => $checkout->whereRaw('LOWER(payment_method) = ?', ['paymongo'])
                ->where(fn (Builder $reference) => $reference->whereNotNull('payment_reference')->orWhereNotNull('checkout_url'))));
    }

    public function canResumeCheckout(): bool
    {
        return $this->status === 'pending'
            && $this->isPayMongo()
            && filled($this->checkout_url)
            && str_starts_with((string) $this->payment_reference, 'cs_')
            && ! $this->course_invitation_id
            && ! $this->payment_review_required_at;
    }

    public function isPayMongo(): bool
    {
        return strcasecmp((string) $this->payment_method, 'PayMongo') === 0;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

 /**
  * @return BelongsTo<CourseInvitation, $this>
  */
 public function courseInvitation(): BelongsTo
{
    return $this->belongsTo(
        CourseInvitation::class,
        'course_invitation_id'
    );
}
}