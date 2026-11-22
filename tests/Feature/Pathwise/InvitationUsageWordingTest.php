<?php

use App\Models\CourseInvitation;
use App\Models\Enrollment;
use Illuminate\Support\Str;

/*
| The teacher's invitation list says how many students joined through each
| invitation ("1 student joined", "N students joined"), not "free redemptions".
*/

beforeEach(function () {
    $this->withoutVite();
});

function usageInvitation(App\Models\Course $course, App\Models\User $teacher, int $joined): CourseInvitation
{
    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $teacher->id,
        'code' => strtoupper(Str::random(12)),
    ]);

    for ($i = 0; $i < $joined; $i++) {
        $student = pathwiseUser('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
            'progress_percentage' => 0,
        ]);
        $invitation->redeemedBy()->attach($student->id, ['enrollment_id' => $enrollment->id]);
    }

    return $invitation;
}

test('invitation usage reads "N students joined", singular for one', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    usageInvitation($course, $teacher, 0);
    usageInvitation($course, $teacher, 1);
    usageInvitation($course, $teacher, 2);

    $html = $this->actingAs($teacher)
        ->get(route('course-invitations.index'))
        ->assertOk()
        ->getContent();

    preg_match_all('/data-test="invitation-usage">([^<]+)</', $html, $matches);

    expect($matches[1])->toEqualCanonicalizing(['0 students joined', '1 student joined', '2 students joined'])
        ->and($html)->not->toContain('free redemption')
        ->and($html)->not->toContain('1 students joined');
});
