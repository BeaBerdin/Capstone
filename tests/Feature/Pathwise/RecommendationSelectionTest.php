<?php

use App\Models\AIRecommendation;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Http;

/*
| Functional Requirement i and Figure 19: the next course is chosen by
| rules (the latest quiz percentage, the course category and difficulty,
| and the courses already taken); Gemini only writes the explanation.
| Gemini is switched off here, so the fixed fallback reason is used.
*/

beforeEach(function () {
    config(['services.gemini.api_key' => '']);
    Http::fake();
});

function recCategory(string $name): CourseCategory
{
    return CourseCategory::firstOrCreate(['name' => $name], ['is_active' => true]);
}

/**
 * A published course with a lesson and a quiz (what the first rules require).
 */
function recCourse(string $title, CourseCategory $category, string $difficulty, bool $complete = true): Course
{
    $course = pathwiseCourse(pathwiseUser('teacher'), [
        'title' => $title,
        'category_id' => $category->id,
        'difficulty_level' => $difficulty,
        'status' => 'published',
    ]);

    if ($complete) {
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => $title.' Lesson',
            'content' => 'Content',
            'lesson_type' => 'quiz',
            'lesson_order' => 1,
            'is_preview' => false,
            'is_published' => true,
        ]);

        Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => $title.' Quiz',
            'passing_score' => 75,
            'is_published' => true,
        ]);
    }

    return $course;
}

function recommendAfter(User $student, Course $course, float $percentage): ?string
{
    app(RecommendationService::class)->generate($student->id, $course->quizzes()->first(), $percentage);

    return AIRecommendation::where('student_id', $student->id)->sole()->course->title;
}

test('a score of 85% or more recommends the next difficulty in the same category', function () {
    $web = recCategory('Web Development');
    $current = recCourse('HTML Basics', $web, 'beginner');
    recCourse('Advanced Laravel', $web, 'advanced');
    recCourse('CSS Layouts', $web, 'intermediate');
    recCourse('Data Intermediate', recCategory('Data Science'), 'intermediate');

    expect(recommendAfter(pathwiseUser('student'), $current, 90))->toBe('CSS Layouts');
});

test('a passing score below 85% recommends the same difficulty', function () {
    $web = recCategory('Web Development');
    $current = recCourse('CSS Layouts', $web, 'intermediate');
    recCourse('HTML Basics', $web, 'beginner');
    recCourse('JavaScript DOM', $web, 'intermediate');

    expect(recommendAfter(pathwiseUser('student'), $current, 80))->toBe('JavaScript DOM');
});

test('a failing score recommends a beginner course', function () {
    $web = recCategory('Web Development');
    $current = recCourse('CSS Layouts', $web, 'intermediate');
    recCourse('JavaScript DOM', $web, 'intermediate');
    recCourse('HTML Basics', $web, 'beginner');

    expect(recommendAfter(pathwiseUser('student'), $current, 40))->toBe('HTML Basics');
});

test('without the target difficulty the closest difficulty in the category is used', function () {
    $web = recCategory('Web Development');
    $current = recCourse('HTML Basics', $web, 'beginner');
    recCourse('Advanced Laravel', $web, 'advanced');

    // Target is intermediate (90%); only beginner (the current course) and advanced exist.
    expect(recommendAfter(pathwiseUser('student'), $current, 90))->toBe('Advanced Laravel');
});

test('without a course in the category another category is used', function () {
    $current = recCourse('HTML Basics', recCategory('Web Development'), 'beginner');
    recCourse('Statistics', recCategory('Data Science'), 'advanced');

    expect(recommendAfter(pathwiseUser('student'), $current, 90))->toBe('Statistics');
});

test('the current, enrolled and completed courses are never recommended', function () {
    $web = recCategory('Web Development');
    $student = pathwiseUser('student');

    $current = recCourse('HTML Basics', $web, 'beginner');
    $enrolled = recCourse('HTML Forms', $web, 'beginner');
    $completed = recCourse('HTML Tables', $web, 'beginner');
    recCourse('HTML Media', $web, 'beginner');

    foreach ([[$enrolled, 'active'], [$completed, 'completed']] as [$course, $status]) {
        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => $status,
            'enrolled_at' => now(),
            'progress_percentage' => 0,
        ]);
    }

    expect(recommendAfter($student, $current, 80))->toBe('HTML Media');
});

test('courses without lessons and quizzes are only a last resort', function () {
    $web = recCategory('Web Development');
    $current = recCourse('HTML Basics', $web, 'beginner');
    recCourse('Empty Course', $web, 'beginner', complete: false);
    recCourse('Complete Elsewhere', recCategory('Data Science'), 'advanced');

    expect(recommendAfter(pathwiseUser('student'), $current, 80))->toBe('Complete Elsewhere');
});

test('the stored score is the quiz percentage and the fallback reason is used without Gemini', function () {
    $web = recCategory('Web Development');
    $student = pathwiseUser('student');
    $current = recCourse('HTML Basics', $web, 'beginner');
    recCourse('HTML Forms', $web, 'beginner');

    recommendAfter($student, $current, 78.5);

    $recommendation = AIRecommendation::where('student_id', $student->id)->sole();

    expect((float) $recommendation->recommendation_score)->toBe(78.5)
        ->and($recommendation->reason)->toContain('You performed well on the quiz')
        ->and((bool) $recommendation->is_viewed)->toBeFalse();

    Http::assertNothingSent();
});

test('a new quiz result replaces the previous recommendation', function () {
    $web = recCategory('Web Development');
    $student = pathwiseUser('student');
    $current = recCourse('HTML Basics', $web, 'beginner');
    recCourse('HTML Forms', $web, 'beginner');
    recCourse('CSS Layouts', $web, 'intermediate');

    expect(recommendAfter($student, $current, 80))->toBe('HTML Forms')
        ->and(recommendAfter($student, $current, 95))->toBe('CSS Layouts');
});

test('submitting a quiz creates the recommendation', function () {
    $web = recCategory('Web Development');
    $student = pathwiseUser('student');
    $current = recCourse('HTML Basics', $web, 'beginner');
    recCourse('HTML Forms', $web, 'beginner');

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $current->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $quiz = $current->quizzes()->first();
    $question = QuizQuestion::create([
        'quiz_id' => $quiz->id,
        'question' => 'What does HTML stand for?',
        'option_a' => 'HyperText Markup Language',
        'option_b' => 'High Text Machine Language',
        'correct_answer' => 'A',
        'points' => 1,
    ]);

    $this->actingAs($student)
        ->post(route('student.quiz.submit', $quiz), ['question_'.$question->id => 'A']);

    $recommendation = AIRecommendation::where('student_id', $student->id)->sole();

    expect($recommendation->course->title)->toBe('HTML Forms')
        ->and((float) $recommendation->recommendation_score)->toBe(100.0);
});
