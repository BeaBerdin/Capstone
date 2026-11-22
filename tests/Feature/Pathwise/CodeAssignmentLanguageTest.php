<?php

use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
| Manuscript Scope (PATHWISE-UPDATES, with MR-17): output evaluation covers
| HTML/CSS/JavaScript, Java, C, PHP and Python, as pasted code or a single
| source file.
*/

function codeAssignmentSetup(string $language = 'java'): array
{
    $teacher = pathwiseApprovedTeacher();
    $student = pathwiseUser('student');
    $course = pathwiseCourse($teacher, ['status' => 'published']);

    Enrollment::create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0,
    ]);

    $assignment = Assignment::create([
        'course_id' => $course->id,
        'title' => 'Hello World',
        'submission_type' => 'code',
        'programming_language' => $language,
        'max_score' => 100,
        'is_published' => true,
    ]);

    return [$teacher, $student, $course, $assignment];
}

test('a teacher can create a code assignment in each manuscript language', function (string $language) {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)
        ->post(route('teacher.assignments.store', $course), [
            'title' => 'Code task',
            'submission_type' => 'code',
            'programming_language' => $language,
            'max_score' => 100,
            'is_published' => 0,
        ])
        ->assertSessionHasNoErrors();

    expect(Assignment::sole()->programming_language)->toBe($language);
})->with(['javascript', 'java', 'c', 'php', 'python']);

test('a language outside the list is rejected', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $this->actingAs($teacher)
        ->post(route('teacher.assignments.store', $course), [
            'title' => 'Code task',
            'submission_type' => 'code',
            'programming_language' => 'ruby',
            'max_score' => 100,
            'is_published' => 0,
        ])
        ->assertSessionHasErrors('programming_language');

    expect(Assignment::count())->toBe(0);
});

test('the teacher form lists the languages with their display names', function () {
    $teacher = pathwiseApprovedTeacher();
    $course = pathwiseCourse($teacher, ['status' => 'draft']);

    $response = $this->actingAs($teacher)
        ->get(route('teacher.assignments.create', $course))
        ->assertOk();

    foreach (['javascript', 'java', 'c', 'php', 'python'] as $language) {
        $response->assertSee('value="'.$language.'"', false);
    }

    $response->assertSeeText('HTML/CSS/JavaScript')
        ->assertSeeText('PHP');
});

test('a student can submit a code assignment as one source file', function () {
    fakeFileDisks();
    Notification::fake();

    [, $student, , $assignment] = codeAssignmentSetup('java');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent(
                'Main.java',
                'public class Main { public static void main(String[] a) { System.out.println("Hi"); } }'
            ),
        ])
        ->assertSessionHasNoErrors();

    $submission = Submission::sole();

    expect($submission->answer_text)->toBeNull()
        ->and($submission->file_path)->not->toBeNull();

    Storage::disk('local')->assertExists($submission->file_path);
});

test('a student can submit a Python assignment as a .py file', function () {
    fakeFileDisks();
    Notification::fake();

    [, $student, , $assignment] = codeAssignmentSetup('python');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('main.py', 'print("Hi")'),
        ])
        ->assertSessionHasNoErrors();

    $submission = Submission::sole();

    expect($submission->file_path)->toEndWith('.py');

    Storage::disk('local')->assertExists($submission->file_path);
});

test('a Python assignment rejects a source file of another language', function () {
    fakeFileDisks();

    [, $student, , $assignment] = codeAssignmentSetup('python');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('Main.java', 'class Main {}'),
        ])
        ->assertSessionHasErrors('submission_file');

    expect(Submission::count())->toBe(0);
});

test('a .txt file is accepted for any code language', function () {
    fakeFileDisks();
    Notification::fake();

    [, $student, , $assignment] = codeAssignmentSetup('php');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('solution.txt', '<?php echo "Hi";'),
        ])
        ->assertSessionHasNoErrors();

    expect(Submission::sole()->file_path)->not->toBeNull();
});

test('a source file of another language is rejected', function () {
    fakeFileDisks();

    [, $student, , $assignment] = codeAssignmentSetup('java');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->createWithContent('main.py', 'print("Hi")'),
        ])
        ->assertSessionHasErrors('submission_file');

    expect(Submission::count())->toBe(0);
});

test('a source file larger than 25 MB is rejected', function () {
    fakeFileDisks();

    [, $student, , $assignment] = codeAssignmentSetup('c');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'submission_file' => UploadedFile::fake()->create('main.c', Assignment::UPLOAD_MAX_KB + 1),
        ])
        ->assertSessionHasErrors('submission_file');

    expect(Submission::count())->toBe(0);
});

test('a code submission needs typed code or a source file', function () {
    [, $student, , $assignment] = codeAssignmentSetup('c');

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [])
        ->assertSessionHasErrors('answer_text');

    expect(Submission::count())->toBe(0);
});

test('typed code still works and replaces an earlier uploaded file', function () {
    fakeFileDisks();
    Notification::fake();

    [, $student, , $assignment] = codeAssignmentSetup('c');

    $this->actingAs($student)->post(route('student.assignments.submit', $assignment), [
        'submission_file' => UploadedFile::fake()->createWithContent('main.c', 'int main(void) { return 0; }'),
    ]);

    $oldPath = Submission::sole()->file_path;

    $this->actingAs($student)
        ->post(route('student.assignments.submit', $assignment), [
            'answer_text' => 'int main(void) { return 1; }',
        ])
        ->assertSessionHasNoErrors();

    $submission = Submission::sole();

    expect($submission->answer_text)->toBe('int main(void) { return 1; }')
        ->and($submission->file_path)->toBeNull();

    Storage::disk('local')->assertMissing($oldPath);
});

test('the student page shows the language label and the source file upload', function () {
    [, $student, , $assignment] = codeAssignmentSetup('javascript');

    $this->actingAs($student)
        ->get(route('student.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('HTML/CSS/JavaScript')
        ->assertSee('Or upload one source file')
        ->assertSee('.html,.htm,.css,.js,.txt', false);
});
