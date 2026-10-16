<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Course;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Administrator (Department Head) settings for the certificate PDF:
 * title, body text, signatory name and position.
 */
class CertificateTemplateController extends Controller
{
    public function edit()
    {
        return view('certificate-template.edit', [
            'template' => CertificateSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body_text' => ['required', 'string', 'max:1000'],
            'signatory_name' => ['required', 'string', 'max:120'],
            'signatory_position' => ['required', 'string', 'max:120'],
        ]);

        $template = CertificateSetting::query()->oldest('id')->first()
            ?? new CertificateSetting;

        $template->fill($validated + ['updated_by' => auth()->id()])->save();

        return redirect()
            ->route('certificate-template.edit')
            ->with('success', 'Certificate template saved. Certificates downloaded from now on use it.');
    }

    /**
     * A sample certificate with the saved settings, so the Administrator
     * can check the layout. Nothing is stored.
     */
    public function preview()
    {
        $certificate = (new Certificate)->forceFill([
            'certificate_number' => 'PATH-'.now()->year.'-00000',
            'issued_date' => now()->toDateString(),
            'status' => 'issued',
        ]);

        $certificate->setRelation('student', (new User)->forceFill(['name' => 'Sample Student']));
        $certificate->setRelation('course', (new Course)->forceFill(['title' => 'Sample Course']));

        return Pdf::loadView('Student.certificate-pdf', [
            'certificate' => $certificate,
            'template' => CertificateSetting::current(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream('certificate-preview.pdf');
    }
}
