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
        public string $status,
        public ?string $remarks = null
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            'database',
        ];
    }

    /**
     * @return array<string, mixed>
     */
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

        if ($this->status === 'rejected' && filled($this->remarks)) {
            $message .= ' Administrator feedback: ' . $this->remarks;
        }

        return [
            'message' => $message,
            'remarks' => $this->remarks,
            'course_id' => $this->courseId,
            'course_title' => $this->courseTitle,
            'status' => $this->status,
        ];
    }
}
