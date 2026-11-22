<?php
/** Fake fixtures, called only by the guarded CLI browser harness on a new runtime. */
if (PHP_SAPI !== 'cli' || !isset($fixtures, $runtime, $db) || isset($fixtures['review'])) {
    throw new RuntimeException('Fresh isolated browser fixture context required.');
}
$teacher = App\Models\User::findOrFail($fixtures['users']['teacher']['id']);
$users = [];
$otherDepartment = App\Models\Department::create(['name' => 'Other review department '.$fixtures['id']]);
foreach (['admin' => 'admin', 'foreignAdmin' => 'admin', 'completed' => 'student', 'cancelled' => 'student'] as $name => $role) {
    $user = new App\Models\User;
    $user->forceFill(['name' => 'Synthetic '.$name, 'email' => strtolower($name).'+'.$fixtures['id'].'@example.test', 'password' => getenv('PATHWISE_BROWSER_PASSWORD'), 'email_verified_at' => now(), 'is_active' => true, 'department_id' => $name === 'foreignAdmin' ? $otherDepartment->id : $teacher->department_id])->save();
    $user->roles()->attach(App\Models\Role::firstOrCreate(['name' => $role])->id);
    $fixtures['users'][$name] = ['id' => $user->id, 'email' => $user->email];
}
$course = App\Models\Course::create(['teacher_id' => $teacher->id, 'category_id' => $fixtures['category'], 'title' => 'Review lifecycle '.$fixtures['id'], 'description' => 'Synthetic review scenario', 'difficulty_level' => 'beginner', 'price' => 0, 'status' => 'draft', 'certificate_available' => false]);
$reading = $course->lessons()->create(['title' => 'Review reading', 'lesson_type' => 'text', 'content' => 'Confidential synthetic reading', 'lesson_order' => 1, 'is_published' => true]);
$reading->sections()->create(['type' => 'text', 'body_format' => 'html', 'body' => '<p><strong>Confidential synthetic reading</strong></p>', 'position' => 0]);
$sections = [];
foreach (['image' => 'png', 'file' => 'pdf'] as $type => $extension) {
    $path = 'lesson-sections/review.'.$extension;
    $content = $type === 'image' ? base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=') : "%PDF-1.4\nSynthetic private PDF\n%%EOF";
    Illuminate\Support\Facades\Storage::disk('local')->put($path, $content);
    $sections[$type] = $reading->sections()->create(['type' => $type, 'file_path' => $path, 'original_name' => 'review.'.$extension, 'position' => count($sections)+1])->id;
}
$quizLesson = $course->lessons()->create(['title' => 'Review quiz', 'lesson_type' => 'quiz', 'lesson_order' => 2, 'is_published' => true]);
$quiz = App\Models\Quiz::create(['course_id' => $course->id, 'lesson_id' => $quizLesson->id, 'title' => 'Review quiz', 'passing_score' => 75, 'is_published' => true]);
$question = $quiz->questions()->create(['question' => 'Synthetic answer?', 'option_a' => 'Yes', 'option_b' => 'No', 'correct_answer' => 'A', 'points' => 1]);
$activityLesson = $course->lessons()->create(['title' => 'Review Activity', 'lesson_type' => 'activity', 'lesson_order' => 3, 'is_published' => true]);
$activity = $activityLesson->assignments()->create(['course_id' => $course->id, 'title' => 'Review Activity', 'description' => 'Confidential Activity instructions', 'submission_type' => 'essay', 'answer_format' => 'either', 'max_score' => 100, 'is_activity' => true, 'is_published' => true, 'ai_enabled' => false]);
foreach (['completed', 'cancelled'] as $role) App\Models\Enrollment::create(['student_id' => $fixtures['users'][$role]['id'], 'course_id' => $course->id, 'status' => $role, 'enrolled_at' => now(), 'completed_at' => $role === 'completed' ? now() : null, 'progress_percentage' => $role === 'completed' ? 100 : 25]);
$completed = $fixtures['users']['completed']['id'];
App\Models\LessonProgress::create(['student_id' => $completed, 'lesson_id' => $reading->id, 'status' => 'completed', 'completed_at' => now()]);
App\Models\QuizResult::create(['student_id' => $completed, 'quiz_id' => $quiz->id, 'score' => 1, 'total_items' => 1, 'percentage' => 100, 'remarks' => 'passed', 'attempt_number' => 1, 'completed_at' => now()]);
Illuminate\Support\Facades\Storage::disk('local')->put('assignment-submissions/review.txt', 'Own graded work');
$submission = App\Models\Submission::create(['assignment_id' => $activity->id, 'student_id' => $completed, 'answer_text' => 'Preserved answer', 'file_path' => 'assignment-submissions/review.txt', 'score' => 95, 'feedback' => 'Preserved grade', 'status' => 'graded', 'submitted_at' => now()]);
$fixtures['review'] = ['course' => $course->id, 'reading' => $reading->id, 'quiz' => $quiz->id, 'question' => $question->id, 'activity' => $activity->id, 'activityLesson' => $activityLesson->id, 'sections' => $sections, 'submission' => $submission->id];
file_put_contents($runtime.'/fixtures.json', json_encode($fixtures, JSON_PRETTY_PRINT));
