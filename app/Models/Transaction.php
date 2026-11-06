<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
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