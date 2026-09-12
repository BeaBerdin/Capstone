<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $courseId,
        public string $courseTitle,
        public string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return [
            'database',
        ];
    }

    public function toArray(object $notifiable): array
    {
        $message = match ($this->status) {
            'published' =>
                'Your course "' . $this->courseTitle . '" has been approved and published.',

            'rejected' =>
                'Your course "' . $this->courseTitle . '" was returned for revision. Please review the course and submit it again.',

            default =>
                'The status of your course "' . $this->courseTitle . '" has been updated.',
        };

        return [
            'message' => $message,
            'course_id' => $this->courseId,
            'course_title' => $this->courseTitle,
            'status' => $this->status,
        ];
    }
}
