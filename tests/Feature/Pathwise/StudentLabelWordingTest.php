<?php

use App\Models\AIRecommendation;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\LearningPath;

/*
| Wording on student pages and the department assignment page:
| - the recommendation score is labelled as the student's quiz score, and the
|   recommended course is marked "Recommended" (no "AI Pick" / "AI Match");
| - learning paths are "System generated" and the button is
|   "Generate Learning Path" (paths are built by fixed rules, not an AI model);
| - department assignment mentions admins and teachers only (no "instructor").
*/

function labelRecommendationSetup(): array
{
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $current = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Introduction to Web Development']);
    $next = pathwiseCourse($teacher, ['status' => 'published', 'title' => 'Python Programming Basics']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $current->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    AIRecommendation::create([
        'student_id' => $student->id,
        'course_id' => $next->id,
        'recommendation_score' => 80,
        'reason' => 'A good next step after your web development quiz.',
        'is_viewed' => false,
    ]);

    return [$student, $current, $next];
}

test('the recommendations page shows the quiz score and a Recommended badge', function () {
    [$student] = labelRecommendationSetup();

    $this->actingAs($student)
        ->get(route('student.recommendations'))
        ->assertOk()
        ->assertSee('Recommended')
        ->assertSee('Your quiz score')
        ->assertSee('Based on your latest quiz result')
        ->assertDontSee('AI Pick')
        ->assertDontSee('AI Match');
});

test('the course learning page shows the recommendation score as the quiz score', function () {
    [$student, $current] = labelRecommendationSetup();

    $this->actingAs($student)
        ->get(route('student.learn.course', $current))
        ->assertOk()
        ->assertSee('Your quiz score')
        ->assertSee('Based on your latest quiz result')
        ->assertDontSee('Recommendation match');
});

test('learning paths are labelled System generated with a Generate Learning Path button', function () {
    $student = pathwiseUser('student');

    $this->actingAs($student)
        ->get(route('student.learning-paths'))
        ->assertOk()
        ->assertSee('Generate Learning Path')
        ->assertDontSee('Generate AI Learning Path');

    $path = LearningPath::create([
        'student_id' => $student->id,
        'name' => 'College of Computer Studies Learning Path',
        'description' => 'Generated based on your learning performance.',
        'difficulty_level' => 'beginner',
        'is_generated' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.learning-paths'))
        ->assertOk()
        ->assertSee('System generated')
        ->assertDontSee('AI Generated');

    $this->actingAs($student)
        ->get(route('student.learning-paths.show', $path))
        ->assertOk()
        ->assertSee('System generated')
        ->assertDontSee('AI Generated');
});

test('department assignment mentions admins and teachers only', function () {
    $superAdmin = pathwiseUser('super_admin');
    $teacher = pathwiseUser('teacher', ['name' => 'Paolo Reyes']);
    $student = pathwiseUser('student', ['name' => 'Dino Cruz']);
    Department::create(['name' => 'College of Computer Studies']);

    $this->actingAs($superAdmin)
        ->get(route('departments.assign'))
        ->assertOk()
        ->assertSee('Assign an admin or teacher to a department.')
        ->assertSee('Only users with the admin or teacher role are listed.')
        ->assertSee('Paolo Reyes')
        ->assertDontSee('Dino Cruz')
        ->assertDontSee('instructor');
});

test('a student cannot be assigned to a department', function () {
    $superAdmin = pathwiseUser('super_admin');
    $student = pathwiseUser('student');
    $department = Department::create(['name' => 'College of Computer Studies']);

    $this->actingAs($superAdmin)
        ->post(route('departments.assign.store'), ['user_id' => $student->id, 'department_id' => $department->id])
        ->assertSessionHas('error', 'Only admins and teachers can be assigned to a department.');

    expect($student->fresh()->department_id)->toBeNull();
});
