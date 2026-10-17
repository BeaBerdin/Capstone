<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

/**
 * Public certificate verification: anyone (for example an employer) can
 * check a certificate number printed on a PATHWISE certificate.
 *
 * Only the status (valid or revoked), the student's name, the course and
 * the issue date are shown; nothing else about the student. The route is
 * rate-limited.
 */
class CertificateVerificationController extends Controller
{
    public function show(Request $request)
    {
        $validated = $request->validate([
            'number' => ['nullable', 'string', 'max:50'],
        ]);

        $number = strtoupper(trim((string) ($validated['number'] ?? '')));

        $result = null;

        if ($number !== '') {
            $certificate = Certificate::with(['student:id,name', 'course:id,title'])
                ->where('certificate_number', $number)
                ->first();

            $result = $certificate
                ? [
                    'found' => true,
                    'valid' => strtolower(trim((string) $certificate->status)) === 'issued',
                    'number' => $certificate->certificate_number,
                    'student' => $certificate->student?->name,
                    'course' => $certificate->course?->title,
                    'issued' => $certificate->issued_date
                        ? \Illuminate\Support\Carbon::parse($certificate->issued_date)->format('F d, Y')
                        : null,
                ]
                : ['found' => false, 'number' => $number];
        }

        return view('certificates.verify', compact('number', 'result'));
    }
}
