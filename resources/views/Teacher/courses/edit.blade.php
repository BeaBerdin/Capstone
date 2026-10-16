<x-layouts::app :title="'Edit Course'">

{{--
    Teacher - Edit Course
    Manuscript Figure 25 (Manage Courses and Lessons) and Figure 28
    (revise a course that was returned for revision, then resubmit it).
    Controller: CourseController@teacherEditCourse / teacherUpdateCourse
--}}

<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-3xl">

            <a href="{{ route('teacher.my-courses') }}"
               class="text-xs font-semibold text-violet-600 hover:text-violet-700">
                &larr; Back to My Courses
            </a>

            <p class="mt-4 text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                Teacher
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                Edit Course Details
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Update the details of <strong>{{ $course->title }}</strong>.
                After saving, use <em>Submit for Approval</em> on My Courses to send it to the Administrator.
            </p>

            @if($course->template)
                <p class="mt-3 inline-block rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">
                    Created from template: {{ $course->template->name }}
                </p>
            @endif

            {{-- Administrator feedback for returned courses --}}
            @if($course->status === 'rejected' && filled($course->review_remarks))
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">
                        Administrator feedback
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm text-amber-800">{{ $course->review_remarks }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    Please correct the highlighted fields below.
                </div>
            @endif

            <form
                action="{{ route('teacher.courses.update', $course) }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                @csrf
                @method('PUT')

                {{-- Title --}}
                <div>
                    <label for="title" class="text-sm font-semibold text-slate-700">Course title</label>
                    <input id="title" name="title" type="text" maxlength="255" required
                           value="{{ old('title', $course->title) }}"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label for="category_id" class="text-sm font-semibold text-slate-700">Category</label>
                    <select id="category_id" name="category_id" required
                            class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected((string) old('category_id', $course->category_id) === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="text-sm font-semibold text-slate-700">Description</label>
                    <textarea id="description" name="description" rows="5" required
                              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100">{{ old('description', $course->description) }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Difficulty --}}
                <div>
                    <span class="text-sm font-semibold text-slate-700">Difficulty level</span>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm has-[:checked]:border-violet-400 has-[:checked]:bg-violet-50">
                                <input type="radio" name="difficulty_level" value="{{ $value }}"
                                       @checked(old('difficulty_level', $course->difficulty_level) === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('difficulty_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Estimated hours --}}
                    <div>
                        <label for="estimated_hours" class="text-sm font-semibold text-slate-700">Estimated hours</label>
                        <input id="estimated_hours" name="estimated_hours" type="number" min="1"
                               value="{{ old('estimated_hours', $course->estimated_hours) }}"
                               class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                        @error('estimated_hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Price --}}
                    <div>
                        <label for="price" class="text-sm font-semibold text-slate-700">Price (PHP, 0 = free)</label>
                        <input id="price" name="price" type="number" min="0" step="0.01"
                               value="{{ old('price', $course->price) }}"
                               class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                        @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Intro video --}}
                <div>
                    <label for="intro_video" class="text-sm font-semibold text-slate-700">Introduction video URL (optional)</label>
                    <input id="intro_video" name="intro_video" type="url" maxlength="500"
                           value="{{ old('intro_video', $course->intro_video) }}"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-100">
                    @error('intro_video') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Thumbnail --}}
                <div>
                    <label for="thumbnail" class="text-sm font-semibold text-slate-700">Thumbnail (optional, replaces the current image)</label>

                    @if($course->thumbnail)
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="Current thumbnail"
                             class="mt-2 h-28 w-48 rounded-xl border border-slate-200 object-cover">
                    @endif

                    <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp"
                           class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-violet-700">
                    @error('thumbnail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Certificate --}}
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                    <input type="hidden" name="certificate_available" value="0">
                    <input type="checkbox" name="certificate_available" value="1"
                           @checked(old('certificate_available', $course->certificate_available))>
                    Issue a certificate when a student completes this course
                </label>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                    <a href="{{ route('teacher.my-courses') }}"
                       class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-xl bg-violet-600 px-5 py-2 text-sm font-bold text-white hover:bg-violet-700">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>

    </main>

</div>

</x-layouts::app>
