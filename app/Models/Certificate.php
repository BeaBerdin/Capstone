<?php

namespace App\Models;

use App\Notifications\PathwiseNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'certificate_number',
        'issued_date',
        'certificate_file',
        'status',
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
     * Tell the student their certificate is ready: an in-app notification,
     * plus an email when mail is configured. Only issued certificates
     * notify, and a notification problem never stops the certificate.
     */
    public function notifyStudent(): void
    {
        try {
            $this->loadMissing(['student', 'course']);

            if (
                ! $this->student
                || ! $this->course
                || strtolower(trim((string) $this->status)) !== 'issued'
            ) {
                return;
            }

            $this->student->notify(new PathwiseNotification(
                title: 'Certificate available',
                message: 'Your certificate for "'.$this->course->title.'" is now available.',
                type: 'certificate_issued',
                courseId: $this->course->id,
                certificateId: $this->id
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Next certificate number in the PATH-YYYY-00001 sequence.
     *
     * Used for every automatically issued certificate (lesson completion
     * and final quiz) so the numbers never depend on student or course IDs.
     */
    public static function generateNumber(): string
    {
        $year = now()->year;

        $lastCertificate = static::where(
            'certificate_number',
            'like',
            "PATH-{$year}-%"
        )
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastCertificate) {
            $parts = explode('-', $lastCertificate->certificate_number);
            $nextNumber = (int) end($parts) + 1;
        }

        do {
            $certificateNumber = 'PATH-'.$year.'-'.str_pad(
                (string) $nextNumber,
                5,
                '0',
                STR_PAD_LEFT
            );

            $nextNumber++;
        } while (
            static::where('certificate_number', $certificateNumber)->exists()
        );

        return $certificateNumber;
    }
}