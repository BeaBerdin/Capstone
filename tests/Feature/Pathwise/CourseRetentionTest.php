<?php

use App\Exceptions\DeletionBlockedException;
use App\Models\AIRecommendation;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\StudentRemovalRequest;
use App\Models\Submission;
use App\Models\Transaction;
use App\Services\RecordRetentionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    fakeFileDisks();
    Notification::fake();
});

function retentionCourse(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'rejected', 'thumbnail' => 'courses/retained.jpg']);
    Storage::disk('public')->put($course->thumbnail, 'course thumbnail');

    return [$teacher, $student, $course];
}

function retentionHistory(Course $course, int $studentId, string $kind): Model
{
    if (str_starts_with($kind, 'enrollment:')) {
        return Enrollment::create([
            'student_id' => $studentId, 'course_id' => $course->id,
            'status' => explode(':', $kind)[1], 'enrolled_at' => now(), 'progress_percentage' => 40,
        ]);
    }

    if (str_starts_with($kind, 'transaction:')) {
        Storage::disk('local')->put('payment-proofs/retained.png', 'payment proof');

        return Transaction::create([
            'student_id' => $studentId, 'course_id' => $course->id,
            'transaction_no' => 'RETENTION-TEST', 'amount' => 499, 'payment_method' => 'GCash',
            'status' => explode(':', $kind)[1], 'payment_proof' => 'payment-proofs/retained.png',
        ]);
    }

    if (str_starts_with($kind, 'certificate:')) {
        Storage::disk('local')->put('certificates/retained.pdf', 'certificate file');

        return Certificate::create([
            'student_id' => $studentId, 'course_id' => $course->id,
            'certificate_number' => 'PATH-RETENTION-TEST', 'issued_date' => now()->toDateString(),
            'status' => explode(':', $kind)[1], 'certificate_file' => 'certificates/retained.pdf',
        ]);
    }

    if ($kind === 'submission') {
        $assignment = Assignment::create([
            'course_id' => $course->id, 'title' => 'Historical assignment',
            'submission_type' => 'essay', 'max_score' => 100,
        ]);
        Storage::disk('local')->put('assignment-submissions/retained.txt', 'uploaded answer');

        return Submission::create([
            'assignment_id' => $assignment->id, 'student_id' => $studentId,
            'answer_text' => 'Keep my original answer', 'file_path' => 'assignment-submissions/retained.txt',
            'status' => 'graded', 'score' => 90, 'feedback' => 'Final feedback',
            'ai_score' => 92, 'ai_feedback' => 'AI suggestion', 'ai_evaluation_status' => 'completed',
        ]);
    }

    if ($kind === 'quiz result') {
        $quiz = Quiz::create(['course_id' => $course->id, 'title' => 'Historical quiz', 'passing_score' => 70]);

        return QuizResult::create([
            'student_id' => $studentId, 'quiz_id' => $quiz->id, 'score' => 4,
            'total_items' => 5, 'percentage' => 80, 'remarks' => 'passed', 'attempt_number' => 1,
        ]);
    }

    if ($kind === 'lesson progress') {
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Historical lesson', 'lesson_type' => 'text', 'lesson_order' => 1]);

        return LessonProgress::create([
            'student_id' => $studentId, 'lesson_id' => $lesson->id,
            'status' => 'completed', 'started_at' => now(), 'completed_at' => now(),
        ]);
    }

    return match ($kind) {
        'review' => CourseReview::create(['student_id' => $studentId, 'course_id' => $course->id, 'rating' => 5, 'comment' => 'Keep this review']),
        'invitation' => CourseInvitation::create(['course_id' => $course->id, 'created_by' => $course->teacher_id, 'student_id' => $studentId, 'code' => 'RETENTION-INVITE']),
        'removal request' => StudentRemovalRequest::create(['course_id' => $course->id, 'instructor_id' => $course->teacher_id, 'student_id' => $studentId, 'reason' => 'Keep removal history']),
        'recommendation' => AIRecommendation::create(['course_id' => $course->id, 'student_id' => $studentId, 'recommendation_score' => 80, 'reason' => 'Historical recommendation']),
        'learning path' => tap(LearningPath::create(['name' => 'Student path', 'student_id' => $studentId]), fn ($path) => $path->courses()->attach($course->id, ['course_order' => 1])),
    };
}

dataset('retained course history', [
    'active enrollment' => 'enrollment:active',
    'cancelled enrollment' => 'enrollment:cancelled',
    'completed enrollment' => 'enrollment:completed',
    'pending transaction' => 'transaction:pending',
    'approved transaction' => 'transaction:approved',
    'rejected transaction' => 'transaction:rejected',
    'issued certificate' => 'certificate:issued',
    'revoked certificate' => 'certificate:revoked',
    'submission without enrollment' => 'submission',
    'quiz result without enrollment' => 'quiz result',
    'progress without enrollment' => 'lesson progress',
    'review' => 'review',
    'invitation' => 'invitation',
    'removal request' => 'removal request',
    'recommendation' => 'recommendation',
    'learning path membership' => 'learning path',
]);

test('each history type independently blocks admin teacher and model course deletion', function (string $kind, string $entryPoint) {
    [$teacher, $student, $course] = retentionCourse();
    // Deliberately load empty relationships before creating history.
    $course->load(['enrollments', 'transactions', 'certificates', 'assignments']);
    $record = retentionHistory($course, $student->id, $kind);
    $before = $record->fresh()->getRawOriginal();
    $privateFiles = collect(Storage::disk('local')->allFiles())->mapWithKeys(
        fn ($path) => [$path => Storage::disk('local')->get($path)]
    )->all();

    if ($entryPoint === 'model') {
        expect(fn () => $course->delete())->toThrow(DeletionBlockedException::class);
    } else {
        $actor = $entryPoint === 'teacher' ? $teacher : pathwiseUser('admin');
        $destroy = $entryPoint === 'teacher' ? 'teacher.courses.destroy' : 'courses.destroy';
        $index = $entryPoint === 'teacher' ? 'teacher.my-courses' : 'courses.index';

        $this->actingAs($actor)->delete(route($destroy, $course))
            ->assertRedirect(route($index))->assertSessionHas('error');
    }

    expect($course->fresh())->not->toBeNull()
        ->and($record->fresh()->getRawOriginal())->toBe($before)
        ->and(Storage::disk('public')->get('courses/retained.jpg'))->toBe('course thumbnail');

    foreach ($privateFiles as $path => $contents) {
        expect(Storage::disk('local')->get($path))->toBe($contents);
    }

    if ($kind === 'learning path') {
        $this->assertDatabaseHas('learning_path_courses', ['learning_path_id' => $record->getKey(), 'course_id' => $course->id]);
    }
})->with('retained course history')->with(['admin', 'teacher', 'model']);

test('unused courses and their unused content remain deletable through existing role routes', function (string $actorRole) {
    [$teacher, , $course] = retentionCourse();
    $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Unused lesson', 'lesson_type' => 'text', 'lesson_order' => 1]);
    $assignment = Assignment::create(['course_id' => $course->id, 'title' => 'Unused assignment', 'submission_type' => 'essay', 'max_score' => 100]);
    $quiz = Quiz::create(['course_id' => $course->id, 'title' => 'Unused quiz', 'passing_score' => 70]);
    $actor = $actorRole === 'teacher' ? $teacher : pathwiseUser('admin');
    $destroy = $actorRole === 'teacher' ? 'teacher.courses.destroy' : 'courses.destroy';

    $this->actingAs($actor)->delete(route($destroy, $course))
        ->assertRedirect()->assertSessionHas('success')->assertSessionMissing('error');

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
    Storage::disk('public')->assertMissing('courses/retained.jpg');
})->with(['admin', 'teacher']);

test('direct Eloquent deletion still allows an unused course', function () {
    [, , $course] = retentionCourse();

    expect($course->delete())->toBeTrue();
    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

test('course deletion cannot cross teacher ownership or role boundaries', function (string $actorRole, string $routeName) {
    [, , $course] = retentionCourse();

    $this->actingAs(pathwiseUser($actorRole))->delete(route($routeName, $course))->assertForbidden();

    $this->assertDatabaseHas('courses', ['id' => $course->id]);
    Storage::disk('public')->assertExists('courses/retained.jpg');
})->with([
    ['teacher', 'teacher.courses.destroy'],
    ['student', 'teacher.courses.destroy'],
    ['student', 'courses.destroy'],
    ['teacher', 'courses.destroy'],
    ['super_admin', 'courses.destroy'],
]);

test('teacher course status restrictions are preserved', function (string $status) {
    [$teacher, , $course] = retentionCourse();
    $course->update(['status' => $status]);

    $this->actingAs($teacher)->delete(route('teacher.courses.destroy', $course))
        ->assertRedirect(route('teacher.my-courses'))->assertSessionHas('error', 'Only draft or returned courses can be deleted.');

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => $status]);
    Storage::disk('public')->assertExists('courses/retained.jpg');
})->with(['pending', 'published']);

test('an unrelated course history does not prevent deletion of unused content', function () {
    [$teacher, $student, $protected] = retentionCourse();
    retentionHistory($protected, $student->id, 'submission');
    $unused = pathwiseCourse($teacher);

    $this->actingAs(pathwiseUser('admin'))->delete(route('courses.destroy', $unused))->assertSessionHas('success');

    $this->assertDatabaseMissing('courses', ['id' => $unused->id]);
    $this->assertDatabaseHas('courses', ['id' => $protected->id]);
    $this->assertDatabaseCount('submissions', 1);
});

test('a rolled back course deletion preserves both course and thumbnail', function () {
    [, , $course] = retentionCourse();

    expect(fn () => DB::transaction(function () use ($course) {
        app(RecordRetentionService::class)->deleteCourse($course);
        throw new RuntimeException('Simulated rollback');
    }))->toThrow(RuntimeException::class, 'Simulated rollback');

    $this->assertDatabaseHas('courses', ['id' => $course->id]);
    Storage::disk('public')->assertExists('courses/retained.jpg');
});

test('deleting an unused course preserves a thumbnail referenced by another course', function () {
    [$teacher, , $course] = retentionCourse();
    pathwiseCourse($teacher, ['thumbnail' => $course->thumbnail]);

    $this->actingAs(pathwiseUser('admin'))->delete(route('courses.destroy', $course))->assertSessionHas('success');

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    Storage::disk('public')->assertExists('courses/retained.jpg');
});
