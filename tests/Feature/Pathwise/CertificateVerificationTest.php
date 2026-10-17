<?php

use App\Models\Certificate;
use App\Models\CertificateSetting;

/*
| Public certificate verification at the "Verify at" address on the
| certificate: valid or revoked, student name, course and issue date only.
*/

function verifiableCertificate(string $status = 'issued'): Certificate
{
    $student = pathwiseUser('student', [
        'name' => 'Juan Dela Cruz',
        'email' => 'juan.private@example.com',
    ]);

    $course = pathwiseCourse(pathwiseUser('teacher'), ['status' => 'published', 'title' => 'Web Development Basics']);

    return Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => 'PATH-2026-00123',
        'issued_date' => '2026-05-20',
        'status' => $status,
    ]);
}

test('the verification page is public and shows a form', function () {
    $this->get(route('certificates.verify'))
        ->assertOk()
        ->assertSee('Verify a Certificate')
        ->assertSee('name="number"', false)
        ->assertDontSee('data-verification=', false);
});

test('a valid certificate shows the status, student name, course and issue date', function () {
    verifiableCertificate();

    $this->get(route('certificates.verify', ['number' => 'PATH-2026-00123']))
        ->assertOk()
        ->assertSee('data-verification="valid"', false)
        ->assertSee('Valid certificate')
        ->assertSee('Juan Dela Cruz')
        ->assertSee('Web Development Basics')
        ->assertSee('May 20, 2026');
});

test('the number is matched without case or extra spaces', function () {
    verifiableCertificate();

    $this->get(route('certificates.verify', ['number' => '  path-2026-00123 ']))
        ->assertOk()
        ->assertSee('data-verification="valid"', false);
});

test('a revoked certificate is shown as revoked', function () {
    verifiableCertificate('revoked');

    $this->get(route('certificates.verify', ['number' => 'PATH-2026-00123']))
        ->assertOk()
        ->assertSee('data-verification="revoked"', false)
        ->assertSee('This certificate has been revoked')
        ->assertDontSee('Valid certificate');
});

test('an unknown number shows that no certificate was found', function () {
    verifiableCertificate();

    $this->get(route('certificates.verify', ['number' => 'PATH-2026-99999']))
        ->assertOk()
        ->assertSee('data-verification="not-found"', false)
        ->assertSee('No certificate found')
        ->assertDontSee('Juan Dela Cruz');
});

test('nothing else about the student is shown', function () {
    $certificate = verifiableCertificate();
    $student = $certificate->student;

    $this->get(route('certificates.verify', ['number' => 'PATH-2026-00123']))
        ->assertOk()
        ->assertDontSee('juan.private@example.com')
        ->assertDontSee(route('student.certificate.view', $certificate), false)
        ->assertViewHas('result', fn (array $result) => array_keys($result) === [
            'found', 'valid', 'number', 'student', 'course', 'issued',
        ] && ! in_array($student->email, $result, true));
});

test('a number that is too long is rejected', function () {
    $this->get(route('certificates.verify', ['number' => str_repeat('A', 51)]))
        ->assertSessionHasErrors('number');
});

test('lookups are limited to 10 per minute', function () {
    verifiableCertificate();

    foreach (range(1, 10) as $attempt) {
        $this->get(route('certificates.verify', ['number' => 'PATH-2026-'.str_pad((string) $attempt, 5, '0', STR_PAD_LEFT)]))
            ->assertOk();
    }

    $this->get(route('certificates.verify', ['number' => 'PATH-2026-00123']))
        ->assertStatus(429);

    // After a minute the page works again.
    $this->travel(61)->seconds();

    $this->get(route('certificates.verify', ['number' => 'PATH-2026-00123']))
        ->assertOk()
        ->assertSee('data-verification="valid"', false);
});

test('the certificate PDF prints the verification address', function () {
    $certificate = verifiableCertificate();
    $certificate->load(['student', 'course']);

    $html = view('Student.certificate-pdf', [
        'certificate' => $certificate,
        'template' => CertificateSetting::current(),
    ])->render();

    expect($html)->toContain('Verify at: '.route('certificates.verify'))
        ->not->toContain('Verify at: pathwise.test');
});
