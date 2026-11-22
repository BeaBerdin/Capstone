<?php
/** Disposable-browser harness only. Never included by application routes/providers. */
$repo = getenv('PATHWISE_REPO_ROOT');
$runtime = getenv('PATHWISE_BROWSER_RUNTIME');
if (!$repo || !$runtime || !is_dir($runtime) || !str_starts_with(basename($runtime), 'pathwise-reading-browser-')) {
    throw new RuntimeException('An explicitly created disposable browser runtime is required.');
}
$repo = realpath($repo);
$runtime = realpath($runtime);
$db = $runtime.'/browser.sqlite';
if (file_exists($repo.'/bootstrap/cache/config.php')) throw new RuntimeException('Refusing cached application configuration.');
if (!file_exists($runtime.'/created-new-runtime')) throw new RuntimeException('Missing new-runtime marker.');
require $repo.'/vendor/autoload.php';

function browserApp(string $repo, string $runtime, string $db) {
    $app = require $repo.'/bootstrap/app.php';
    $app->useEnvironmentPath($runtime);
    $app->useStoragePath($runtime.'/storage');
    $app->booted(function () use ($db) {
        if (config('database.default') !== 'sqlite' || realpath(config('database.connections.sqlite.database')) !== realpath($db)) throw new RuntimeException('Browser database isolation failed.');
        if (!in_array(config('mail.default'), ['log', 'array'], true)) throw new RuntimeException('Browser mail must be local only.');
        foreach (['gemini.api_key','paymongo.secret_key','paymongo.public_key','paymongo.webhook_secret','ses.key','ses.secret','postmark.key','resend.key'] as $key) {
            if (filled(config('services.'.$key))) throw new RuntimeException('External credentials are forbidden.');
        }
        Illuminate\Support\Facades\Http::preventStrayRequests();
    });
    return $app;
}

if (PHP_SAPI === 'cli-server') {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $public = realpath($repo.'/public');
    $file = realpath($public.'/'.ltrim(rawurldecode($path), '/'));
    if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file) && !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['php','phtml','phar'], true)) {
        $types = ['js'=>'text/javascript','css'=>'text/css','png'=>'image/png','jpg'=>'image/jpeg','svg'=>'image/svg+xml','woff'=>'font/woff','woff2'=>'font/woff2','ico'=>'image/x-icon','json'=>'application/json'];
        header('Content-Type: '.($types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
        readfile($file);return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sections'][0]['body'])) {
        file_put_contents($runtime.'/multipart-wire.jsonl', json_encode(['sectionId'=>(int)($_POST['sections'][0]['id'] ?? 0),'contentType'=>$_SERVER['CONTENT_TYPE'] ?? '', 'body'=>$_POST['sections'][0]['body']], JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    $app = browserApp($repo, $runtime, $db);
    if ($path === '/__browser-safety') {
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        header('Content-Type: application/json');
        echo json_encode(['runtimeId'=>getenv('PATHWISE_BROWSER_ID'),'sqlite'=>config('database.default')==='sqlite','database'=>realpath($db),'mail'=>config('mail.default'),'curlDisabled'=>!function_exists('curl_exec')&&!function_exists('curl_multi_exec'),'streamsDisabled'=>!filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN)&&!function_exists('stream_socket_client'),'proxy'=>getenv('HTTP_PROXY')]);return;
    }
    $app->handleRequest(Illuminate\Http\Request::capture());return;
}

$app = browserApp($repo, $runtime, $db);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$action = $argv[1] ?? 'snapshot';
use App\Models\{User,Role,Department,StudentApplication,CourseCategory,Course,CourseTemplate,Enrollment,Lesson,Quiz,Assignment,LessonProgress};
use Illuminate\Support\Facades\DB;

if ($action === 'init') {
    if (filesize($db) !== 0 || file_exists($runtime.'/fixtures.json')) throw new RuntimeException('Refusing an existing database.');
    $code = Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true, '--no-interaction'=>true]);
    if ($code !== 0) throw new RuntimeException('Disposable migration failed.');
    $password = getenv('PATHWISE_BROWSER_PASSWORD');
    $suffix = getenv('PATHWISE_BROWSER_ID');
    if (!$password || strlen($password)<24) throw new RuntimeException('A generated password is required.');
    $department = Department::create(['name'=>'Browser test '.$suffix]);
    $users=[];
    foreach (['edp'=>'super_admin','teacher'=>'teacher','student'=>'student','outsider'=>'student'] as $name=>$role) {
        $user = new User;
        $user->forceFill(['name'=>'Test '.ucfirst($name),'email'=>$name.'+'.$suffix.'@example.test','password'=>$password,'department_id'=>$department->id,'email_verified_at'=>now(),'is_active'=>true])->save();
        $user->roles()->attach(Role::firstOrCreate(['name'=>$role])->id);$users[$name]=$user;
    }
    $review=StudentApplication::submitForTeacher($users['teacher'],'disposable_browser');
    $review->forceFill(['status'=>'approved','reviewed_by'=>$users['edp']->id,'reviewed_at'=>now()])->save();
    $category=CourseCategory::create(['name'=>'Browser test category '.$suffix,'is_active'=>true]);
    (new Database\Seeders\CourseTemplateSeeder)->run();
    $courses=[];
    foreach (['sandbox'=>'draft','regression'=>'published'] as $name=>$status) {
        $courses[$name]=Course::create(['teacher_id'=>$users['teacher']->id,'category_id'=>$category->id,'title'=>'Browser '.$name.' '.$suffix,'description'=>'Synthetic testing only','difficulty_level'=>'beginner','price'=>0,'status'=>$status,'estimated_hours'=>1,'certificate_available'=>false]);
        Enrollment::create(['student_id'=>$users['student']->id,'course_id'=>$courses[$name]->id,'status'=>'active','enrolled_at'=>now()]);
    }
    $legacyBody="  <h2>Literal legacy</h2>\n\n  Café λ 日本語\nLast line.  ";
    $legacy=$courses['sandbox']->lessons()->create(['title'=>'Literal legacy','lesson_type'=>'text','content'=>$legacyBody,'lesson_order'=>1,'is_published'=>true]);
    $legacySection=$legacy->sections()->create(['type'=>'text','body_format'=>'plain','body'=>$legacyBody,'position'=>0]);
    $base="  Café λ\n\n  indented\nLast line.  ";
    $unchanged=['LF and Unicode'=>$base,'CRLF and blank lines'=>"\r\n\r\n  Café λ 日本語\r\n\r\nEnd.  \r\n",'leading LF'=>"\n\n  Résumé 日本語\nEnd\n\n",'mixed line endings'=>"  One\r\nTwo\nThree\rFour  ",'single line'=>"\t  <b>Literal</b> & café  \t"];
    $edits=['remove leading space'=>substr($base,1),'add leading space'=>' '.$base,'remove trailing spaces'=>rtrim($base),'add trailing spaces'=>$base.'  ','add blank line'=>str_replace("\n\n","\n\n\n",$base),'remove blank line'=>str_replace("\n\n","\n",$base),'change indentation'=>str_replace('  indented',"\tindented",$base),'change text'=>str_replace('Café','Café edited',$base),'append newline'=>$base."\n"];
    $cases=[];$order=2;
    foreach ([true,false] as $same) foreach (($same?$unchanged:$edits) as $name=>$value) {
        $original=$same?$value:$base;
        $lesson=$courses['sandbox']->lessons()->create(['title'=>$name,'lesson_type'=>'text','content'=>$original,'lesson_order'=>$order++,'is_published'=>true]);
        $section=$lesson->sections()->create(['type'=>'text','body_format'=>'plain','body'=>$original,'position'=>0]);
        $cases[]=['name'=>$name,'lesson'=>$lesson->id,'section'=>$section->id,'original'=>$original,'unchanged'=>$same,'edited'=>$same?null:$value];
    }
    $ql=$courses['regression']->lessons()->create(['title'=>'Browser quiz','lesson_type'=>'quiz','lesson_order'=>1,'is_published'=>true]);
    $quiz=Quiz::create(['course_id'=>$courses['regression']->id,'lesson_id'=>$ql->id,'title'=>'Browser quiz','passing_score'=>75,'is_published'=>true]);
    $question=$quiz->questions()->create(['question'=>'Choose the correct synthetic answer.','option_a'=>'Correct','option_b'=>'Other','option_c'=>'Other','option_d'=>'Other','correct_answer'=>'A','points'=>1]);
    $al=$courses['regression']->lessons()->create(['title'=>'Browser activity','lesson_type'=>'activity','content'=>'Explain a test concept.','lesson_order'=>2,'is_published'=>true]);
    $activity=Assignment::create(['course_id'=>$courses['regression']->id,'lesson_id'=>$al->id,'title'=>'Browser activity','description'=>'Explain a test concept.','submission_type'=>'essay','answer_format'=>'text','max_score'=>100,'is_activity'=>true,'is_published'=>true,'ai_enabled'=>false]);
    $pl=$courses['regression']->lessons()->create(['title'=>'Preserved progress','lesson_type'=>'text','content'=>'Preserve this progress','lesson_order'=>3,'is_published'=>true]);
    $progress=LessonProgress::create(['student_id'=>$users['student']->id,'lesson_id'=>$pl->id,'status'=>'completed','completed_at'=>now()]);
    $fixtures=['id'=>$suffix,'category'=>$category->id,'users'=>array_map(fn($u)=>['id'=>$u->id,'email'=>$u->email],$users),'courses'=>array_map(fn($c)=>$c->id,$courses),'templates'=>CourseTemplate::pluck('id','name')->all(),'legacy'=>['id'=>$legacy->id,'section'=>$legacySection->id,'body'=>$legacyBody],'cases'=>$cases,'quiz'=>['id'=>$quiz->id,'lesson'=>$ql->id,'question'=>$question->id],'activity'=>['id'=>$activity->id,'lesson'=>$al->id],'protectedProgress'=>(array)DB::table('lesson_progress')->find($progress->id)];
    file_put_contents($runtime.'/fixtures.json',json_encode($fixtures,JSON_PRETTY_PRINT));echo json_encode($fixtures);return;
}
$fixtures=json_decode(file_get_contents($runtime.'/fixtures.json'),true,flags:JSON_THROW_ON_ERROR);
if ($action === 'enroll') {
    $course=Course::where('teacher_id',$fixtures['users']['teacher']['id'])->findOrFail((int)($argv[2]??0));
    Enrollment::firstOrCreate(['student_id'=>$fixtures['users']['student']['id'],'course_id'=>$course->id],['status'=>'active','enrolled_at'=>now()]);
} elseif ($action === 'review-fixtures') {
    require __DIR__.'/course-under-review-fixtures.php';
} elseif ($action !== 'snapshot') throw new RuntimeException('Unsupported browser fixture operation.');
$state=[];
foreach (['courses','lessons','lesson_sections','enrollments','lesson_progress','quizzes','quiz_questions','quiz_results','assignments','submissions'] as $table) $state[$table]=DB::table($table)->orderBy('id')->get();
$state['integrity']=DB::select('PRAGMA integrity_check');echo json_encode($state,JSON_INVALID_UTF8_SUBSTITUTE);
