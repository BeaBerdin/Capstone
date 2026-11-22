<?php

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
| Previous / next navigation at the bottom of every student lesson page
| (reading, video, activity, quiz), in the course's published lesson order.
*/

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

function navCourse(): array
{
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published', 'certificate_available' => true]);

    Enrollment::create([
        'course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active',
        'enrolled_at' => now(), 'progress_percentage' => 0,
    ]);

    return [$student, $course];
}

function navLesson(Course $course, string $title, int $order, array $attributes = []): Lesson
{
    return Lesson::create(array_merge([
        'course_id' => $course->id, 'title' => $title, 'content' => 'Content of '.$title,
        'lesson_type' => 'text', 'lesson_order' => $order, 'is_preview' => false, 'is_published' => true,
    ], $attributes));
}

function navActivity(Course $course, string $title, int $order): Assignment
{
    $lesson = navLesson($course, $title, $order, ['lesson_type' => 'activity']);

    return $lesson->assignments()->create([
        'course_id' => $course->id, 'title' => $title, 'description' => 'Answer this.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100,
        'is_activity' => true, 'is_published' => true,
    ]);
}

test('a lesson shows "Previous" and "Next" in the published lesson order', function () {
    [$student, $course] = navCourse();
    $first = navLesson($course, 'Intro to Networks', 1);
    $second = navLesson($course, 'IP Addresses', 2);
    $third = navLesson($course, 'Subnetting', 3);

    $this->actingAs($student)
        ->get(route('student.lesson.view', $second))
        ->assertOk()
        ->assertSee('data-test="lesson-nav"', false)
        ->assertSee('Previous: Intro to Networks')
        ->assertSee('href="'.route('student.lesson.view', $first).'"', false)
        ->assertSee('Next: Subnetting')
        ->assertSee('href="'.route('student.lesson.view', $third).'"', false)
        ->assertSee('data-test="lesson-nav-course"', false);
});

test('unpublished lessons are skipped', function () {
    [$student, $course] = navCourse();
    $first = navLesson($course, 'Intro to Networks', 1);
    navLesson($course, 'Draft Lesson', 2, ['is_published' => false]);
    navLesson($course, 'Subnetting', 3);

    $this->actingAs($student)
        ->get(route('student.lesson.view', $first))
        ->assertOk()
        ->assertSee('Next: Subnetting')
        ->assertDontSee('Draft Lesson');
});

test('the first lesson has no Previous', function () {
    [$student, $course] = navCourse();
    $first = navLesson($course, 'Intro to Networks', 1);
    navLesson($course, 'IP Addresses', 2);

    $this->actingAs($student)
        ->get(route('student.lesson.view', $first))
        ->assertOk()
        ->assertDontSee('data-test="lesson-nav-previous"', false)
        ->assertSee('Next: IP Addresses');
});

test('the last lesson shows Back to course instead of Next', function () {
    [$student, $course] = navCourse();
    navLesson($course, 'Intro to Networks', 1);
    $last = navLesson($course, 'Wrap-up', 2);

    $this->actingAs($student)
        ->get(route('student.lesson.view', $last))
        ->assertOk()
        ->assertSee('Previous: Intro to Networks')
        ->assertDontSee('data-test="lesson-nav-next"', false)
        ->assertSee('data-test="lesson-nav-finish"', false)
        ->assertSee('href="'.route('student.learn.course', $course).'"', false)
        ->assertDontSee('data-test="lesson-nav-certificate"', false);
});

test('the last lesson links to the certificate once it is issued', function () {
    [$student, $course] = navCourse();
    $last = navLesson($course, 'Wrap-up', 1);
    LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $last->id, 'status' => 'completed', 'completed_at' => now()]);
    Certificate::create([
        'student_id' => $student->id, 'course_id' => $course->id, 'certificate_number' => 'PATH-NAV-1',
        'issued_date' => now()->toDateString(), 'status' => 'issued',
    ]);

    $this->actingAs($student)
        ->get(route('student.lesson.view', $last))
        ->assertOk()
        ->assertSee('data-test="lesson-nav-certificate"', false)
        ->assertSee('View certificate')
        ->assertSee('href="'.route('student.certificates').'"', false);
});

test('a locked next step is shown disabled with the reason and is not a link', function () {
    [$student, $course] = navCourse();
    $first = navLesson($course, 'Intro to Networks', 1);
    $last = navLesson($course, 'Wrap-up', 2);
    $finalQuiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => null, 'title' => 'Final Exam', 'passing_score' => 60, 'is_published' => true]);

    // Not all lessons completed: the final quiz is locked (as on the course page).
    $html = $this->actingAs($student)
        ->get(route('student.lesson.view', $last))
        ->assertOk()
        ->assertSee('data-test="lesson-nav-next-locked"', false)
        ->assertSee('Next: Final Exam')
        ->assertSee('Complete all lessons first')
        ->getContent();

    expect($html)->not->toContain('href="'.route('student.quiz.take', $finalQuiz).'"');

    // Every lesson completed: the same step becomes a normal link.
    foreach ([$first, $last] as $lesson) {
        LessonProgress::updateOrCreate(['student_id' => $student->id, 'lesson_id' => $lesson->id], ['status' => 'completed', 'completed_at' => now()]);
    }

    $this->actingAs($student)
        ->get(route('student.lesson.view', $last))
        ->assertOk()
        ->assertSee('data-test="lesson-nav-next"', false)
        ->assertSee('href="'.route('student.quiz.take', $finalQuiz).'"', false)
        ->assertDontSee('data-test="lesson-nav-next-locked"', false);
});

test('an activity follows its lesson position, also right after a successful submission', function () {
    [$student, $course] = navCourse();
    navLesson($course, 'Intro to Networks', 1);
    $activity = navActivity($course, 'Activity: Subnet a Network', 2);
    $next = navLesson($course, 'Routing Basics', 3);

    $this->actingAs($student)
        ->get(route('student.assignments.show', $activity))
        ->assertOk()
        ->assertSee('Previous: Intro to Networks')
        ->assertSee('Next: Routing Basics');

    $this->actingAs($student)
        ->followingRedirects()
        ->post(route('student.assignments.submit', $activity), ['answer_text' => 'My subnet plan'])
        ->assertOk()
        ->assertSee('Assignment submitted successfully')
        ->assertSee('Next: Routing Basics')
        ->assertSee('href="'.route('student.lesson.view', $next).'"', false);
});

test('a quiz lesson page has the navigation, and Next links straight to the next quiz', function () {
    [$student, $course] = navCourse();
    navLesson($course, 'Intro to Networks', 1);
    $quizLesson = navLesson($course, 'Checkpoint 1', 2, ['lesson_type' => 'quiz']);
    $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson->id, 'title' => 'Checkpoint 1', 'passing_score' => 60, 'is_published' => true]);
    QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Q?', 'option_a' => 'Right', 'option_b' => 'Wrong', 'correct_answer' => 'A', 'points' => 1]);
    $quizLesson2 = navLesson($course, 'Checkpoint 2', 3, ['lesson_type' => 'quiz']);
    $quiz2 = Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson2->id, 'title' => 'Checkpoint 2', 'passing_score' => 60, 'is_published' => true]);

    $this->actingAs($student)
        ->get(route('student.quiz.take', $quiz))
        ->assertOk()
        ->assertSee('Previous: Intro to Networks')
        ->assertSee('Next: Checkpoint 2')
        ->assertSee('href="'.route('student.quiz.take', $quiz2).'"', false);
});

test('a standalone assignment shows only Back to course', function () {
    [$student, $course] = navCourse();
    navLesson($course, 'Intro to Networks', 1);
    $assignment = Assignment::create([
        'course_id' => $course->id, 'title' => 'Final Essay', 'description' => 'Write.',
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100, 'is_published' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('data-test="lesson-nav-course"', false)
        ->assertDontSee('data-test="lesson-nav-previous"', false)
        ->assertDontSee('data-test="lesson-nav-next"', false);
});

test('on phones the buttons stack full width with large tap targets and truncated titles', function () {
    [$student, $course] = navCourse();
    navLesson($course, 'Intro to Networks', 1);
    $second = navLesson($course, 'A very long lesson title that would otherwise wrap onto several lines on a phone', 2);
    navLesson($course, 'Subnetting', 3);

    $html = $this->actingAs($student)->get(route('student.lesson.view', $second))->assertOk()->getContent();

    preg_match('/<nav aria-label="Lesson navigation".*?<\/nav>/s', $html, $nav);

    expect($nav[0])->toContain('flex-col')
        ->toContain('sm:flex-row')
        ->toContain('w-full')
        ->toContain('sm:w-auto')
        ->toContain('min-h-12')
        ->toContain('truncate');
});

test('the navigation never unlocks content on the server', function () {
    [$student, $course] = navCourse();
    $other = pathwiseCourse(pathwiseApprovedTeacher(), ['status' => 'published']);
    $foreign = navLesson($other, 'Not my course', 1);

    // Showing navigation does not grant access to lessons of another course.
    $this->actingAs($student)->get(route('student.lesson.view', $foreign))->assertForbidden();
});
