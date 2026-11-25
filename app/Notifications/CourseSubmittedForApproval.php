<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\User;
use App\Notifications\Channels\SafeMailChannel;
use App\Support\EdpNotificationRecipients;
use App\Support\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the reviewers of a course: a teacher submitted (or, after Return for
 * Revision, resubmitted) it for approval. The reviewers are the
 * Administrators of the teacher's department, or the Super Admin when there
 * is none. Saved in-app (unread until opened); emailed once when mail is
 * configured. Sent immediately like every PATHWISE email (no queue worker
 * on the server). A failed email is logged by SafeMailChannel and never
 * undoes the submission.
 */
class CourseSubmittedForApproval extends Notification
{
    use Queueable;

    public const TYPE = 'course_submitted';

    public function __construct(
        public int $courseId,
        public string $courseTitle,
        public string $teacherName,
        public bool $resubmission = false,
        public bool $toEdpMailbox = false,
    ) {
    }

    public static function reviewUrl(int $courseId): string
    {
        return route('courses.index', ['review' => $courseId]).'#course-'.$courseId;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // The configured EDP mailbox (Super Admin fallback): email only.
        if ($notifiable instanceof AnonymousNotifiable) {
            return MailSettings::isConfigured() ? [SafeMailChannel::class] : [];
        }

        $channels = ['database'];

        // A Super Admin's login email is used only when no EDP mailbox is
        // configured (that mailbox then gets the single email instead).
        $usesMailbox = $this->toEdpMailbox && EdpNotificationRecipients::hasConfigured();

        if (
            MailSettings::isConfigured()
            && ! $usesMailbox
            && EdpNotificationRecipients::isDeliverable($notifiable->email ?? null)
        ) {
            $channels[] = SafeMailChannel::class;
        }

        return $channels;
    }

    public function message(): string
    {
        return $this->teacherName.($this->resubmission ? ' resubmitted ' : ' submitted ').$this->courseTitle.' for approval.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $heading = $this->resubmission ? 'Course resubmitted for approval' : 'Course submitted for approval';

        return (new MailMessage)
            ->subject('PathWise - '.$heading.': '.$this->courseTitle)
            ->view(['emails.pathwise-message', 'emails.pathwise-message-text'], [
                'heading' => $heading,
                'preheader' => $this->message(),
                'name' => $notifiable instanceof User ? $notifiable->name : null,
                'paragraphs' => [
                    $this->message(),
                    'Course: '.$this->courseTitle,
                    'Teacher: '.$this->teacherName,
                    $this->resubmission
                        ? 'The course was returned for revision earlier. Please review the changes, then approve it or return it for revision.'
                        : 'Please review the course, then approve it or return it for revision.',
                ],
                'buttonText' => 'Review course',
                'buttonUrl' => self::reviewUrl($this->courseId),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->resubmission ? 'Course resubmitted for approval' : 'Course submitted for approval',
            'message' => $this->message(),
            'type' => self::TYPE,
            'course_id' => $this->courseId,
            'course_title' => $this->courseTitle,
            'teacher_name' => $this->teacherName,
            'resubmission' => $this->resubmission,
        ];
    }
}
