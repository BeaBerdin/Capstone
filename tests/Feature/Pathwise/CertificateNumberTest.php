<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;

/*
| Manuscript: Functional Requirement "Certificate Generation". Every
| automatically issued certificate gets a unique PATH-YYYY-00001 number,
| whether the course is completed through a lesson or a final quiz.
*/

function certificateCourseSetup(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'price' => 0]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Only lesson',
        'content' => 'Content',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_preview' => false,
        'is_published' => true,
    ]);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    return [$student, $course, $lesson];
}

test('completing the last lesson issues a PATH-YYYY sequence certificate number', function () {
    [$student, $course, $lesson] = certificateCourseSetup();

    $this->actingAs($student)
        ->post(route('student.lesson.complete', $lesson))
        ->assertSessionHas('success');

    $certificate = Certificate::where('student_id', $student->id)
        ->where('course_id', $course->id)
        ->sole();

    expect($certificate->certificate_number)->toBe('PATH-'.now()->year.'-00001');
});

test('a lesson certificate does not collide with a number built from other student and course IDs', function () {
    [$student, $course, $lesson] = certificateCourseSetup();

    // The old lesson code built PW-YYYY-{studentId}{courseId}, so another
    // student/course pair with the same digits caused a duplicate number.
    $otherStudent = pathwiseUser('student');
    Certificate::create([
        'student_id' => $otherStudent->id,
        'course_id' => $course->id,
        'certificate_number' => 'PW-'.now()->year.'-'.str_pad($student->id.$course->id, 5, '0', STR_PAD_LEFT),
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    $this->actingAs($student)
        ->post(route('student.lesson.complete', $lesson))
        ->assertSessionHas('success');

    expect(Certificate::where('student_id', $student->id)->value('certificate_number'))
        ->toBe('PATH-'.now()->year.'-00001');
});

test('certificate numbers continue the PATH-YYYY sequence', function () {
    [$student, $course] = certificateCourseSetup();
    $year = now()->year;

    Certificate::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'certificate_number' => "PATH-{$year}-00004",
        'issued_date' => now()->toDateString(),
        'status' => 'issued',
    ]);

    expect(Certificate::generateNumber())->toBe("PATH-{$year}-00005");
});
