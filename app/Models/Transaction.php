<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  protected $fillable = [
    'student_id',
    'course_id',
    'course_invitation_id',
    'transaction_no',
    'amount',
    'payment_method',
    'status',
    'payment_proof',
    'checkout_url',
    'payment_reference',
];
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

 public function courseInvitation()
{
    return $this->belongsTo(
        CourseInvitation::class,
        'course_invitation_id'
    );
}
}