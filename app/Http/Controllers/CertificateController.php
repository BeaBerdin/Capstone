<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\CertificateEligibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $certificates = Certificate::with([
                'student',
                'course',
            ])
            ->whereHas('course', fn ($course) => $course->manageableBy($request->user()))
            ->latest()
            ->get();

        return view(
            'certificates.index',
            compact('certificates')
        );
    }


    public function create(Request $request)
    {
        $students = $this->certificateStudents($request->user())->orderBy('name')->get();
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        return view(
            'certificates.create',
            compact(
                'students',
                'courses'
            )
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' =>
                'required|exists:users,id',

            'course_id' =>
                'required|integer|exists:courses,id',

            'certificate_number' =>
                'required|string|max:255|unique:certificates,certificate_number',

            'issued_date' =>
                'required|date',

            'certificate_file' =>
                'nullable|string|max:255',

            'status' =>
                'required|in:issued,revoked',
        ]);

        Course::authorizeManagement($request->user(), $validated['course_id']);

        $this->ensureNoDuplicateCertificate(
            (int) $validated['student_id'],
            (int) $validated['course_id']
        );

        $this->ensureStudentCompletedCourse(
            (int) $validated['student_id'],
            (int) $validated['course_id']
        );

        if ($validated['status'] === 'issued') {
            $this->ensureActivitiesPassed((int) $validated['student_id'], (int) $validated['course_id']);
        }

        $certificate = Certificate::create($validated);

        if ($certificate->status === 'issued') {
            $this->notifyStudentAboutIssuedCertificate(
                $certificate
            );
        }

        return redirect()
            ->route('certificates.index')
            ->with(
                'success',
                'Certificate created successfully.'
            );
    }


    public function edit(Request $request, Certificate $certificate)
    {
        Course::authorizeManagement($request->user(), $certificate->course_id);

        $students = $this->certificateStudents($request->user())->orderBy('name')->get();
        $courses = Course::manageableBy($request->user())->orderBy('title')->get();

        return view(
            'certificates.edit',
            compact(
                'certificate',
                'students',
                'courses'
            )
        );
    }


    public function update(
        Request $request,
        Certificate $certificate
    ) {
        Course::authorizeManagement($request->user(), $certificate->course_id);

        $validated = $request->validate([
            'student_id' =>
                'required|exists:users,id',

            'course_id' =>
                'required|integer|exists:courses,id',

            'certificate_number' =>
                'required|string|max:255|unique:certificates,certificate_number,'
                . $certificate->id,

            'issued_date' =>
                'required|date',

            'certificate_file' =>
                'nullable|string|max:255',

            'status' =>
                'required|in:issued,revoked',
        ]);

        Course::authorizeManagement($request->user(), $validated['course_id']);

        $this->ensureNoDuplicateCertificate(
            (int) $validated['student_id'],
            (int) $validated['course_id'],
            $certificate->id
        );

        $this->ensureStudentCompletedCourse(
            (int) $validated['student_id'],
            (int) $validated['course_id']
        );

        $wasIssued =
            strtolower(
                trim(
                    (string) $certificate->status
                )
            )
            ===
            'issued';

        // Issuing (or re-issuing a revoked one, or moving it to another
        // student or course) needs passing final grades on every activity.
        // An already-issued certificate that is only edited is left as is.
        if (
            $validated['status'] === 'issued'
            && (
                ! $wasIssued
                || (int) $certificate->student_id !== (int) $validated['student_id']
                || (int) $certificate->course_id !== (int) $validated['course_id']
            )
        ) {
            $this->ensureActivitiesPassed((int) $validated['student_id'], (int) $validated['course_id']);
        }

        $previousStudentId =
            (int) $certificate->student_id;

        $previousCourseId =
            (int) $certificate->course_id;

        $certificate->update($validated);

        $certificate->refresh();

        $isIssuedNow =
            strtolower(
                trim(
                    (string) $certificate->status
                )
            )
            ===
            'issued';

        $issuedRecipientChanged =
            $previousStudentId !== (int) $certificate->student_id
            ||
            $previousCourseId !== (int) $certificate->course_id;

        if (
            $isIssuedNow
            &&
            (
                ! $wasIssued
                ||
                $issuedRecipientChanged
            )
        ) {
            $this->notifyStudentAboutIssuedCertificate(
                $certificate
            );
        }

        return redirect()
            ->route('certificates.index')
            ->with(
                'success',
                'Certificate updated successfully.'
            );
    }


    public function destroy(Request $request, Certificate $certificate)
    {
        Course::authorizeManagement($request->user(), $certificate->course_id);

        $certificate->delete();

        return redirect()
            ->route('certificates.index')
            ->with(
                'success',
                'Certificate deleted successfully.'
            );
    }

    private function certificateStudents(User $administrator): Builder
    {
        return User::query()->when(! $administrator->hasRole('super_admin'), fn (Builder $students) => $students
            ->whereIn('id', $this->completedEnrollments()
                ->whereHas('course', fn ($course) => $course->manageableBy($administrator))
                ->select('student_id')));
    }

    private function completedEnrollments(): Builder
    {
        return Enrollment::where('status', 'completed')->where('progress_percentage', '>=', 100);
    }


    /*
    |--------------------------------------------------------------------------
    | STUDENT CERTIFICATE ACCESS
    |--------------------------------------------------------------------------
    */

    public function studentView(Certificate $certificate)
    {
        $this->ensureStudentCanAccessCertificate(
            $certificate
        );

        $certificate->load([
            'student',
            'course',
        ]);

        // Same wording as the PDF: the Administrator's current template.
        $template = CertificateSetting::current();

        return view(
            'Student.certificate-view',
            compact('certificate', 'template')
        );
    }


    public function download(Certificate $certificate)
    {
        $this->ensureStudentCanAccessCertificate(
            $certificate
        );

        $certificate->load([
            'student',
            'course',
        ]);

        // The Administrator's current template; the certificate's own
        // number, date, student and course are never changed.
        $template = CertificateSetting::current();

        $pdf = Pdf::loadView(
                'Student.certificate-pdf',
                compact('certificate', 'template')
            )
            ->setPaper(
                'a4',
                'landscape'
            );

        $safeCertificateNumber = preg_replace(
            '/[^A-Za-z0-9\-_]/',
            '_',
            (string) $certificate->certificate_number
        );

        return $pdf->download(
            $safeCertificateNumber . '.pdf'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE NOTIFICATION
    |--------------------------------------------------------------------------
    | Only an issued certificate notifies the student. Updating ordinary
    | certificate details does not generate duplicate bell notifications.
    */

    private function notifyStudentAboutIssuedCertificate(
        Certificate $certificate
    ): void {
        // Shared with automatic certificates (lesson and quiz completion).
        $certificate->notifyStudent();
    }


    /*
    |--------------------------------------------------------------------------
    | CERTIFICATE SECURITY HELPERS
    |--------------------------------------------------------------------------
    */

    private function ensureStudentCanAccessCertificate(
        Certificate $certificate
    ): void {
        if (
            (int) $certificate->student_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'You are not authorized to access this certificate.'
            );
        }

        if (
            strtolower(
                trim(
                    (string) $certificate->status
                )
            )
            !==
            'issued'
        ) {
            abort(
                404,
                'Certificate not found.'
            );
        }

        $this->ensureStudentCompletedCourse(
            (int) $certificate->student_id,
            (int) $certificate->course_id
        );
    }


    private function ensureStudentCompletedCourse(
        int $studentId,
        int $courseId
    ): void {
        $completedEnrollment = $this->completedEnrollments()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->exists();

        if (!$completedEnrollment) {
            abort(
                403,
                'A certificate is only available after the course has been completed.'
            );
        }
    }


    /**
     * Refuse issuing while an activity/assignment has no passing final
     * teacher grade (see CertificateEligibility).
     */
    private function ensureActivitiesPassed(int $studentId, int $courseId): void
    {
        $course = Course::findOrFail($courseId);
        $blockers = CertificateEligibility::activityBlockers($course, $studentId);

        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'status' => 'The certificate cannot be issued yet: '.implode('; ', $blockers).'.',
            ]);
        }
    }

    private function ensureNoDuplicateCertificate(
        int $studentId,
        int $courseId,
        ?int $ignoreCertificateId = null
    ): void {
        $query = Certificate::where(
                'student_id',
                $studentId
            )
            ->where(
                'course_id',
                $courseId
            );

        if ($ignoreCertificateId !== null) {
            $query->where(
                'id',
                '!=',
                $ignoreCertificateId
            );
        }

        if ($query->exists()) {
            abort(
                422,
                'A certificate already exists for this student and course.'
            );
        }
    }
}
