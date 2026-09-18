<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PathwiseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'general',
        public ?int $courseId = null,
        public ?int $assignmentId = null,
        public ?int $submissionId = null,
        public ?int $transactionId = null,
        public ?int $certificateId = null
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | DELIVERY CHANNEL
    |--------------------------------------------------------------------------
    | PathWise currently uses database notifications so they appear inside
    | the bell notification dropdown.
    */

    public function via(object $notifiable): array
    {
        return [
            'database',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DATABASE DATA
    |--------------------------------------------------------------------------
    */

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,

            'course_id' => $this->courseId,
            'assignment_id' => $this->assignmentId,
            'submission_id' => $this->submissionId,
            'transaction_id' => $this->transactionId,
            'certificate_id' => $this->certificateId,
        ];
    }
}