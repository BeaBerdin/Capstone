@php
    $activityAssignment ??= null;
@endphp

<section id="activitySection" class="sad-card pw-card hidden p-5 sm:p-7">
    <div class="flex items-start gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
        </span>
        <div>
            <h2 class="text-lg font-bold text-slate-900">Activity instructions</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">Describe what students should do. Their work will appear in your existing assignment submissions for review.</p>
        </div>
    </div>

    <div class="mt-5">
        <label for="activityInstructions" class="text-sm font-semibold text-slate-800">Instructions <span class="text-rose-600">*</span></label>
        <textarea id="activityInstructions" data-content-input rows="7" maxlength="30000" placeholder="Explain the task, expected work, and any requirements…" class="pw-field mt-2 resize-y px-4 py-3 leading-6">{{ old('content', $lesson->content ?? '') }}</textarea>
        @error('content')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="activity_answer_format" class="text-sm font-semibold text-slate-800">How students may submit</label>
            <select id="activity_answer_format" name="activity_answer_format" class="pw-field mt-2 h-11 px-3">
                <option value="either" @selected(old('activity_answer_format', $activityAssignment?->answer_format ?? 'either') === 'either')>Typed text or file upload</option>
                <option value="text" @selected(old('activity_answer_format', $activityAssignment?->answer_format) === 'text')>Typed text only</option>
                <option value="file" @selected(old('activity_answer_format', $activityAssignment?->answer_format) === 'file')>File upload only</option>
            </select>
        </div>
        <div>
            <label for="activity_max_score" class="text-sm font-semibold text-slate-800">Maximum score</label>
            <input id="activity_max_score" type="number" name="activity_max_score" min="1" max="10000" value="{{ old('activity_max_score', $activityAssignment?->max_score ?? 100) }}" class="pw-field mt-2 h-11 px-3">
        </div>
        <div>
            <label class="flex h-full cursor-pointer items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/70 p-4">
                <input type="checkbox" name="activity_ai_enabled" value="1" @checked(old('activity_ai_enabled', $activityAssignment?->ai_evaluation_enabled ?? false)) class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                <span>
                    <span class="block text-sm font-bold text-slate-900">Optional Gemini suggestions</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-600">Gemini can suggest a score and feedback. You review and assign the final grade.</span>
                </span>
            </label>
        </div>
        <div class="sm:col-span-2">
            <label for="activity_criteria" class="text-sm font-semibold text-slate-800">Evaluation criteria <span class="font-normal text-slate-500">(optional, helps AI suggestions)</span></label>
            <textarea id="activity_criteria" name="activity_criteria" rows="4" maxlength="10000" placeholder="Describe the rubric or qualities to look for…" class="pw-field mt-2 resize-y px-3 py-3 leading-6">{{ old('activity_criteria', $activityAssignment?->evaluation_criteria) }}</textarea>
        </div>
    </div>
</section>
