<?php

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\User;

/*
| Learning paths (Functional Requirement i) are rule-based: the student's
| best quiz result picks the category, the average quiz score picks the
| difficulty, and published courses with published lessons and quizzes are
| listed from beginner to advanced. Free and paid courses are both included.
|
| These tests use free courses on purpose. The controller used to filter on
| a courses.is_free column that no migration creates; on SQLite that filter
| silently matched nothing, and on a fresh MySQL database it would fail.
*/

function pathCategory(string $name): CourseCategory
{
    return CourseCategory::firstOrCreate(['name' => $name], ['is_active' => true]);
}

/**
 * A published course with one published lesson and one published quiz.
 */
function pathCourse(string $title, CourseCategory $category, string $difficulty, array $attributes = [], ?Department $department = null): Course
{
    $course = pathwiseCourse(pathwiseUser('teacher', ['department_id' => $department?->id]), array_merge([
        'title' => $title,
        'category_id' => $category->id,
        'difficulty_level' => $difficulty,
        'status' => 'published',
        'price' => 0,
    ], $attributes));

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => $title.' Lesson',
        'content' => 'Content',
        'lesson_type' => 'text',
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

    return $course;
}

function pathQuizResult(User $student, Course $course, float $percentage): void
{
    Enrollment::firstOrCreate(
        ['student_id' => $student->id, 'course_id' => $course->id],
        ['status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]
    );

    QuizResult::create([
        'student_id' => $student->id,
        'quiz_id' => $course->quizzes()->first()->id,
        'score' => (int) round($percentage),
        'total_items' => 100,
        'percentage' => $percentage,
        'remarks' => $percentage >= 75 ? 'passed' : 'failed',
        'attempt_number' => 1,
        'completed_at' => now(),
    ]);
}

function generatePath($test, User $student): ?LearningPath
{
    $test->actingAs($student)->post(route('student.learning-paths.generate'));

    return LearningPath::where('student_id', $student->id)->where('is_generated', true)->first();
}

test('a learning path lists the best category\'s published courses, free and paid, from beginner to advanced', function () {
    $web = pathCategory('Web Development');
    $student = pathwiseUser('student');

    $taken = pathCourse('HTML Basics', $web, 'beginner');
    pathCourse('Advanced Laravel', $web, 'advanced', ['price' => 499]);
    pathCourse('CSS Layouts', $web, 'intermediate');
    pathCourse('JavaScript Basics', $web, 'beginner', ['price' => 299]);

    pathQuizResult($student, $taken, 80);

    $this->actingAs($student)
        ->post(route('student.learning-paths.generate'))
        ->assertRedirect()
        ->assertSessionHas('success', 'Learning path generated successfully.');

    $path = LearningPath::where('student_id', $student->id)->sole();

    expect($path->name)->toBe('Web Development Learning Path')
        ->and($path->difficulty_level)->toBe('intermediate')   // average 80%: 70–84
        ->and($path->courses()->pluck('title')->all())
        ->toBe(['HTML Basics', 'JavaScript Basics', 'CSS Layouts', 'Advanced Laravel']);
});

test('the average quiz score sets the difficulty', function (array $scores, string $difficulty) {
    $category = pathCategory('Programming');
    $student = pathwiseUser('student');

    foreach ($scores as $index => $score) {
        pathQuizResult($student, pathCourse('Course '.$index, $category, 'beginner'), $score);
    }

    expect(generatePath($this, $student)->difficulty_level)->toBe($difficulty);
})->with([
    'advanced at 85% or more' => [[90, 80], 'advanced'],
    'intermediate from 70%' => [[70], 'intermediate'],
    'beginner below 70%' => [[60, 50], 'beginner'],
]);

test('draft courses and courses without published lessons or quizzes are left out', function () {
    $web = pathCategory('Web Development');
    $student = pathwiseUser('student');

    $taken = pathCourse('HTML Basics', $web, 'beginner');
    pathCourse('Draft Course', $web, 'beginner', ['status' => 'draft']);

    $noQuiz = pathCourse('No Published Quiz', $web, 'beginner');
    $noQuiz->quizzes()->update(['is_published' => false]);

    $noLesson = pathCourse('No Published Lesson', $web, 'beginner');
    $noLesson->lessons()->update(['is_published' => false]);

    pathQuizResult($student, $taken, 90);

    expect(generatePath($this, $student)->courses()->pluck('title')->all())->toBe(['HTML Basics']);
});

test('a short path is filled up to three courses from other categories', function () {
    $web = pathCategory('Web Development');
    $data = pathCategory('Data Science');
    $student = pathwiseUser('student');

    $taken = pathCourse('HTML Basics', $web, 'beginner');
    pathCourse('Python for Data', $data, 'beginner');
    pathCourse('Statistics', $data, 'intermediate');
    pathCourse('Machine Vision', $data, 'advanced');

    pathQuizResult($student, $taken, 75);

    expect(generatePath($this, $student)->courses()->pluck('title')->all())
        ->toBe(['HTML Basics', 'Python for Data', 'Statistics']);
});

test('generating again updates the same path instead of adding another', function () {
    $web = pathCategory('Web Development');
    $student = pathwiseUser('student');

    pathQuizResult($student, pathCourse('HTML Basics', $web, 'beginner'), 60);
    generatePath($this, $student);

    pathQuizResult($student, pathCourse('CSS Layouts', $web, 'intermediate'), 100);
    $path = generatePath($this, $student);

    expect(LearningPath::where('student_id', $student->id)->count())->toBe(1)
        ->and($path->difficulty_level)->toBe('intermediate')   // average of 60 and 100
        ->and($path->courses()->count())->toBe(2);
});

test('without a quiz result in a published course there is no path', function () {
    $student = pathwiseUser('student');

    $draft = pathCourse('Draft Course', pathCategory('Web Development'), 'beginner', ['status' => 'draft']);
    pathQuizResult($student, $draft, 90);

    $this->actingAs($student)
        ->post(route('student.learning-paths.generate'))
        ->assertRedirect(route('student.learning-paths'))
        ->assertSessionHas('error', 'No eligible quiz results found. Complete a quiz in a published course first to generate your learning path.');

    expect(LearningPath::count())->toBe(0);
});

test('the administrator can choose free and paid courses for a learning path', function () {
    $category = pathCategory('Web Development');
    $department = Department::create(['name' => 'Computing']);
    $freeCourse = pathCourse('Free Course', $category, 'beginner', department: $department);
    pathCourse('Paid Course', $category, 'beginner', ['price' => 499], department: $department);

    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $this->actingAs($admin)
        ->get(route('learning-paths.create'))
        ->assertOk()
        ->assertSee('Free Course')
        ->assertSee('Paid Course');

    $path = LearningPath::create(['name' => 'Admin Path', 'difficulty_level' => 'beginner']);
    $path->courses()->attach($freeCourse->id, ['course_order' => 1]);

    $this->actingAs($admin)
        ->get(route('learning-paths.edit', $path))
        ->assertOk()
        ->assertSee('Free Course')
        ->assertSee('Paid Course');
});

test('the code no longer refers to the undefined courses.is_free column', function () {
    // No migration creates courses.is_free (it is not in the data dictionary
    // either). Guard against the filter coming back.
    expect(Illuminate\Support\Facades\Schema::hasColumn('courses', 'is_free'))->toBeFalse()
        ->and(file_get_contents(app_path('Http/Controllers/LearningPathController.php')))->not->toContain('is_free');
});
