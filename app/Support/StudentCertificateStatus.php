<?php

namespace App\Support;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;

/**
 * The single source of truth for the certificate button a student sees
 * (dashboard, My Courses, course pages):
 *  - "issued":  an issued certificate exists -> green "View Certificate";
 *  - "pending": the course is completed (enrollment status "completed") and
 *               offers a certificate, but none is issued yet -> disabled
 *               "Certificate pending", with the first reason from
 *               CertificateEligibility when there is one;
 *  - "none":    not completed, or no certificate offered -> no button.
 * Display only: viewing/downloading still needs an issued certificate.
 */
class StudentCertificateStatus
{
    public const ISSUED = 'issued';

    public const PENDING = 'pending';

    public const NONE = 'none';

    /**
     * @return array{state: string, certificate: ?Certificate, reason: ?string}
     */
    public static function for(?Enrollment $enrollment, ?Course $course, int $studentId, ?Certificate $issued = null): array
    {
        $none = ['state' => self::NONE, 'certificate' => null, 'reason' => null];

        if (! $course || ! $course->certificate_available) {
            return $none;
        }

        $issued ??= Certificate::query()
            ->where('student_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'issued')
            ->first();

        if ($issued) {
            return ['state' => self::ISSUED, 'certificate' => $issued, 'reason' => null];
        }

        if (! $enrollment || $enrollment->status !== 'completed') {
            return $none;
        }

        $reasons = CertificateEligibility::blockers($course, $studentId);

        return [
            'state' => self::PENDING,
            'certificate' => null,
            'reason' => $reasons[0] ?? 'Your certificate has not been issued yet.',
        ];
    }

    /**
     * Issued certificates of a student, keyed by course id (one query for a
     * list of course cards).
     *
     * @return \Illuminate\Support\Collection<int, Certificate>
     */
    public static function issuedByCourse(int $studentId)
    {
        return Certificate::query()
            ->where('student_id', $studentId)
            ->where('status', 'issued')
            ->get()
            ->keyBy('course_id');
    }
}
