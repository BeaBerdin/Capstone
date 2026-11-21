<?php

use App\Http\Controllers\LessonController;
use App\Http\Controllers\SubmissionController;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Submission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function activityBlockerFixture(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);
    $lesson = $course->lessons()->create([
        'title' => 'Activity fixture', 'content' => 'Submit your answer.',
        'lesson_type' => 'activity', 'is_published' => true,
    ]);
    $assignment = $lesson->assignments()->create([
        'course_id' => $course->id, 'title' => $lesson->title, 'description' => $lesson->content,
        'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100,
        'is_activity' => true, 'is_published' => true,
    ]);
    Enrollment::create([
        'course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active', 'enrolled_at' => now(),
    ]);

    return [$teacher, $student, $course, $lesson, $assignment];
}

function activityConversionFields(): array
{
    return ['title' => 'Converted', 'lesson_type' => 'video', 'video_url' => 'https://example.test/video', 'lesson_order' => 1];
}

test('a first submission accepted before a stale teacher write protects all student records', function (string $operation) {
    fakeFileDisks();
    [$teacher, $student, $course, $lesson, $assignment] = activityBlockerFixture();
    $staleLesson = $lesson->fresh();
    $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
        'submission_file' => UploadedFile::fake()->createWithContent('answer.txt', 'A sample answer.'),
        'answer_text' => 'A sample answer.',
    ])->assertRedirect(route('student.assignments.show', $assignment))->assertSessionHasNoErrors();

    $submission = $assignment->submissions()->sole();
    $submission->update(['status' => 'graded', 'score' => 95, 'feedback' => 'Reviewed']);
    $before = $submission->fresh()->getAttributes();
    $progress = LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $student->id)->sole();
    $progressBefore = $progress->getAttributes();
    $course->update(['status' => 'draft']);
    $this->actingAs($teacher);

    if ($operation === 'delete') {
        expect(fn () => app(LessonController::class)->teacherDeleteLesson($staleLesson))
            ->toThrow(HttpException::class, 'This Activity has student submissions and cannot be deleted.');
    } else {
        expect(fn () => app(LessonController::class)->teacherUpdateLesson(
            Request::create('/', 'PUT', activityConversionFields()), $staleLesson
        ))->toThrow(ValidationException::class, 'cannot be changed to another lesson type');
    }

    expect($lesson->fresh()->lesson_type)->toBe('activity')
        ->and($assignment->fresh()->is_activity)->toBeTrue()
        ->and($submission->fresh()->getAttributes())->toBe($before)
        ->and($progress->fresh()->getAttributes())->toBe($progressBefore);
    expect($submission->file_path)->not->toBeNull();
    Storage::disk('local')->assertExists($submission->file_path);
})->with(['delete', 'convert']);

test('a teacher write that wins rejects the student with a stale Activity assignment', function (string $operation) {
    fakeFileDisks();
    [$teacher, $student, $course, $lesson, $assignment] = activityBlockerFixture();
    $staleAssignment = $assignment->fresh()->load('lesson');
    $course->update(['status' => 'draft']);

    $this->actingAs($teacher);
    if ($operation === 'delete') {
        $this->delete(route('teacher.lessons.delete', $lesson))->assertRedirect(route('teacher.lessons', $course));
        $this->assertModelMissing($lesson);
    } else {
        $this->put(route('teacher.lessons.update', $lesson), activityConversionFields())
            ->assertRedirect(route('teacher.lessons', $course))->assertSessionHasNoErrors();
        expect($lesson->fresh()->lesson_type)->toBe('video');
    }

    $this->assertModelMissing($assignment);
    $this->actingAs($student);
    expect(fn () => app(SubmissionController::class)->studentStore(
        Request::create('/', 'POST', ['answer_text' => 'Stale answer']), $staleAssignment
    ))->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(Submission::count())->toBe(0)->and(LessonProgress::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with(['delete', 'convert']);

test('the Activity migration preserves legacy rows and refuses rollback before changing data', function () {
    $original = DB::getDefaultConnection();
    config(['database.connections.activity_upgrade' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('activity_upgrade');
    \Illuminate\Support\Facades\Schema::clearResolvedInstance('db.schema');

    try {
        $db = DB::connection();
        $schema = $db->getSchemaBuilder();
        $schema->create('lessons', function (Blueprint $table) {
            $table->id();
            $table->enum('lesson_type', ['video', 'document', 'text', 'quiz'])->default('video');
            $table->text('content')->nullable();
        });
        $schema->create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained();
            $table->string('title');
        });
        $schema->create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained();
            $table->string('file_path');
            $table->integer('score');
        });
        foreach (['video', 'document', 'text', 'quiz'] as $type) {
            $db->table('lessons')->insert(['lesson_type' => $type, 'content' => 'Legacy '.$type]);
        }
        $db->table('assignments')->insert(['id' => 1, 'lesson_id' => 3, 'title' => 'Legacy assignment']);
        $db->table('submissions')->insert(['assignment_id' => 1, 'file_path' => 'private/legacy.txt', 'score' => 93]);
        $legacyLessons = $db->table('lessons')->orderBy('id')->get()->toJson();
        $legacySubmissions = $db->table('submissions')->orderBy('id')->get()->toJson();

        $migration = require database_path('migrations/2026_11_21_140000_add_activity_lesson_type.php');
        $migration->up();
        expect($db->table('lessons')->orderBy('id')->get()->toJson())->toBe($legacyLessons)
            ->and($db->table('submissions')->orderBy('id')->get()->toJson())->toBe($legacySubmissions)
            ->and((array) $db->table('assignments')->find(1))->toBe([
                'id' => 1, 'lesson_id' => 3, 'title' => 'Legacy assignment', 'is_activity' => 0,
            ]);
        $activity = $db->table('lessons')->insertGetId(['lesson_type' => 'activity']);
        $assignment = $db->table('assignments')->insertGetId(['lesson_id' => $activity, 'title' => 'Activity', 'is_activity' => true]);
        $db->table('submissions')->insert(['assignment_id' => $assignment, 'file_path' => 'private/activity.txt', 'score' => 95]);
        $allSubmissions = $db->table('submissions')->orderBy('id')->get()->toJson();

        $db->enableQueryLog();
        expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'cannot be rolled back safely');
        expect($db->getQueryLog())->toBe([])
            ->and($db->table('lessons')->find($activity)->lesson_type)->toBe('activity')
            ->and((int) $db->table('assignments')->where('lesson_id', $activity)->value('is_activity'))->toBe(1)
            ->and($db->table('submissions')->orderBy('id')->get()->toJson())->toBe($allSubmissions);
    } finally {
        DB::setDefaultConnection($original);
        \Illuminate\Support\Facades\Schema::clearResolvedInstance('db.schema');
        DB::purge('activity_upgrade');
    }
});


test('a stale admin request cannot change or delete a lesson that became an Activity', function (string $operation) {
    fakeFileDisks();
    [, , $course, $lesson, $assignment] = activityBlockerFixture();
    $staleLesson = clone $lesson;
    $staleLesson->lesson_type = 'text';
    $admin = pathwiseUser('super_admin');
    $request = Request::create('/', 'PUT', activityConversionFields() + ['course_id' => $course->id]);
    $request->setUserResolver(fn () => $admin);
    $controller = app(LessonController::class);

    expect(fn () => $operation === 'delete'
        ? $controller->destroy($request, $staleLesson)
        : $controller->update($request, $staleLesson))
        ->toThrow(HttpException::class, 'Manage this Activity from its teacher lesson editor.');
    expect($lesson->fresh()->lesson_type)->toBe('activity')
        ->and($assignment->fresh()->is_activity)->toBeTrue();
})->with(['delete', 'update']);


test('course deletion rejects accepted Activity work and preserves every record and file', function (string $role) {
    fakeFileDisks();
    [$teacher, $student, $course, $lesson, $assignment] = activityBlockerFixture();
    $course->update(['status' => 'draft', 'thumbnail' => 'covers/activity.jpg']);
    Storage::disk('public')->put($course->thumbnail, 'cover');
    $path = 'assignment-submissions/'.$assignment->id.'/answer.txt';
    Storage::disk('local')->put($path, 'accepted answer');
    $submission = $assignment->submissions()->create([
        'student_id' => $student->id, 'file_path' => $path, 'status' => 'graded', 'score' => 95, 'feedback' => 'Reviewed',
    ]);
    $progress = LessonProgress::create([
        'student_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'completed', 'completed_at' => now(),
    ]);

    // Admin history guards alone cannot protect rows whose enrollment was removed earlier.
    Enrollment::where('course_id', $course->id)->delete();
    $department = \App\Models\Department::create(['name' => 'Activity department']);
    $teacher->update(['department_id' => $department->id]);
    $user = $role === 'teacher' ? $teacher : pathwiseUser($role, ['department_id' => $department->id]);
    $models = [$course, $lesson, $assignment, $submission, $progress];
    $snapshots = array_map(fn ($model) => $model->fresh()->getAttributes(), $models);
    $route = $role === 'teacher' ? 'teacher.courses.destroy' : 'courses.destroy';

    $this->actingAs($user)->delete(route($route, $course))
        ->assertRedirect(route($role === 'teacher' ? 'teacher.my-courses' : 'courses.index'))
        ->assertSessionHas('error', 'This course cannot be deleted because it has Activity submissions or student progress.');
    expect(array_map(fn ($model) => $model->fresh()->getAttributes(), $models))->toBe($snapshots);
    Storage::disk('public')->assertExists($course->thumbnail);
    Storage::disk('local')->assertExists($path);
})->with(['teacher', 'admin', 'super_admin']);

test('course deletion preserves Activity progress even with no submission', function () {
    [$teacher, $student, $course, $lesson] = activityBlockerFixture();
    $course->update(['status' => 'draft']);
    $progress = LessonProgress::create(['student_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'completed']);

    $this->actingAs($teacher)->delete(route('teacher.courses.destroy', $course))->assertSessionHas('error');
    $this->assertModelExists($course);
    $this->assertModelExists($progress);
});

test('course deletion keeps role checks and rejects a stale student after deletion wins', function () {
    fakeFileDisks();
    [$teacher, $student, $course, $lesson, $assignment] = activityBlockerFixture();
    $staleAssignment = $assignment->fresh()->load('lesson');
    $course->update(['status' => 'draft', 'thumbnail' => 'covers/delete.jpg']);
    Storage::disk('public')->put($course->thumbnail, 'cover');

    $this->actingAs(pathwiseUser('teacher'))->delete(route('teacher.courses.destroy', $course))->assertForbidden();
    $this->actingAs(pathwiseUser('admin'))->delete(route('courses.destroy', $course))->assertForbidden();
    $this->actingAs($teacher)->delete(route('teacher.courses.destroy', $course))
        ->assertRedirect(route('teacher.my-courses'))->assertSessionHas('success');
    $this->assertModelMissing($course);
    $this->assertModelMissing($lesson);
    $this->assertModelMissing($assignment);
    Storage::disk('public')->assertMissing($course->thumbnail);

    $this->actingAs($student);
    expect(fn () => app(SubmissionController::class)->studentStore(
        Request::create('/', 'POST', ['answer_text' => 'Stale answer']), $staleAssignment
    ))->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(Submission::count())->toBe(0)->and(LessonProgress::count())->toBe(0);
});


test('Activity Gemini is opt-in and its suggestion never saves the final grade', function () {
    [$teacher, $student, $course, $lesson, $assignment] = activityBlockerFixture();
    config(['services.gemini.api_key' => 'disposable-test-key']);
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([
        'output_text' => json_encode(['score' => 85, 'feedback' => 'Suggested feedback', 'strengths' => [], 'improvements' => []]),
    ])]);

    $this->actingAs($student)->post(route('student.lesson.complete', $lesson))->assertForbidden();
    $this->post(route('student.assignments.submit', $assignment), ['answer_text' => 'Work to review'])
        ->assertSessionHasNoErrors()->assertRedirect(route('student.assignments.show', $assignment));
    $submission = $assignment->submissions()->sole();

    $url = route('teacher.submissions.ai-evaluate', $submission);
    $this->actingAs($teacher)->post($url)->assertSessionHas("error", "AI evaluation is not enabled for this assignment.");
    \Illuminate\Support\Facades\Http::assertNothingSent();
    $assignment->update(['ai_evaluation_enabled' => true]);
    $this->actingAs(pathwiseUser('teacher'))->post($url)->assertForbidden();
    $this->actingAs($teacher)->post($url)->assertSessionHas('success');
    expect($submission->fresh()->ai_score)->toBe(85)
        ->and($submission->fresh()->score)->toBeNull()
        ->and($submission->fresh()->feedback)->toBeNull()
        ->and($submission->fresh()->status)->toBe('submitted');

    $this->put(route('teacher.submissions.grade', $submission), [
        'status' => 'graded', 'score' => 92, 'feedback' => 'Teacher feedback',
    ])->assertSessionHasNoErrors();
    expect($submission->fresh()->score)->toBe(92)
        ->and($submission->fresh()->feedback)->toBe('Teacher feedback')
        ->and($submission->fresh()->ai_score)->toBe(85);

    $course->update(['status' => 'draft']);
    $this->put(route('teacher.lessons.update', $lesson), [
        'title' => $lesson->title, 'lesson_type' => 'activity', 'content' => 'Revised instructions.',
        'lesson_order' => 1, 'activity_max_score' => 50, 'activity_answer_format' => 'either',
    ])->assertSessionHasErrors('activity_max_score');
    expect($submission->fresh()->score)->toBe(92)
        ->and($assignment->fresh()->max_score)->toBe(100);
});
