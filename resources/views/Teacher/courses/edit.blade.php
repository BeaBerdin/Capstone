<x-layouts::app :title="'Edit Course'">
@include('partials.soft3d')

{{--
    Teacher - Edit Course
    Manuscript Figure 25 (Manage Courses and Lessons) and Figure 28
    (revise a course that was returned for revision, then resubmit it).
    Controller: CourseController@teacherEditCourse / teacherUpdateCourse
    Layout follows the PathWise forms reference (see Teacher.assignments.create).
--}}

@php
    $field = 'mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100';
@endphp

<div class="sad-page min-h-screen">
    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mx-auto max-w-6xl">

            <x-pw.form-header
                :crumbs="['My Courses' => route('teacher.my-courses'), $course->title => null, 'Edit Course' => null]"
                title="Edit Course Details"
                :back-url="route('teacher.my-courses')"
                back-label="Back to My Courses"
            >
                After saving, use <em>Submit for Approval</em> on My Courses to send
                <span class="font-semibold text-violet-700">{{ $course->title }}</span> to the Administrator.
            </x-pw.form-header>

            @if($course->template)
                <p class="mt-4 inline-block rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                    Created from template: {{ $course->template->name }}
                </p>
            @endif

            {{-- Administrator feedback for returned courses --}}
            @if($course->status === 'rejected' && filled($course->review_remarks))
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">
                        Administrator feedback
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm text-amber-800">{{ $course->review_remarks }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_300px] lg:items-start">

                <form
                    action="{{ route('teacher.courses.update', $course) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="sad-card overflow-hidden"
                >
                    @csrf
                    @method('PUT')

                    <x-pw.form-errors />

                    {{-- ---------- Course information ---------- --}}
                    <x-pw.form-section title="Course Information" first>
                        <x-slot:icon>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"></path><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"></path></svg>
                        </x-slot:icon>

                        {{-- Title --}}
                        <div>
                            <label for="title" class="text-sm font-semibold text-slate-700">
                                Course title <span class="text-rose-500">*</span>
                            </label>
                            <input id="title" name="title" type="text" maxlength="255" required
                                   value="{{ old('title', $course->title) }}"
                                   class="{{ $field }}">
                            @error('title') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category_id" class="text-sm font-semibold text-slate-700">
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <select id="category_id" name="category_id" required class="{{ $field }}">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @selected((string) old('category_id', $course->category_id) === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="text-sm font-semibold text-slate-700">
                                Description <span class="text-rose-500">*</span>
                            </label>
                            <textarea id="description" name="description" rows="5" required
                                      class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-violet-300 focus:ring-4 focus:ring-violet-100">{{ old('description', $course->description) }}</textarea>
                            @error('description')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-slate-500">Shown on the marketplace and the course page.</p>
                            @enderror
                        </div>
                    </x-pw.form-section>

                    {{-- ---------- Level, duration and price ---------- --}}
                    <x-pw.form-section title="Level, Duration & Price">
                        <x-slot:icon>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"></path><path d="m7 15 4-4 3 3 5-6"></path></svg>
                        </x-slot:icon>

                        {{-- Difficulty --}}
                        <div>
                            <span class="text-sm font-semibold text-slate-700">
                                Difficulty level <span class="text-rose-500">*</span>
                            </span>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 has-[:checked]:border-violet-400 has-[:checked]:bg-violet-50">
                                        <input type="radio" name="difficulty_level" value="{{ $value }}"
                                               class="text-violet-600 focus:ring-violet-500"
                                               @checked(old('difficulty_level', $course->difficulty_level) === $value)>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('difficulty_level') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            {{-- Estimated hours --}}
                            <div>
                                <label for="estimated_hours" class="text-sm font-semibold text-slate-700">Estimated hours</label>
                                <input id="estimated_hours" name="estimated_hours" type="number" min="1"
                                       value="{{ old('estimated_hours', $course->estimated_hours) }}"
                                       class="{{ $field }}">
                                @error('estimated_hours') <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Price --}}
                            <div>
                                <label for="price" class="text-sm font-semibold text-slate-700">Price (PHP)</label>
                                <input id="price" name="price" type="number" min="0" step="0.01"
                                       value="{{ old('price', $course->price) }}"
                                       class="{{ $field }}">
                                @error('price')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @else
                                    <p class="mt-1.5 text-xs text-slate-500">Enter 0 to make this course free.</p>
                                @enderror
                            </div>
                        </div>
                    </x-pw.form-section>

                    {{-- ---------- Media and completion ---------- --}}
                    <x-pw.form-section title="Media & Completion">
                        <x-slot:icon>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="m10 9 5 3-5 3z"></path></svg>
                        </x-slot:icon>

                        {{-- Intro video --}}
                        <div>
                            <label for="intro_video" class="text-sm font-semibold text-slate-700">Introduction video URL</label>
                            <input id="intro_video" name="intro_video" type="url" maxlength="500"
                                   value="{{ old('intro_video', $course->intro_video) }}"
                                   placeholder="https://youtube.com/watch?v=..."
                                   class="{{ $field }}">
                            @error('intro_video')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-slate-500">Optional. A YouTube or other public video link.</p>
                            @enderror
                        </div>

                        {{-- Thumbnail --}}
                        <div>
                            <label for="thumbnail" class="text-sm font-semibold text-slate-700">Thumbnail</label>

                            @if($course->thumbnail)
                                <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="Current thumbnail"
                                     class="mt-2 h-28 w-48 rounded-xl border border-slate-200 object-cover">
                            @endif

                            <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp"
                                   class="mt-2 block w-full rounded-xl border border-dashed border-violet-200 bg-violet-50/40 p-3 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-violet-700">
                            @error('thumbnail')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-slate-500">Optional. JPG, PNG or WEBP; replaces the current image.</p>
                            @enderror
                        </div>

                        {{-- Certificate --}}
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 px-4 py-3">
                            <input type="hidden" name="certificate_available" value="0">
                            <input type="checkbox" name="certificate_available" value="1"
                                   class="mt-1 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                   @checked(old('certificate_available', $course->certificate_available))>
                            <span>
                                <span class="block text-sm font-semibold text-slate-700">Completion certificate</span>
                                <span class="mt-0.5 block text-xs text-slate-500">
                                    Issue a certificate when a student completes this course.
                                </span>
                            </span>
                        </label>
                    </x-pw.form-section>

                    <x-pw.form-footer submit="Save Changes" :cancel-url="route('teacher.my-courses')">
                        <x-slot:icon>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"></path></svg>
                        </x-slot:icon>
                    </x-pw.form-footer>
                </form>

                <x-pw.form-aside
                    title="Course Guidelines"
                    :tips="[
                        'Use a clear title and description: students see them on the marketplace.',
                        'Lessons, quizzes and assignments are managed from the course\'s Lessons page.',
                        'After saving, submit the course for approval on My Courses.',
                        'While a course is pending approval it is locked and cannot be edited.',
                    ]"
                />

            </div>

        </div>
    </main>
</div>

</x-layouts::app>
