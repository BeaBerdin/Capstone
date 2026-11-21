<?php

use App\Models\Course;
use App\Models\Department;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;

/*
| The course cover stored in courses.thumbnail is shown wherever a course is
| listed, for every role, and a placeholder is shown when there is none.
*/

const COVER_PATH = 'courses/thumbnails/web-dev-cover.jpg';

function coverUrl(): string
{
    return asset('storage/'.COVER_PATH);
}

function coveredCourse(App\Models\User $teacher, array $attributes = []): Course
{
    return pathwiseCourse($teacher, array_merge([
        'status' => 'published',
        'thumbnail' => COVER_PATH,
    ], $attributes));
}

test('the cover URL is resolved in one place', function () {
    expect(Course::thumbnailUrlFor(null))->toBeNull()
        ->and(Course::thumbnailUrlFor(''))->toBeNull()
        ->and(Course::thumbnailUrlFor(COVER_PATH))->toBe(coverUrl())
        ->and(Course::thumbnailUrlFor('/'.COVER_PATH))->toBe(coverUrl())
        ->and(Course::thumbnailUrlFor('https://cdn.example.org/a.jpg'))->toBe('https://cdn.example.org/a.jpg');

    $course = coveredCourse(pathwiseUser('teacher'));

    expect($course->thumbnail_url)->toBe(coverUrl());
});

test('the cover component falls back to a placeholder without an image', function () {
    $course = coveredCourse(pathwiseUser('teacher'), ['thumbnail' => null]);

    $html = (string) $this->blade('<x-pw.course-cover :course="$course" />', ['course' => $course]);

    expect($html)->not->toContain('<img')->toContain('aspect-video');

    $course->thumbnail = COVER_PATH;

    $html = (string) $this->blade('<x-pw.course-cover :course="$course" variant="thumb" />', ['course' => $course]);

    expect($html)->toContain('src="'.coverUrl().'"')->toContain('object-cover');
});

test('an administrator sees the cover on course management, the dashboard, lessons and learning paths', function () {
    $department = Department::create(['name' => 'Computing']);
    $teacher = pathwiseUser('teacher', ['department_id' => $department->id]);
    $admin = pathwiseUser('admin', ['department_id' => $department->id]);

    $pending = coveredCourse($teacher, ['status' => 'pending']);

    Lesson::create([
        'course_id' => $pending->id,
        'title' => 'Lesson one',
        'content' => 'Hello',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_published' => true,
    ]);

    $path = LearningPath::create(['name' => 'Web path', 'description' => 'Path', 'difficulty_level' => 'beginner']);
    $path->courses()->attach($pending->id, ['course_order' => 1]);

    $this->actingAs($admin)->get(route('courses.index'))->assertOk()->assertSee(coverUrl());
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee(coverUrl());
    $this->actingAs($admin)->get(route('lessons.index'))->assertOk()->assertSee(coverUrl());
    $this->actingAs($admin)->get(route('learning-paths.show', $path))->assertOk()->assertSee(coverUrl());
});

test('course management still renders for a course without a cover', function () {
    $department = Department::create(['name' => 'Computing']);
    coveredCourse(pathwiseUser('teacher', ['department_id' => $department->id]), ['thumbnail' => null, 'status' => 'pending']);

    $this->actingAs(pathwiseUser('admin', ['department_id' => $department->id]))
        ->get(route('courses.index'))
        ->assertOk()
        ->assertDontSee('storage/courses')
        ->assertSee('Approve');
});

test('a super admin sees the cover on the dashboard and in reports', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');
    $superAdmin = pathwiseUser('super_admin');

    $course = coveredCourse($teacher);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 50,
    ]);

    $lesson = Lesson::create([
        'course_id' => $course->id,
        'title' => 'Lesson one',
        'content' => 'Hello',
        'lesson_type' => 'text',
        'lesson_order' => 1,
        'is_published' => true,
    ]);

    LessonProgress::create([
        'student_id' => $student->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    $this->actingAs($superAdmin)->get(route('dashboard'))->assertRedirect();

    $this->actingAs($superAdmin)
        ->get('/super-admin-dashboard')
        ->assertOk()
        ->assertSee(coverUrl());

    $response = $this->actingAs($superAdmin)->get(route('reports.index'))->assertOk();

    // Most Active Courses and Most Popular Courses.
    expect(substr_count($response->getContent(), coverUrl()))->toBe(2);
});

test('a teacher sees the cover on my courses and analytics', function () {
    $teacher = pathwiseUser('teacher');

    coveredCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)->get(route('teacher.my-courses'))->assertOk()->assertSee(coverUrl());
    $this->actingAs($teacher)->get(route('teacher.analytics'))->assertOk()->assertSee(coverUrl());
});

test('a student sees the cover in the marketplace, course details, my courses, the dashboard and an invitation', function () {
    $teacher = pathwiseUser('teacher');
    $student = pathwiseUser('student');

    $course = coveredCourse($teacher);

    $this->actingAs($student)->get(route('student.marketplace'))->assertOk()->assertSee(coverUrl());
    $this->actingAs($student)->get(route('student.course.show', $course))->assertOk()->assertSee(coverUrl());

    $invitation = CourseInvitation::create([
        'course_id' => $course->id,
        'created_by' => $teacher->id,
        'code' => 'COVERTEST123',
    ]);

    $this->actingAs($student)
        ->get(route('course-invitations.show', $invitation->code))
        ->assertOk()
        ->assertSee(coverUrl());

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 10,
    ]);

    $this->actingAs($student)->get(route('student.my-courses'))->assertOk()->assertSee(coverUrl());
    $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee(coverUrl());
});

/*
| Strict consistency: three courses with different covers are traced across
| every role. Each page must show each course's own cover, never another
| course's cover, and never drop a cover that exists.
*/

/**
 * Three published courses, each with its own cover, plus the records that
 * make them appear on every dashboard.
 *
 * @return array{teacher: App\Models\User, student: App\Models\User, courses: array<string, Course>, path: LearningPath}
 */
function threeCoveredCourses(): array
{
    $department = Department::create(['name' => 'Computing']);
    $teacher = pathwiseUser('teacher', ['department_id' => $department->id]);
    $student = pathwiseUser('student');

    $courses = [
        'courses/python-cover.png' => pathwiseCourse($teacher, ['title' => 'Python Programming Basics', 'status' => 'published', 'thumbnail' => 'courses/python-cover.png']),
        'courses/webdesign-cover.jpg' => pathwiseCourse($teacher, ['title' => 'Responsive Web Design', 'status' => 'published', 'thumbnail' => 'courses/webdesign-cover.jpg']),
        'courses/datasci-cover.webp' => pathwiseCourse($teacher, ['title' => 'Data Science Foundations', 'status' => 'published', 'thumbnail' => 'courses/datasci-cover.webp']),
    ];

    $path = LearningPath::create(['name' => 'Full stack path', 'description' => 'Path', 'difficulty_level' => 'beginner']);

    $order = 1;

    foreach ($courses as $course) {
        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'enrolled_at' => now(),
            'progress_percentage' => 40,
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Lesson for '.$course->title,
            'content' => 'Hello',
            'lesson_type' => 'text',
            'lesson_order' => 1,
            'is_published' => true,
        ]);

        LessonProgress::create([
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        App\Models\AIRecommendation::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'recommendation_score' => 80,
            'reason' => 'Based on your quiz results.',
        ]);

        $path->courses()->attach($course->id, ['course_order' => $order++]);
    }

    return compact('teacher', 'student', 'courses', 'path');
}

/**
 * Every cover image on the page belongs to the course named right after it,
 * and (when $all is true) all three courses show their cover.
 *
 * @param  array<string, Course>  $courses
 */
function assertCoversBelongToTheirCourses(string $html, array $courses, bool $all = true): void
{
    $html = html_entity_decode($html);

    $titles = array_map(fn (Course $course) => $course->title, $courses);

    foreach ($courses as $path => $course) {
        $url = asset('storage/'.$path);

        if ($all) {
            expect(str_contains($html, 'src="'.$url.'"'))->toBeTrue("Cover of [{$course->title}] is missing.");
        }

        $offset = 0;

        while (($position = strpos($html, 'src="'.$url.'"', $offset)) !== false) {
            $offset = $position + 1;

            // The first course title after the image must be this course's.
            $next = collect($titles)
                ->mapWithKeys(fn ($title) => [$title => strpos($html, $title, $position)])
                ->filter(fn ($found) => $found !== false)
                ->sort()
                ->keys()
                ->first();

            expect($next)->toBe($course->title);
        }
    }
}

test('three courses keep their own cover on every student page', function () {
    ['student' => $student, 'courses' => $courses, 'path' => $path] = threeCoveredCourses();

    // The marketplace lists courses the student does not own yet.
    $html = $this->actingAs(pathwiseUser('student'))->get(route('student.marketplace'))->assertOk()->getContent();
    assertCoversBelongToTheirCourses($html, $courses);

    $pages = [
        route('student.my-courses'),
        route('student.recommendations'),
        route('student.learning-paths'),
        route('student.learning-paths.show', $path),
    ];

    foreach ($pages as $page) {
        $html = $this->actingAs($student)->get($page)->assertOk()->getContent();

        assertCoversBelongToTheirCourses($html, $courses);
    }

    // Dashboard shows a selection of the courses, never a mismatched cover.
    $html = $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->getContent();
    assertCoversBelongToTheirCourses($html, $courses, all: false);
    expect(substr_count($html, 'storage/courses/'))->toBeGreaterThan(0);

    // Course details and the learning page show that course's own cover.
    foreach ($courses as $coverPath => $course) {
        foreach ([route('student.course.show', $course), route('student.learn.course', $course)] as $page) {
            $html = $this->actingAs($student)->get($page)->assertOk()->getContent();

            expect($html)->toContain('src="'.asset('storage/'.$coverPath).'"');
            assertCoversBelongToTheirCourses($html, $courses, all: false);
        }
    }
});

test('recommendations show a full 16:9 cover with no blurred backdrop', function () {
    ['student' => $student, 'courses' => $courses] = threeCoveredCourses();

    $html = $this->actingAs($student)->get(route('student.recommendations'))->assertOk()->getContent();

    expect($html)
        ->not->toContain('object-contain')
        ->not->toContain('opacity-50 blur-xl');

    // One image per recommendation, in the same frame as Learning Paths.
    foreach (array_keys($courses) as $coverPath) {
        expect(substr_count($html, 'src="'.asset('storage/'.$coverPath).'"'))->toBe(1);
    }

    expect(substr_count($html, 'aspect-video w-full rounded-xl'))->toBe(3);
});

test('learning path steps show a full-bleed 16:9 cover with no blurred backdrop', function () {
    ['student' => $student, 'courses' => $courses, 'path' => $path] = threeCoveredCourses();

    $html = $this->actingAs($student)->get(route('student.learning-paths.show', $path))->assertOk()->getContent();

    expect($html)
        ->not->toContain('object-contain')
        ->not->toContain('opacity-50 blur-xl');

    // One image per step, each in the same 16:9 rounded container.
    foreach (array_keys($courses) as $coverPath) {
        expect(substr_count($html, 'src="'.asset('storage/'.$coverPath).'"'))->toBe(1);
    }

    expect(substr_count($html, 'aspect-video w-full rounded-xl'))->toBe(3);
});

test('the recommended learning sequence shows each course with its own cover, in order, with no gradient behind it', function () {
    ['student' => $student, 'courses' => $courses, 'path' => $path] = threeCoveredCourses();

    $html = html_entity_decode(
        $this->actingAs($student)->get(route('student.learning-paths.show', $path))->assertOk()->getContent()
    );

    $section = substr($html, strpos($html, 'Recommended Learning Sequence'));

    // Steps follow the path order, and each step's image is that course's cover.
    $positions = [];

    foreach ($courses as $coverPath => $course) {
        $image = strpos($section, 'src="'.asset('storage/'.$coverPath).'"');

        expect($image)->not->toBeFalse();

        $title = strpos($section, $course->title, $image);

        expect($title)->not->toBeFalse()
            ->and(substr($section, $image + 5, $title - $image - 5))->not->toContain('src="');

        $positions[] = $image;
    }

    expect($positions)->toBe(collect($positions)->sort()->values()->all());

    // The placeholder gradient is hidden whenever a cover exists.
    expect(preg_match_all('/data-cover-placeholder\s+hidden/', $section))->toBe(3);
});

test('the course learning page shows the full framed cover, as on AI Recommendations', function () {
    ['student' => $student, 'courses' => $courses] = threeCoveredCourses();

    foreach ($courses as $coverPath => $course) {
        $html = $this->actingAs($student)->get(route('student.learn.course', $course))->assertOk()->getContent();

        // The first framed cover on the page is the hero, showing this course's cover.
        $start = strpos($html, 'aspect-video w-full rounded-xl');

        expect($start)->not->toBeFalse();

        $hero = substr($html, $start, 2500);

        expect($hero)
            ->toContain('src="'.asset('storage/'.$coverPath).'"')
            ->toContain('object-cover object-center')
            ->and($html)->not->toContain('from-slate-950/45');
    }
});

test('a framed cover still shows the placeholder when the course has no cover', function () {
    $course = coveredCourse(pathwiseUser('teacher'), ['thumbnail' => null]);

    $html = (string) $this->blade('<x-pw.course-cover :course="$course" framed />', ['course' => $course]);

    expect($html)->toContain('data-cover-placeholder')
        ->not->toContain('<img')
        ->and(preg_match('/data-cover-placeholder\s+hidden/', $html))->toBe(0);
});

test('three courses keep their own cover on every teacher page', function () {
    ['teacher' => $teacher, 'courses' => $courses] = threeCoveredCourses();

    $pages = [
        route('teacher.dashboard'),
        route('teacher.my-courses'),
        route('teacher.lessons.index'),
        route('teacher.analytics'),
    ];

    foreach ($pages as $page) {
        $html = $this->actingAs($teacher)->get($page)->assertOk()->getContent();

        assertCoversBelongToTheirCourses($html, $courses);
    }

    foreach ($courses as $coverPath => $course) {
        foreach ([route('teacher.lessons', $course), route('teacher.course.students', $course)] as $page) {
            $html = $this->actingAs($teacher)->get($page)->assertOk()->getContent();

            expect($html)->toContain('src="'.asset('storage/'.$coverPath).'"');
        }
    }
});

test('three courses keep their own cover on every admin and super admin page', function () {
    ['teacher' => $teacher, 'courses' => $courses, 'path' => $path] = threeCoveredCourses();

    $admin = pathwiseUser('admin', ['department_id' => $teacher->department_id]);
    $superAdmin = pathwiseUser('super_admin');

    $adminPages = [
        route('courses.index'),
        route('lessons.index'),
        route('learning-paths.show', $path),
        route('ai-recommendations.index'),
    ];

    foreach ($adminPages as $page) {
        $html = $this->actingAs($admin)->get($page)->assertOk()->getContent();

        assertCoversBelongToTheirCourses($html, $courses);
    }

    foreach (['/super-admin-dashboard', route('reports.index')] as $page) {
        $html = $this->actingAs($superAdmin)->get($page)->assertOk()->getContent();

        assertCoversBelongToTheirCourses($html, $courses);
    }

    // Pending approvals on the admin dashboard.
    Course::query()->update(['status' => 'pending']);

    $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    assertCoversBelongToTheirCourses($html, $courses);
});

test('a replaced cover shows everywhere and the old one is gone', function () {
    fakeFileDisks();

    ['teacher' => $teacher, 'student' => $student, 'courses' => $courses, 'path' => $path] = threeCoveredCourses();

    $python = $courses['courses/python-cover.png'];

    // Course details are editable only while the course is a draft or was
    // returned for revision; it is published again afterwards.
    $python->update(['status' => 'draft']);

    $this->actingAs($teacher)
        ->put(route('teacher.courses.update', $python), [
            'title' => $python->title,
            'description' => $python->description,
            'category_id' => $python->category_id,
            'difficulty_level' => 'beginner',
            'price' => 0,
            'thumbnail' => Illuminate\Http\UploadedFile::fake()->image('new-python.png', 1280, 720),
        ])
        ->assertSessionHasNoErrors();

    $newPath = $python->fresh()->thumbnail;

    $python->update(['status' => 'published']);

    expect($newPath)->not->toBe('courses/python-cover.png')->not->toBeEmpty();

    $newUrl = asset('storage/'.$newPath);
    $oldUrl = asset('storage/courses/python-cover.png');

    $admin = pathwiseUser('admin', ['department_id' => $teacher->department_id]);
    $superAdmin = pathwiseUser('super_admin');

    $visits = [
        [pathwiseUser('student'), route('student.marketplace')],
        [$student, route('student.my-courses')],
        [$student, route('student.recommendations')],
        [$student, route('student.learning-paths.show', $path)],
        [$student, route('student.course.show', $python)],
        [$teacher, route('teacher.my-courses')],
        [$teacher, route('teacher.dashboard')],
        [$admin, route('courses.index')],
        [$superAdmin, '/super-admin-dashboard'],
        [$superAdmin, route('reports.index')],
    ];

    foreach ($visits as [$user, $page]) {
        $html = $this->actingAs($user)->get($page)->assertOk()->getContent();

        expect($html)->toContain('src="'.$newUrl.'"')->not->toContain($oldUrl);
    }
});
