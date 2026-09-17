<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = Certificate::with([
                'student',
                'course',
            ])
            ->latest()
            ->get();

        return view(
            'certificates.index',
            compact('certificates')
        );
    }


    public function create()
    {
        $students = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

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
                'required|exists:courses,id',

            'certificate_number' =>
                'required|string|max:255|unique:certificates,certificate_number',

            'issued_date' =>
                'required|date',

            'certificate_file' =>
                'nullable|string|max:255',

            'status' =>
                'required|in:issued,revoked',
        ]);

        $this->ensureNoDuplicateCertificate(
            (int) $validated['student_id'],
            (int) $validated['course_id']
        );

        $this->ensureStudentCompletedCourse(
            (int) $validated['student_id'],
            (int) $validated['course_id']
        );

        Certificate::create($validated);

        return redirect()
            ->route('certificates.index')
            ->with(
                'success',
                'Certificate created successfully.'
            );
    }


    public function edit(Certificate $certificate)
    {
        $students = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

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
        $validated = $request->validate([
            'student_id' =>
                'required|exists:users,id',

            'course_id' =>
                'required|exists:courses,id',

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

        $this->ensureNoDuplicateCertificate(
            (int) $validated['student_id'],
            (int) $validated['course_id'],
            $certificate->id
        );

        $this->ensureStudentCompletedCourse(
            (int) $validated['student_id'],
            (int) $validated['course_id']
        );

        $certificate->update($validated);

        return redirect()
            ->route('certificates.index')
            ->with(
                'success',
                'Certificate updated successfully.'
            );
    }


    public function destroy(Certificate $certificate)
    {
        $certificate->delete();

        return redirect()
            ->route('certificates.index')
            ->with(
                'success',
                'Certificate deleted successfully.'
            );
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

        return view(
            'student.certificate-view',
            compact('certificate')
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

        $pdf = Pdf::loadView(
                'student.certificate-pdf',
                compact('certificate')
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
        $completedEnrollment = Enrollment::where(
                'student_id',
                $studentId
            )
            ->where(
                'course_id',
                $courseId
            )
            ->where(
                'status',
                'completed'
            )
            ->where(
                'progress_percentage',
                '>=',
                100
            )
            ->exists();

        if (!$completedEnrollment) {
            abort(
                403,
                'A certificate is only available after the course has been completed.'
            );
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
