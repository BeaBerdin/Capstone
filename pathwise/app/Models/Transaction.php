<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'transaction_no',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_proof',
        'status',
        'remarks',
        'approved_by',
        'approved_at',
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
}