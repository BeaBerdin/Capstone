<?php

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Enrollment;

/*
| B1: the Administrator sets the certificate title, body text, signatory
| name and position. The certificate PDF uses them; issued certificates
| keep their number and date and stay valid.
*/

function templatePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Certificate of Achievement',
        'body_text' => 'Awarded for completing every lesson and assessment.',
        'signatory_name' => 'Dr. Maria Santos',
        'signatory_position' => 'Dean, College of Computing',
    ], $overrides);
}

function issuedCertificate(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student', ['name' => 'Juan Dela Cruz']);
    $course = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Web Basics']);

    // A certificate can only be downloaded for a completed course.
    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'completed',
        'enrolled_at' => now()->subMonth(),
        'completed_at' => now()->subWeek(),
        'progress_percentage' => 100,
    ]);

    $certificate = Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PATH-2026-00042',
        'issued_date' => '2026-09-30',
        'status' => 'issued',
    ]);

    return [$student, $certificate];
}

function renderCertificatePdfHtml(Certificate $certificate): string
{
    $certificate->load(['student', 'course']);

    return view('Student.certificate-pdf', [
        'certificate' => $certificate,
        'template' => CertificateSetting::current(),
    ])->render();
}

test('without saved settings the certificate uses the original wording', function () {
    [, $certificate] = issuedCertificate();

    $html = renderCertificatePdfHtml($certificate);

    expect(CertificateSetting::count())->toBe(0)
        ->and($html)->toContain('Certificate of Completion')
        ->toContain('PATHWISE')
        ->toContain('AI-Powered E-Learning Platform')
        ->toContain('academic standards of the PathWise AI-Powered E-Learning Platform')
        ->toContain('Juan Dela Cruz')
        ->toContain('Web Basics')
        ->toContain('PATH-2026-00042');
});

test('the Administrator can open and save the certificate template', function () {
    $admin = pathwiseUser('admin');

    $this->actingAs($admin)
        ->get(route('certificate-template.edit'))
        ->assertOk()
        ->assertSee('Certificate of Completion')
        ->assertSee('AI-Powered E-Learning Platform');

    $this->actingAs($admin)
        ->put(route('certificate-template.update'), templatePayload())
        ->assertRedirect(route('certificate-template.edit'))
        ->assertSessionHas('success');

    $template = CertificateSetting::sole();

    expect($template->title)->toBe('Certificate of Achievement')
        ->and($template->signatory_name)->toBe('Dr. Maria Santos')
        ->and($template->signatory_position)->toBe('Dean, College of Computing')
        ->and($template->updated_by)->toBe($admin->id);

    // Saving again updates the same single row.
    $this->actingAs($admin)
        ->put(route('certificate-template.update'), templatePayload(['title' => 'Certificate of Excellence']));

    expect(CertificateSetting::count())->toBe(1)
        ->and(CertificateSetting::sole()->title)->toBe('Certificate of Excellence');
});

test('the certificate PDF uses the saved template and escapes it', function () {
    [, $certificate] = issuedCertificate();

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('certificate-template.update'), templatePayload([
            'body_text' => 'Awarded with <b>distinction</b>.',
        ]));

    $html = renderCertificatePdfHtml($certificate);

    expect($html)->toContain('Certificate of Achievement')
        ->toContain('Dr. Maria Santos')
        ->toContain('Dean, College of Computing')
        ->toContain('Awarded with &lt;b&gt;distinction&lt;/b&gt;.')
        ->not->toContain('<b>distinction</b>')
        ->not->toContain('AI-Powered E-Learning Platform');
});

test('an issued certificate stays valid and downloadable after the template changes', function () {
    [$student, $certificate] = issuedCertificate();

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('certificate-template.update'), templatePayload());

    $response = $this->actingAs($student)
        ->get(route('student.certificate.download', $certificate))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');

    $certificate->refresh();

    expect($certificate->certificate_number)->toBe('PATH-2026-00042')
        ->and((string) $certificate->issued_date)->toContain('2026-09-30')
        ->and($certificate->status)->toBe('issued');
});

test('the template fields are required and limited in length', function () {
    $admin = pathwiseUser('admin');

    $this->actingAs($admin)
        ->put(route('certificate-template.update'), [
            'title' => '',
            'body_text' => '',
            'signatory_name' => '',
            'signatory_position' => '',
        ])
        ->assertSessionHasErrors(['title', 'body_text', 'signatory_name', 'signatory_position']);

    $this->actingAs($admin)
        ->put(route('certificate-template.update'), templatePayload([
            'title' => str_repeat('a', 121),
            'body_text' => str_repeat('a', 1001),
        ]))
        ->assertSessionHasErrors(['title', 'body_text']);

    expect(CertificateSetting::count())->toBe(0);
});

test('the preview shows a sample PDF without creating a certificate', function () {
    $response = $this->actingAs(pathwiseUser('admin'))
        ->get(route('certificate-template.preview'))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and(Certificate::count())->toBe(0);
});

test('only the Administrator can change the certificate template', function (string $role) {
    $user = pathwiseUser($role);

    $this->actingAs($user)
        ->get(route('certificate-template.edit'))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('certificate-template.update'), templatePayload())
        ->assertForbidden();

    expect(CertificateSetting::count())->toBe(0);
})->with(['teacher', 'student', 'super_admin']);

test('the admin sidebar links to the certificate template', function () {
    $this->actingAs(pathwiseUser('admin'))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('certificate-template.edit').'"', false);
});

test('changing the template changes both the on-screen certificate and the PDF', function () {
    [$student, $certificate] = issuedCertificate();

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('certificate-template.update'), templatePayload());

    $screen = $this->actingAs($student)
        ->get(route('student.certificate.view', $certificate))
        ->assertOk()
        ->assertSee('CERTIFICATE')
        ->assertSee('OF ACHIEVEMENT')
        ->assertSee('Awarded for completing every lesson and assessment.')
        ->assertSee('Dr. Maria Santos')
        ->assertSee('Dean, College of Computing')
        ->assertDontSee('OF COMPLETION')
        ->assertDontSee('AI-Powered E-Learning Platform')
        ->getContent();

    $pdf = renderCertificatePdfHtml($certificate);

    foreach (['Awarded for completing every lesson and assessment.', 'Dr. Maria Santos', 'Dean, College of Computing'] as $wording) {
        expect($screen)->toContain($wording)
            ->and($pdf)->toContain($wording);
    }

    expect($pdf)->toContain('Certificate of Achievement');

    // A second change shows up on both as well.
    $this->actingAs(pathwiseUser('admin'))
        ->put(route('certificate-template.update'), templatePayload([
            'signatory_name' => 'Engr. Ana Reyes',
            'signatory_position' => 'Program Head',
        ]));

    $this->actingAs($student)
        ->get(route('student.certificate.view', $certificate))
        ->assertSee('Engr. Ana Reyes')
        ->assertSee('Program Head')
        ->assertDontSee('Dr. Maria Santos');

    expect(renderCertificatePdfHtml($certificate))->toContain('Engr. Ana Reyes')
        ->toContain('Program Head')
        ->not->toContain('Dr. Maria Santos');
});

test('the on-screen certificate escapes the template wording', function () {
    [$student, $certificate] = issuedCertificate();

    $this->actingAs(pathwiseUser('admin'))
        ->put(route('certificate-template.update'), templatePayload([
            'body_text' => 'Awarded with <b>distinction</b>.',
        ]));

    $this->actingAs($student)
        ->get(route('student.certificate.view', $certificate))
        ->assertOk()
        ->assertSee('Awarded with &lt;b&gt;distinction&lt;/b&gt;.', false)
        ->assertDontSee('<b>distinction</b>', false);
});

test('without saved settings the on-screen certificate shows the original wording', function () {
    [$student, $certificate] = issuedCertificate();
    CertificateSetting::query()->delete();

    $this->actingAs($student)
        ->get(route('student.certificate.view', $certificate))
        ->assertOk()
        ->assertSee('CERTIFICATE')
        ->assertSee('OF COMPLETION')
        ->assertSee(CertificateSetting::DEFAULTS['signatory_name'])
        ->assertSee(CertificateSetting::DEFAULTS['signatory_position']);
});
