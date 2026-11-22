<?php

use App\Models\{Department, Enrollment, LessonProgress, Quiz, QuizResult, Submission};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Notification, Storage};

function underReviewFixture(string $status = 'published', string $enrollmentStatus = 'active'): array
{
    Notification::fake();
    Storage::fake('local');
    $department = Department::create(['name' => 'Review department']);
    $teacher = pathwiseApprovedTeacher(['department_id' => $department->id]);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => $status, 'title' => 'Review lifecycle']);
    $reading = $course->lessons()->create(['title' => 'Private reading', 'lesson_type' => 'text', 'content' => 'Protected lesson body', 'lesson_order' => 1, 'is_published' => true]);
    $reading->sections()->create(['type' => 'text', 'body_format' => 'plain', 'body' => 'Protected lesson body', 'position' => 0]);
    $sections = [];
    foreach (['image' => 'lesson-sections/test.png', 'file' => 'lesson-sections/test.pdf'] as $type => $path) {
        Storage::disk('local')->put($path, 'synthetic media');
        $sections[] = $reading->sections()->create(['type' => $type, 'file_path' => $path, 'original_name' => basename($path), 'position' => count($sections) + 1]);
    }
    Storage::disk('local')->put('lesson-files/test.pdf', 'synthetic document');
    $document = $course->lessons()->create(['title' => 'Document', 'lesson_type' => 'document', 'file_path' => 'lesson-files/test.pdf', 'lesson_order' => 2, 'is_published' => true]);
    $ql = $course->lessons()->create(['title' => 'Quiz', 'lesson_type' => 'quiz', 'lesson_order' => 3, 'is_published' => true]);
    $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $ql->id, 'title' => 'Quiz', 'passing_score' => 75, 'is_published' => true]);
    $quiz->questions()->create(['question' => 'Synthetic?', 'option_a' => 'Yes', 'option_b' => 'No', 'correct_answer' => 'A', 'points' => 1]);
    $al = $course->lessons()->create(['title' => 'Activity', 'lesson_type' => 'activity', 'lesson_order' => 4, 'is_published' => true]);
    $activity = $al->assignments()->create(['course_id' => $course->id, 'title' => 'Activity', 'description' => 'Protected instructions', 'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100, 'is_activity' => true, 'is_published' => true]);
    $enrollment = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => $enrollmentStatus, 'enrolled_at' => now(), 'progress_percentage' => $enrollmentStatus === 'completed' ? 100 : 40, 'completed_at' => $enrollmentStatus === 'completed' ? now() : null]);
    LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $reading->id, 'status' => 'completed', 'completed_at' => now()]);
    QuizResult::create(['student_id' => $student->id, 'quiz_id' => $quiz->id, 'score' => 1, 'total_items' => 1, 'percentage' => 100, 'remarks' => 'passed', 'attempt_number' => 1, 'completed_at' => now()]);
    Storage::disk('local')->put('assignment-submissions/own.txt', 'My submitted work');
    $submission = Submission::create(['student_id' => $student->id, 'assignment_id' => $activity->id, 'answer_text' => 'Original answer', 'file_path' => 'assignment-submissions/own.txt', 'score' => 90, 'feedback' => 'Reviewed', 'status' => 'graded', 'submitted_at' => now()]);
    return compact('teacher', 'admin', 'student', 'course', 'reading', 'document', 'quiz', 'activity', 'enrollment', 'submission', 'sections');
}

function underReviewUpdate($course): array
{
    return ['category_id' => $course->category_id, 'title' => $course->title, 'description' => $course->description, 'difficulty_level' => $course->difficulty_level, 'price' => $course->price, 'status' => 'pending', 'certificate_available' => true];
}

function underReviewRecords(): array
{
    $rows = [];
    foreach (['enrollments', 'lesson_progress', 'quiz_results', 'submissions', 'assignments', 'lessons', 'lesson_sections'] as $table) {
        $rows[$table] = DB::table($table)->orderBy('id')->get()->toJson();
    }
    foreach (Storage::disk('local')->allFiles() as $file) $rows['files'][$file] = Storage::disk('local')->get($file);
    return $rows;
}

test('review lifecycle keeps active and completed enrollments and learning records', function (string $enrollmentStatus) {
    $f = underReviewFixture('draft', $enrollmentStatus);
    extract($f);
    $this->actingAs($teacher)->post(route('teacher.courses.submit', $course))->assertSessionHasNoErrors();
    expect($course->fresh()->status)->toBe('pending');
    $this->actingAs($admin)->post(route('courses.approve', $course))->assertRedirect();
    expect($course->fresh()->status)->toBe('published');
    $this->actingAs($student)->get(route('student.learn.course', $course))->assertOk();
    $before = underReviewRecords();
    $this->actingAs($admin)->put(route('courses.update', $course), underReviewUpdate($course))->assertSessionHasNoErrors();
    expect($course->fresh()->status)->toBe('pending');
    foreach (['student.my-courses', 'student.dashboard'] as $page) {
        $this->actingAs($student)->get(route($page))->assertOk()->assertSee('Review lifecycle')->assertSee('Course Under Review')
            ->assertDontSee('href="'.route('student.learn.course', $course).'"', false)->assertSee($enrollmentStatus === 'completed' ? '100%' : '40%');
    }
    if ($enrollmentStatus === 'completed') $this->get(route('student.my-courses'))->assertSee('Completed');
    foreach ([route('student.learn.course', $course), route('student.lesson.view', $reading)] as $url) {
        $this->get($url, ['Accept' => 'text/html'])->assertRedirect(route('student.my-courses'))->assertSessionHas('course_under_review', true);
        $this->followingRedirects()->get($url, ['Accept' => 'text/html'])->assertOk()->assertSee('Course Under Review')->assertDontSee('Protected lesson body');
    }
    expect(underReviewRecords())->toBe($before);
    $this->actingAs($admin)->post(route('courses.approve', $course))->assertRedirect();
    expect($course->fresh()->status)->toBe('published')->and(underReviewRecords())->toBe($before);
    $this->actingAs($student)->get(route('student.lesson.view', $reading))->assertOk()->assertSee('Protected lesson body');
    $this->get(route('student.my-courses'))->assertDontSee('Course Under Review')->assertSee('href="'.route('student.learn.course', $course).'"', false);
})->with(['active', 'completed']);

test('pending blocks stale forms JSON quizzes Activities and private lesson media without changing work', function () {
    extract(underReviewFixture());
    $this->actingAs($student)->get(route('student.quiz.take', $quiz))->assertOk();
    $this->get(route('student.assignments.show', $activity))->assertOk();
    $course->update(['status' => 'pending']);
    $before = underReviewRecords();
    $this->getJson(route('student.learn.course', $course))->assertNotFound();
    $this->getJson(route('student.lesson.view', $reading))->assertNotFound();
    $this->get(route('student.course.show', $course))->assertNotFound();
    $this->get(route('student.quiz.take', $quiz))->assertForbidden();
    $this->post(route('student.quiz.submit', $quiz), ['question_'.$quiz->questions->first()->id => 'A'])->assertForbidden();
    $this->get(route('student.assignments.show', $activity))->assertNotFound();
    $this->post(route('student.assignments.submit', $activity), ['answer_text' => 'Stale edit', 'submission_file' => UploadedFile::fake()->createWithContent('new.txt', 'Rejected upload')])->assertNotFound();
    $this->post(route('student.lesson.complete', $reading))->assertNotFound();
    $this->post(route('student.lesson.complete', $activity->lesson_id))->assertNotFound();
    $this->get(route('files.lesson', $document))->assertForbidden();
    foreach ($sections as $section) $this->get(route('files.lesson-section', $section))->assertForbidden();
    $this->get(route('files.submission', $submission))->assertOk();
    $this->get(route('student.assignments.index'))->assertOk()->assertDontSee('Protected instructions');
    expect(underReviewRecords())->toBe($before);
});

test('pending never discloses review state to outsiders or cancelled students and reapproval cannot restore cancelled access', function (string $kind) {
    extract(underReviewFixture('pending', $kind === 'cancelled' ? 'cancelled' : 'active'));
    $viewer = $kind === 'outsider' ? pathwiseUser('student') : $student;
    $this->actingAs($viewer);
    foreach ([route('student.learn.course', $course), route('student.lesson.view', $reading)] as $url) {
        $this->get($url, ['Accept' => 'text/html'])->assertNotFound()->assertSessionMissing('course_under_review');
    }
    $this->get(route('files.lesson-section', $sections[0]))->assertForbidden();
    if ($kind === 'outsider') $this->get(route('files.submission', $submission))->assertForbidden();
    $course->update(['status' => 'published']);
    $this->get(route('student.learn.course', $course))->assertForbidden();
})->with(['outsider', 'cancelled']);

test('department review permissions stay scoped and Super Admin retains campus oversight', function () {
    extract(underReviewFixture());
    $other = Department::create(['name' => 'Other department']);
    foreach ([pathwiseUser('admin', ['department_id' => $other->id]), pathwiseUser('admin'), $teacher, $student] as $denied) {
        $this->actingAs($denied)->put(route('courses.update', $course), underReviewUpdate($course))->assertForbidden();
        expect($course->fresh()->status)->toBe('published');
    }
    $this->actingAs($admin)->put(route('courses.update', $course), underReviewUpdate($course))->assertSessionHasNoErrors();
    expect($course->fresh()->status)->toBe('pending');
    $this->actingAs(pathwiseUser('super_admin'))->post(route('courses.approve', $course))->assertRedirect();
    expect($course->fresh()->status)->toBe('published');
});
