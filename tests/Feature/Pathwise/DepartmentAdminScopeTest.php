<?php

use App\Models\AIRecommendation;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\StudentRemovalRequest;
use App\Models\Submission;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    Storage::fake('local');
});

function departmentScopeCourse(?Department $department, string $label): Course
{
    return pathwiseCourse(pathwiseUser('teacher', ['department_id' => $department?->id]), [
        'title' => "$label course",
        'status' => 'pending',
    ]);
}

function departmentScopeRecords(Course $course, string $label): array
{
    $student = pathwiseUser('student', ['name' => "$label learner"]);
    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => "$label lesson",
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'file_path' => "lesson-files/$label.txt",
    ]);
    $quiz = Quiz::create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'title' => "$label quiz",
        'passing_score' => 70,
        'is_published' => true,
    ]);
    $question = $quiz->questions()->create([
        'question' => "$label question",
        'option_a' => 'Alpha',
        'option_b' => 'Bravo',
        'option_c' => 'Charlie',
        'option_d' => 'Delta',
        'correct_answer' => 'A',
        'points' => 1,
    ]);
    QuizResult::create([
        'quiz_id' => $quiz->id, 'student_id' => $student->id, 'score' => 1,
        'total_items' => 1, 'percentage' => 100, 'remarks' => 'passed', 'completed_at' => now(),
    ]);
    $assignment = Assignment::create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'title' => "$label assignment",
        'submission_type' => 'essay',
        'max_score' => 100,
        'is_published' => true,
    ]);
    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'student_id' => $student->id,
        'answer_text' => "$label answer",
        'file_path' => "assignment-submissions/$label.txt",
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
    $enrollment = Enrollment::create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => 'completed',
        'enrolled_at' => now(),
        'completed_at' => now(),
        'progress_percentage' => 100,
    ]);
    $certificate = Certificate::create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'certificate_number' => "$label certificate",
        'issued_date' => now(),
        'status' => 'issued',
    ]);
    $review = CourseReview::create([
        'course_id' => $course->id, 'student_id' => $student->id,
        'rating' => 5, 'comment' => "$label review",
    ]);
    $learningPath = LearningPath::create([
        'name' => "$label learning path", 'description' => 'One course', 'student_id' => $student->id,
        'difficulty_level' => 'beginner', 'is_generated' => false,
    ]);
    $learningPath->courses()->attach($course->id, ['course_order' => 1]);
    $request = StudentRemovalRequest::create([
        'instructor_id' => $course->teacher_id,
        'student_id' => $student->id,
        'course_id' => $course->id,
        'reason' => "$label removal reason",
        'status' => 'pending',
    ]);
    $recommendation = AIRecommendation::create([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'recommendation_score' => 80,
        'reason' => "$label recommendation",
    ]);

    Storage::disk('local')->put($submission->file_path, "$label submission bytes");
    Storage::disk('local')->put($lesson->file_path, "$label lesson bytes");

    return compact('student', 'lesson', 'quiz', 'question', 'assignment', 'submission', 'enrollment',
        'certificate', 'review', 'learningPath', 'request', 'recommendation');
}

function departmentScopeSetup(): array
{
    $localDept = Department::create(['name' => 'Computing']);
    $foreignDept = Department::create(['name' => 'Health']);
    $admin = pathwiseUser('admin', ['department_id' => $localDept->id]);
    $local = departmentScopeCourse($localDept, 'Scoped');
    $foreign = departmentScopeCourse($foreignDept, 'Other');

    return [$admin, $local, $foreign];
}

test('department admins see their own courses and dashboard queue only', function () {
    [$admin, $local, $foreign] = departmentScopeSetup();
    $this->actingAs($admin);
    foreach (['courses.index', 'admin.dashboard'] as $page) {
        $this->get(route($page))->assertOk()->assertSee($local->title)->assertDontSee($foreign->title);
    }
});

test('department admins cannot edit approve reject or delete a foreign course', function () {
    [$admin, $local, $foreign] = departmentScopeSetup();
    $before = $foreign->fresh()->getAttributes();
    $this->actingAs($admin);
    $this->get(route('courses.edit', $foreign))->assertForbidden();
    $this->put(route('courses.update', $foreign), ['title' => 'Wrong department'])->assertForbidden();
    $this->post(route('courses.approve', $foreign))->assertForbidden();
    $this->post(route('courses.reject', $foreign), ['remarks' => 'Wrong department'])->assertForbidden();
    $this->delete(route('courses.destroy', $foreign))->assertForbidden();
    expect($foreign->fresh()->getAttributes())->toBe($before);

    $this->get(route('courses.edit', $local))->assertOk();
    $this->post(route('courses.approve', $local))->assertSessionHas('success');
    expect($local->fresh()->status)->toBe('published');
});

test('department scope also filters related lists and private files', function () {
    [$admin, $local, $foreign] = departmentScopeSetup();
    $own = departmentScopeRecords($local, 'ScopeLocal');
    $other = departmentScopeRecords($foreign, 'ScopeForeign');
    $this->actingAs($admin);

    $pages = [
        'lessons.index' => ['ScopeLocal lesson', 'ScopeForeign lesson'],
        'quizzes.index' => ['ScopeLocal quiz', 'ScopeForeign quiz'],
        'quiz-questions.index' => ['ScopeLocal question', 'ScopeForeign question'],
        'quiz-results.index' => ['ScopeLocal quiz', 'ScopeForeign quiz'],
        'assignments.index' => ['ScopeLocal assignment', 'ScopeForeign assignment'],
        'submissions.index' => ['ScopeLocal assignment', 'ScopeForeign assignment'],
        'enrollments.index' => ['ScopeLocal learner', 'ScopeForeign learner'],
        'student-progress.index' => ['ScopeLocal learner', 'ScopeForeign learner'],
        'certificates.index' => ['ScopeLocal certificate', 'ScopeForeign certificate'],
        'ai-recommendations.index' => ['ScopeLocal learner', 'ScopeForeign learner'],
        'admin.course-reviews.index' => ['ScopeLocal review', 'ScopeForeign review'],
        'learning-paths.index' => ['ScopeLocal learning path', 'ScopeForeign learning path'],
        'admin.student-removal-requests.index' => ['ScopeLocal removal reason', 'ScopeForeign removal reason'],
    ];
    foreach ($pages as $page => [$allowed, $denied]) {
        $this->get(route($page))->assertOk()->assertSee($allowed)->assertDontSee($denied);
    }
    $this->get(route('submissions.show', $own['submission']))->assertOk();
    $this->get(route('submissions.show', $other['submission']))->assertForbidden();
    $this->get(route('files.submission', $own['submission']))->assertOk();
    $this->get(route('files.submission', $other['submission']))->assertForbidden();
    $this->get(route('files.lesson', $own['lesson']))->assertOk();
    $this->get(route('files.lesson', $other['lesson']))->assertForbidden();
});

test('foreign related records reject direct write requests and keep their data', function () {
    [$admin, , $foreign] = departmentScopeSetup();
    $other = departmentScopeRecords($foreign, 'ScopeForeign');
    $this->actingAs($admin);
    $routes = [
        'lessons' => 'lesson', 'quizzes' => 'quiz', 'quiz-questions' => 'question',
        'assignments' => 'assignment', 'enrollments' => 'enrollment',
        'certificates' => 'certificate', 'learning-paths' => 'learningPath',
    ];
    foreach ($routes as $resource => $record) {
        $model = $other[$record];
        $before = $model->fresh()->getAttributes();
        $this->get(route("$resource.edit", $model))->assertForbidden();
        $this->put(route("$resource.update", $model), [])->assertForbidden();
        $this->delete(route("$resource.destroy", $model))->assertForbidden();
        expect($model->fresh()->getAttributes())->toBe($before);
    }

    $this->post(route('admin.course-reviews.hide', $other['review']))->assertForbidden();
    $this->post(route('admin.course-reviews.unhide', $other['review']))->assertForbidden();
    $this->delete(route('ai-recommendations.destroy', $other['recommendation']))->assertForbidden();
    $this->post(route('admin.student-removal-requests.review', $other['request']), [
        'decision' => 'approved', 'review_note' => 'Scope test.',
    ])->assertForbidden();

    expect($other['request']->fresh()->status)->toBe('pending')
        ->and($other['enrollment']->fresh()->status)->toBe('completed')
        ->and($other['recommendation']->fresh())->not->toBeNull();
});

test('a local record cannot be moved or linked to a foreign course', function () {
    [$admin, $local, $foreign] = departmentScopeSetup();
    $own = departmentScopeRecords($local, 'ScopeLocal');
    $other = departmentScopeRecords($foreign, 'ScopeForeign');
    $this->actingAs($admin);

    $lessonPayload = ['title' => 'Move', 'lesson_order' => 1, 'lesson_type' => 'text'];
    $this->put(route('lessons.update', $own['lesson']), $lessonPayload + ['course_id' => $foreign->id])->assertForbidden();
    $this->post(route('lessons.store'), $lessonPayload + ['course_id' => $foreign->id])->assertForbidden();

    $quizPayload = ['title' => 'Move', 'passing_score' => 75, 'is_published' => 0];
    $this->put(route('quizzes.update', $own['quiz']), $quizPayload + ['course_id' => $foreign->id])->assertForbidden();
    $this->put(route('quizzes.update', $own['quiz']), $quizPayload + [
        'course_id' => $local->id, 'lesson_id' => $other['lesson']->id,
    ])->assertStatus(422);

    $assignmentPayload = ['title' => 'Move', 'submission_type' => 'essay', 'max_score' => 100, 'is_published' => 0];
    $this->put(route('assignments.update', $own['assignment']), $assignmentPayload + ['course_id' => $foreign->id])->assertForbidden();
    $this->put(route('assignments.update', $own['assignment']), $assignmentPayload + [
        'course_id' => $local->id, 'lesson_id' => $other['lesson']->id,
    ])->assertStatus(422);

    $this->put(route('learning-paths.update', $own['learningPath']), [
        'name' => 'Move', 'difficulty_level' => 'beginner', 'course_ids' => [$local->id, $foreign->id],
    ])->assertForbidden();

    expect($own['lesson']->fresh()->course_id)->toBe($local->id)
        ->and($own['quiz']->fresh()->lesson_id)->toBe($own['lesson']->id)
        ->and($own['assignment']->fresh()->lesson_id)->toBe($own['lesson']->id)
        ->and($own['learningPath']->courses()->pluck('courses.id')->all())->toBe([$local->id]);
});

test('an unassigned administrator has no course scope and a super admin has a campus-wide scope', function () {
    [, $local, $foreign] = departmentScopeSetup();
    $unassignedCourse = departmentScopeCourse(null, 'Unassigned');
    $other = departmentScopeRecords($foreign, 'ScopeForeign');
    $this->actingAs(pathwiseUser('admin'))
        ->get(route('courses.index'))->assertOk()->assertDontSee($local->title)->assertDontSee($unassignedCourse->title);
    $this->get(route('courses.create'))->assertForbidden();
    $this->post(route('courses.approve', $unassignedCourse))->assertForbidden();

    $this->actingAs(pathwiseUser('super_admin'))
        ->get(route('courses.index'))->assertOk()->assertSee($local->title)->assertSee($foreign->title)->assertSee($unassignedCourse->title);
    $this->get(route('super_admin.dashboard'))->assertOk()
        ->assertSee(route('courses.index'), false)
        ->assertSee(route('admin.student-removal-requests.index'), false);
    $this->get(route('submissions.show', $other['submission']))->assertOk();
    $this->get(route('files.submission', $other['submission']))->assertOk();
    $this->post(route('courses.approve', $unassignedCourse))->assertSessionHas('success');
    expect($unassignedCourse->fresh()->status)->toBe('published');
});

test('inconsistent lesson links cannot be used to read or remove foreign records', function () {
    [$admin, $local, $foreign] = departmentScopeSetup();
    $own = departmentScopeRecords($local, 'ScopeLocal');
    $other = departmentScopeRecords($foreign, 'ScopeForeign');

    // Simulate old data with foreign references already present.
    $own['assignment']->update(['lesson_id' => $other['lesson']->id]);
    $other['quiz']->update(['lesson_id' => $own['lesson']->id]);
    $this->actingAs($admin);

    $this->get(route('assignments.show', $own['assignment']))->assertForbidden();
    $this->get(route('submissions.show', $own['submission']))->assertForbidden();
    $this->get(route('files.submission', $own['submission']))->assertForbidden();
    $this->delete(route('lessons.destroy', $own['lesson']))->assertForbidden();
    $this->delete(route('courses.destroy', $local))->assertForbidden();

    Storage::disk('local')->assertExists($own['lesson']->file_path);
    expect($other['quiz']->fresh()->lesson_id)->toBe($own['lesson']->id)
        ->and($own['submission']->fresh())->not->toBeNull();
});
