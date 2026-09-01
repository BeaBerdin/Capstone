<x-layouts::app :title="__('Record Quiz Result')">
<div class="min-h-screen bg-slate-50/80 pb-12 text-slate-900">
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <a href="{{ route('teacher.quiz-results.index') }}" class="text-sm font-semibold text-slate-500 hover:text-violet-700">← Back to Quiz Results</a>
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-violet-600">Assessment Records</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Record quiz result</h1>
            <p class="mt-2 text-sm text-slate-500">Manually add a result for a student enrolled in one of your courses.</p>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><p class="font-bold">Please check the form.</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('teacher.quiz-results.store') }}" method="POST" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="text-sm font-bold text-slate-800">Student</label><select name="student_id" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"><option value="">Choose student</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected((string)old('student_id')===(string)$student->id)>{{ $student->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-bold text-slate-800">Quiz</label><select name="quiz_id" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"><option value="">Choose quiz</option>@foreach($quizzes as $quiz)<option value="{{ $quiz->id }}" @selected((string)old('quiz_id')===(string)$quiz->id)>{{ $quiz->title }}{{ $quiz->course ? ' — '.$quiz->course->title : '' }}</option>@endforeach</select></div>
                <div><label class="text-sm font-bold text-slate-800">Score</label><input type="number" name="score" min="0" value="{{ old('score') }}" required placeholder="8" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"></div>
                <div><label class="text-sm font-bold text-slate-800">Total items</label><input type="number" name="total_items" min="1" value="{{ old('total_items') }}" required placeholder="10" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"></div>
                <div><label class="text-sm font-bold text-slate-800">Attempt number</label><input type="number" name="attempt_number" min="1" value="{{ old('attempt_number',1) }}" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-violet-400 focus:ring-4 focus:ring-violet-100"></div>
            </div>
            <div class="mt-6 rounded-xl border border-violet-100 bg-violet-50 p-4 text-sm leading-6 text-violet-800">PathWise calculates the percentage automatically and uses the selected quiz's configured passing score.</div>
            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><a href="{{ route('teacher.quiz-results.index') }}" class="inline-flex h-11 items-center rounded-xl border border-slate-200 px-5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a><button class="h-11 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white hover:bg-violet-700">Save Result</button></div>
        </form>
    </div>
</div>
</x-layouts::app>
