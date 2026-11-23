<?php

use App\Support\AiAvailability;
use App\Models\{Assignment, Enrollment, Lesson, Quiz, Submission};
use App\Services\{GeminiQuizService, GeminiEvaluationService};
use Illuminate\Support\Facades\{Http, Log, Notification, DB};

function friendlyAiFixture(string $operation): array
{
    Notification::fake();
    config(['services.gemini.api_key' => 'synthetic-secret-key', 'services.gemini.model' => 'gemini-2.5-flash']);
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => $operation === 'quiz' ? 'draft' : 'published']);
    $reading = $course->lessons()->create(['title' => 'Source', 'content' => 'Private fake source material', 'lesson_type' => 'text', 'is_published' => true, 'lesson_order' => 1]);
    $lesson = $course->lessons()->create(['title' => 'Quiz', 'lesson_type' => 'quiz', 'is_published' => true, 'lesson_order' => 2]);
    $quiz = Quiz::create(['course_id' => $course->id, 'lesson_id' => $lesson->id, 'title' => 'Unchanged quiz', 'passing_score' => 75, 'is_published' => false]);
    $quiz->questions()->create(['question' => 'Original question', 'option_a' => 'One', 'option_b' => 'Two', 'correct_answer' => 'A', 'points' => 1]);
    $student = pathwiseUser('student');
    Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active', 'progress_percentage' => 40]);
    $assignment = Assignment::create(['course_id' => $course->id, 'title' => 'Private fake assignment', 'description' => 'Private fake prompt', 'submission_type' => 'essay', 'answer_format' => 'text', 'max_score' => 100, 'is_published' => true, 'ai_evaluation_enabled' => true]);
    $submission = Submission::create(['assignment_id' => $assignment->id, 'student_id' => $student->id, 'answer_text' => 'Private fake student response', 'status' => 'graded', 'score' => 87, 'feedback' => 'Preserve manual grade', 'submitted_at' => now()]);
    return compact('teacher','course','quiz','submission');
}
function friendlyAiRequest($test, array $f, string $operation)
{
    $test->actingAs($f['teacher']);
    if ($operation === 'evaluation') return $test->post(route('teacher.submissions.ai-evaluate', $f['submission']));
    $revision = $test->get(route('teacher.quiz.builder', $f['quiz']->lesson_id))->assertOk()->viewData('quizRevision');
    return $test->post(route('teacher.quiz.questions.generate', $f['quiz']), ['revision' => $revision, 'question_count' => 2, 'difficulty' => 'beginner']);
}
function friendlyAiRows(): array
{
    return array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->toJson(), ['quizzes','quiz_questions','enrollments','lesson_progress','quiz_results']);
}

test('expected Gemini service failures show exact message with sanitized logs and preserved work', function (string $operation, string $failure) {
    $f = friendlyAiFixture($operation);
    $status = match ($failure) { 'quota' => 429, 'server' => 503, 'credentials' => 403, default => null };
    if ($failure === 'missing configuration') { config(['services.gemini.api_key' => '']); Http::fake(); }
    elseif ($status !== null) Http::fake(['*' => Http::response(['error' => ['message' => 'synthetic-secret-key Private fake student response full prompt stack trace']], $status)]);
    elseif ($failure === 'malformed response') Http::fake(['*' => Http::response('Private fake invalid provider response', 200, ['Content-Type' => 'text/plain'])]);
    else Http::fake(['*' => Http::failedConnection('synthetic-secret-key full prompt network timeout')]);
    $before = friendlyAiRows();$submission = $f['submission']->getAttributes();
    Log::spy();
    $response = friendlyAiRequest($this, $f, $operation);
    $response->assertRedirect()->assertSessionHas('error', fn ($message) => str_starts_with($message, AiAvailability::MESSAGE));
    expect(friendlyAiRows())->toBe($before);
    $fresh = $f['submission']->fresh();
    foreach (['answer_text','score','feedback','status','file_path','student_id','assignment_id'] as $field) expect($fresh->$field)->toBe($submission[$field] ?? null);
    if ($operation === 'evaluation') expect($fresh->ai_evaluation_status)->toBe('failed')->and($fresh->ai_score)->toBeNull();
    else $response->assertSessionHasInput('question_count', 2);
    Log::shouldHaveReceived('warning')->once()->with(Mockery::type('string'), Mockery::on(function ($context) use ($status) {
        return ($context['http_status'] ?? null) === $status
            && !str_contains(json_encode($context), 'synthetic-secret-key') && !str_contains(json_encode($context), 'Private fake');
    }));
    Log::shouldNotHaveReceived('error');
    if ($failure === 'missing configuration') Http::assertNothingSent();
})->with(['quiz','evaluation'])->with(['quota','server','credentials','timeout','network','missing configuration']);

test('unrelated application bugs are not labelled as Gemini availability failures', function (string $operation) {
    $f = friendlyAiFixture($operation);
    $service = $operation === 'quiz' ? GeminiQuizService::class : GeminiEvaluationService::class;
    $method = $operation === 'quiz' ? 'generateQuestions' : 'evaluate';
    $this->mock($service)->shouldReceive($method)->once()->andThrow(new LogicException('Application bug mentioning quota 429'));
    $before = friendlyAiRows();
    $this->withoutExceptionHandling();
    try {
        friendlyAiRequest($this, $f, $operation);
        $this->fail('Application bug must propagate.');
    } catch (LogicException $exception) {
        expect($exception->getMessage())->toBe('Application bug mentioning quota 429');
    }
    expect(session('error'))->not->toBe(AiAvailability::MESSAGE)->and(friendlyAiRows())->toBe($before);
})->with(['quiz','evaluation']);

test('local Gemini quotas keep their limits and preserve input with the same friendly message', function (string $scope) {
    $f = friendlyAiFixture('quiz');
    Http::fake();
    $key = $scope === 'minute' ? 'pathwise:gemini:quiz:minute' : 'pathwise:gemini:quiz:hourly';
    foreach (range(1, $scope === 'minute' ? 4 : 20) as $n) Illuminate\Support\Facades\RateLimiter::hit($key, 60);
    $before = friendlyAiRows();
    $message = $scope === 'minute' ? AiAvailability::MESSAGE : AiAvailability::HOURLY_LIMIT_MESSAGE;
    friendlyAiRequest($this, $f, 'quiz')->assertSessionHas('error', $message)->assertSessionHasInput('question_count', 2);
    expect(friendlyAiRows())->toBe($before);
    Http::assertNothingSent();
})->with(['minute','hourly']);


test('availability logging cannot retain provider bodies prompts or arbitrary contexts', function () {
    Log::spy();
    AiAvailability::logUnavailable('quiz generation', new App\Exceptions\AiUnavailableException('synthetic-secret-key student prompt'), ['body' => 'Private student response', 'teacher_id' => 12]);
    Log::shouldHaveReceived('warning')->once()->with(Mockery::type('string'), Mockery::on(fn ($context) => !str_contains(json_encode($context),'synthetic-secret-key') && !str_contains(json_encode($context),'Private student') && !isset($context['body']) && $context['teacher_id'] === 12));
});

test('unusable evaluation content keeps Beas actionable message but never logs model output', function (string $shape) {
    $f = friendlyAiFixture('evaluation');
    Http::fake(['*' => $shape === 'model content' ? Http::response(['output_text' => 'Private fake student response not JSON']) : Http::response('Private fake invalid envelope', 200, ['Content-Type' => 'text/plain'])]);
    Log::spy();
    friendlyAiRequest($this, $f, 'evaluation')->assertSessionHas('error', fn ($message) => str_contains($message,'invalid evaluation format') && !str_contains($message,AiAvailability::MESSAGE));
    Log::shouldNotHaveReceived('error');
    expect($f['submission']->fresh()->score)->toBe(87);
})->with(['model content', 'response envelope']);
