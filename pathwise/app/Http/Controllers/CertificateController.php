<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function index()
    {
        $certificates = Certificate::with(['student', 'course'])
            ->latest()
            ->get();

        return view('certificates.index', compact('certificates'));
    }

    public function create()
    {
        $students = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

        return view('certificates.create', compact('students', 'courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'certificate_number' => 'required|string|max:255|unique:certificates,certificate_number',
            'issued_date' => 'required|date',
            'certificate_file' => 'nullable|string|max:255',
            'status' => 'required|in:issued,revoked',
        ]);

        Certificate::create($validated);

        return redirect()
            ->route('certificates.index')
            ->with('success', 'Certificate created successfully.');
    }

    public function edit(Certificate $certificate)
    {
        $students = User::orderBy('name')->get();
        $courses = Course::orderBy('title')->get();

        return view('certificates.edit', compact(
            'certificate',
            'students',
            'courses'
        ));
    }

    public function update(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'certificate_number' => 'required|string|max:255|unique:certificates,certificate_number,' . $certificate->id,
            'issued_date' => 'required|date',
            'certificate_file' => 'nullable|string|max:255',
            'status' => 'required|in:issued,revoked',
        ]);

        $certificate->update($validated);

        return redirect()
            ->route('certificates.index')
            ->with('success', 'Certificate updated successfully.');
    }

    
   public function destroy(Certificate $certificate)
{
    $certificate->delete();

    return redirect()
        ->route('certificates.index')
        ->with('success', 'Certificate deleted successfully.');
}

public function studentView(Certificate $certificate)
{
    if ($certificate->student_id != auth()->id()) {
        abort(403);
    }

    $certificate->load([
        'student',
        'course'
    ]);

    return view(
        'student.certificate-view',
        compact('certificate')
    );
}

public function download(Certificate $certificate)
{
    if ($certificate->student_id != auth()->id()) {
        abort(403);
    }

    $certificate->load(['student', 'course']);

    $pdf = Pdf::loadView('student.certificate-pdf', compact('certificate'))
        ->setPaper('a4', 'landscape');

    return $pdf->download(
        $certificate->certificate_number . '.pdf'
    );
}
}