<x-layouts::app :title="'Edit Lesson'">

@php
    $currentType = old('lesson_type', $lesson->lesson_type ?? 'video');
@endphp

<style>
    .pw-card {
        background: #ffffff;
        border: 1px solid #e7e9ef;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .035);
    }

    .pw-field {
        width: 100%;
        border: 1px solid #dfe2e9;
        border-radius: 12px;
        background: #ffffff;
        color: #172033;
        font-size: 14px;
        transition: all 160ms ease;
    }

    .pw-field:focus {
        outline: none !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, .10) !important;
    }

    .pw-type-card input:checked + div {
        border-color: #7c3aed;
        background: #f5f3ff;
        box-shadow: 0 0 0 2px rgba(124, 58, 237, .08);
    }

    .pw-type-card input:checked + div .type-icon {
        background: #7c3aed;
        color: #ffffff;
    }
</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1400px]">


            {{-- =====================================================
                BREADCRUMB
            ====================================================== --}}

            <div
                class="mb-5 flex flex-wrap items-center
                       gap-2 text-xs text-slate-400"
            >

                <a
                    href="{{ route('teacher.my-courses') }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    My Courses
                </a>

                <span>›</span>

                <a
                    href="{{ route('teacher.lessons', $lesson->course) }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    {{ $lesson->course->title }}
                </a>

                <span>›</span>

                <span class="font-semibold text-slate-600">
                    Edit Lesson
                </span>

            </div>



            {{-- =====================================================
                HEADER
            ====================================================== --}}

            <div
                class="mb-7 flex flex-col gap-5
                       lg:flex-row lg:items-end lg:justify-between"
            >

                <div>

                    <p
                        class="text-xs font-bold uppercase
                               tracking-[.12em] text-violet-600"
                    >
                        Course Builder
                    </p>

                    <h1
                        class="mt-2 text-3xl font-bold
                               tracking-tight text-slate-950"
                    >
                        Edit lesson
                    </h1>

                    <p
                        class="mt-2 max-w-2xl text-sm
                               leading-6 text-slate-500"
                    >
                        Update this lesson in

                        <span class="font-semibold text-slate-700">
                            {{ $lesson->course->title }}
                        </span>.
                    </p>

                </div>


                <span
                    class="inline-flex self-start items-center
                           gap-2 rounded-full px-3 py-1.5
                           text-xs font-semibold
                           {{
                               $lesson->is_published
                               ? 'bg-emerald-50 text-emerald-700'
                               : 'bg-slate-100 text-slate-600'
                           }}"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full
                               {{
                                   $lesson->is_published
                                   ? 'bg-emerald-500'
                                   : 'bg-slate-400'
                               }}"
                    ></span>

                    {{
                        $lesson->is_published
                        ? 'Published'
                        : 'Draft'
                    }}
                </span>

            </div>



            {{-- =====================================================
                ERRORS
            ====================================================== --}}

            @if($errors->any())

                <div
                    class="mb-6 rounded-2xl
                           border border-red-200
                           bg-red-50 p-5"
                >

                    <div class="flex gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0
                                   items-center justify-center
                                   rounded-full bg-red-100
                                   text-red-600"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v4"></path>
                                <path d="M12 16h.01"></path>
                            </svg>
                        </div>


                        <div>

                            <p class="text-sm font-bold text-red-800">
                                Please check the lesson information.
                            </p>

                            <ul
                                class="mt-2 list-disc space-y-1
                                       pl-5 text-xs text-red-700"
                            >

                                @foreach($errors->all() as $error)

                                    <li>
                                        {{ $error }}
                                    </li>

                                @endforeach

                            </ul>

                        </div>

                    </div>

                </div>

            @endif



            {{-- =====================================================
                FORM
            ====================================================== --}}

            <form
                action="{{ route('teacher.lessons.update', $lesson) }}"
                method="POST"
                enctype="multipart/form-data"
                id="editLessonForm"
            >

                @csrf
                @method('PUT')


                <div
                    class="grid grid-cols-1 gap-6
                           xl:grid-cols-[minmax(0,1fr)_340px]"
                >


                    {{-- =================================================
                        LEFT SIDE
                    ================================================== --}}

                    <div class="space-y-6">


                        {{-- =============================================
                            LESSON TYPE
                        ============================================== --}}

                        <section class="pw-card p-5 sm:p-7">

                            <h2 class="text-lg font-bold text-slate-900">
                                Lesson type
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Change the format of this lesson if needed.
                            </p>


                            <div
                                class="mt-6 grid grid-cols-2
                                       gap-3 lg:grid-cols-4"
                            >


                                {{-- VIDEO --}}
                                <label class="pw-type-card cursor-pointer">

                                    <input
                                        type="radio"
                                        name="lesson_type"
                                        value="video"
                                        class="sr-only"
                                        @checked($currentType === 'video')
                                    >

                                    <div
                                        class="h-full rounded-2xl
                                               border border-slate-200
                                               p-4 transition
                                               hover:border-violet-200"
                                    >

                                        <div
                                            class="type-icon flex h-11 w-11
                                                   items-center justify-center
                                                   rounded-xl bg-violet-50
                                                   text-violet-600 transition"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                            >
                                                <path d="M8 5v14l11-7z"></path>
                                            </svg>
                                        </div>

                                        <p
                                            class="mt-3 text-sm
                                                   font-bold text-slate-800"
                                        >
                                            Video
                                        </p>

                                        <p
                                            class="mt-1 text-[11px]
                                                   leading-4 text-slate-400"
                                        >
                                            YouTube or online video.
                                        </p>

                                    </div>

                                </label>



                                {{-- READING --}}
                                <label class="pw-type-card cursor-pointer">

                                    <input
                                        type="radio"
                                        name="lesson_type"
                                        value="text"
                                        class="sr-only"
                                        @checked($currentType === 'text')
                                    >

                                    <div
                                        class="h-full rounded-2xl
                                               border border-slate-200
                                               p-4 transition
                                               hover:border-blue-200"
                                    >

                                        <div
                                            class="type-icon flex h-11 w-11
                                                   items-center justify-center
                                                   rounded-xl bg-blue-50
                                                   text-blue-600 transition"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M4 19.5A2.5 2.5
                                                       0 016.5 17H20"
                                                ></path>

                                                <path
                                                    d="M6.5 2H20v20H6.5
                                                       A2.5 2.5 0 014
                                                       19.5v-15A2.5 2.5
                                                       0 016.5 2z"
                                                ></path>
                                            </svg>
                                        </div>

                                        <p
                                            class="mt-3 text-sm
                                                   font-bold text-slate-800"
                                        >
                                            Reading
                                        </p>

                                        <p
                                            class="mt-1 text-[11px]
                                                   leading-4 text-slate-400"
                                        >
                                            Written learning content.
                                        </p>

                                    </div>

                                </label>



                                {{-- DOCUMENT --}}
                                <label class="pw-type-card cursor-pointer">

                                    <input
                                        type="radio"
                                        name="lesson_type"
                                        value="document"
                                        class="sr-only"
                                        @checked($currentType === 'document')
                                    >

                                    <div
                                        class="h-full rounded-2xl
                                               border border-slate-200
                                               p-4 transition
                                               hover:border-teal-200"
                                    >

                                        <div
                                            class="type-icon flex h-11 w-11
                                                   items-center justify-center
                                                   rounded-xl bg-teal-50
                                                   text-teal-600 transition"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M6 2h8l4 4v16H6z"></path>
                                                <path d="M14 2v5h5"></path>
                                            </svg>
                                        </div>

                                        <p
                                            class="mt-3 text-sm
                                                   font-bold text-slate-800"
                                        >
                                            Document
                                        </p>

                                        <p
                                            class="mt-1 text-[11px]
                                                   leading-4 text-slate-400"
                                        >
                                            PDF, Word or presentation.
                                        </p>

                                    </div>

                                </label>



                                {{-- QUIZ --}}
                                <label class="pw-type-card cursor-pointer">

                                    <input
                                        type="radio"
                                        name="lesson_type"
                                        value="quiz"
                                        class="sr-only"
                                        @checked($currentType === 'quiz')
                                    >

                                    <div
                                        class="h-full rounded-2xl
                                               border border-slate-200
                                               p-4 transition
                                               hover:border-orange-200"
                                    >

                                        <div
                                            class="type-icon flex h-11 w-11
                                                   items-center justify-center
                                                   rounded-xl bg-orange-50
                                                   text-orange-500 transition"
                                        >
                                            <span class="text-xl font-black">
                                                ?
                                            </span>
                                        </div>

                                        <p
                                            class="mt-3 text-sm
                                                   font-bold text-slate-800"
                                        >
                                            Quiz
                                        </p>

                                        <p
                                            class="mt-1 text-[11px]
                                                   leading-4 text-slate-400"
                                        >
                                            Assessment lesson.
                                        </p>

                                    </div>

                                </label>

                            </div>

                        </section>



                        {{-- =============================================
                            BASIC INFORMATION
                        ============================================== --}}

                        <section class="pw-card p-5 sm:p-7">

                            <div
                                class="border-b border-slate-100
                                       pb-5"
                            >
                                <h2 class="text-lg font-bold text-slate-900">
                                    Lesson information
                                </h2>

                                <p class="mt-1 text-sm text-slate-500">
                                    Update the lesson title, order,
                                    and estimated study time.
                                </p>
                            </div>


                            <div class="mt-6 space-y-6">


                                {{-- TITLE --}}
                                <div>

                                    <label
                                        for="title"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Lesson title

                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="title"
                                        name="title"
                                        required
                                        maxlength="255"
                                        value="{{ old('title', $lesson->title) }}"
                                        placeholder="Lesson title"
                                        class="pw-field mt-2.5 h-12 px-4"
                                    >

                                    @error('title')

                                        <p class="mt-2 text-xs text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>



                                <div
                                    class="grid grid-cols-1
                                           gap-5 sm:grid-cols-2"
                                >


                                    {{-- ORDER --}}
                                    <div>

                                        <label
                                            for="lesson_order"
                                            class="text-sm font-semibold
                                                   text-slate-800"
                                        >
                                            Lesson order
                                        </label>

                                        <input
                                            type="number"
                                            id="lesson_order"
                                            name="lesson_order"
                                            min="1"
                                            required
                                            value="{{ old('lesson_order', $lesson->lesson_order) }}"
                                            class="pw-field mt-2.5 h-12 px-4"
                                        >

                                        <p
                                            class="mt-1.5 text-[11px]
                                                   text-slate-400"
                                        >
                                            Controls the lesson's position.
                                        </p>

                                    </div>



                                    {{-- DURATION --}}
                                    <div>

                                        <label
                                            for="duration_minutes"
                                            class="text-sm font-semibold
                                                   text-slate-800"
                                        >
                                            Estimated duration
                                        </label>

                                        <div class="relative mt-2.5">

                                            <input
                                                type="number"
                                                id="duration_minutes"
                                                name="duration_minutes"
                                                min="1"
                                                value="{{ old('duration_minutes', $lesson->duration_minutes) }}"
                                                placeholder="15"
                                                class="pw-field h-12
                                                       px-4 pr-20"
                                            >

                                            <span
                                                class="pointer-events-none
                                                       absolute right-4
                                                       top-1/2
                                                       -translate-y-1/2
                                                       text-xs text-slate-400"
                                            >
                                                minutes
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </section>



                        {{-- =============================================
                            VIDEO CONTENT
                        ============================================== --}}

                        <section
                            id="videoSection"
                            class="pw-card hidden p-5 sm:p-7"
                        >

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-11 w-11 shrink-0
                                           items-center justify-center
                                           rounded-xl bg-violet-50
                                           text-violet-600"
                                >
                                    ▶
                                </div>


                                <div>

                                    <h2 class="text-lg font-bold text-slate-900">
                                        Video content
                                    </h2>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Update the video and description.
                                    </p>

                                </div>

                            </div>



                            <div class="mt-6 space-y-5">

                                <div>

                                    <label
                                        for="video_url"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Video URL
                                    </label>

                                    <input
                                        type="url"
                                        id="video_url"
                                        name="video_url"
                                        value="{{ old('video_url', $lesson->video_url) }}"
                                        placeholder="https://www.youtube.com/watch?v=..."
                                        class="pw-field mt-2.5 h-12 px-4"
                                    >

                                    @error('video_url')

                                        <p class="mt-2 text-xs text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>


                                <div>

                                    <label
                                        for="videoDescription"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Lesson description
                                    </label>

                                    <textarea
                                        id="videoDescription"
                                        data-content-input
                                        rows="5"
                                        placeholder="Explain what students should learn from this video..."
                                        class="pw-field mt-2.5
                                               resize-none px-4 py-3
                                               leading-6"
                                    >{{ old('content', $lesson->content) }}</textarea>

                                </div>

                            </div>

                        </section>



                        {{-- =============================================
                            READING CONTENT
                        ============================================== --}}

                        <section
                            id="textSection"
                            class="pw-card hidden p-5 sm:p-7"
                        >

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-11 w-11 shrink-0
                                           items-center justify-center
                                           rounded-xl bg-blue-50
                                           text-blue-600"
                                >
                                    📖
                                </div>


                                <div>

                                    <h2 class="text-lg font-bold text-slate-900">
                                        Reading content
                                    </h2>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Edit the material students
                                        will read directly in PathWise.
                                    </p>

                                </div>

                            </div>


                            <textarea
                                id="readingContent"
                                data-content-input
                                rows="14"
                                placeholder="Write your lesson content here..."
                                class="pw-field mt-6 resize-y
                                       px-4 py-4 leading-7"
                            >{{ old('content', $lesson->content) }}</textarea>

                        </section>



                        {{-- =============================================
                            DOCUMENT CONTENT
                        ============================================== --}}

                        <section
                            id="documentSection"
                            class="pw-card hidden p-5 sm:p-7"
                        >

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-11 w-11 shrink-0
                                           items-center justify-center
                                           rounded-xl bg-teal-50
                                           text-teal-600"
                                >
                                    📄
                                </div>


                                <div>

                                    <h2 class="text-lg font-bold text-slate-900">
                                        Document material
                                    </h2>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Keep the current file or upload
                                        a replacement.
                                    </p>

                                </div>

                            </div>



                            {{-- CURRENT FILE --}}
                            @if($lesson->file_path)

                                <div
                                    class="mt-6 flex flex-col gap-4
                                           rounded-2xl border
                                           border-teal-100
                                           bg-teal-50/60 p-4
                                           sm:flex-row
                                           sm:items-center
                                           sm:justify-between"
                                >

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div
                                            class="flex h-11 w-11 shrink-0
                                                   items-center justify-center
                                                   rounded-xl bg-white
                                                   text-teal-600 shadow-sm"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path d="M6 2h8l4 4v16H6z"></path>
                                                <path d="M14 2v5h5"></path>
                                            </svg>
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="text-xs font-semibold
                                                       text-teal-700"
                                            >
                                                Current document
                                            </p>

                                            <p
                                                class="mt-1 truncate
                                                       text-sm font-bold
                                                       text-slate-800"
                                            >
                                                {{ basename($lesson->file_path) }}
                                            </p>

                                        </div>

                                    </div>


                                    <a
                                        href="{{ asset('storage/' . $lesson->file_path) }}"
                                        target="_blank"
                                        class="inline-flex h-9
                                               items-center justify-center
                                               rounded-lg border
                                               border-teal-200
                                               bg-white px-3
                                               text-xs font-semibold
                                               text-teal-700
                                               transition
                                               hover:bg-teal-50"
                                    >
                                        Open File
                                    </a>

                                </div>

                            @endif



                            {{-- REPLACE FILE --}}
                            <div class="mt-5">

                                <input
                                    type="file"
                                    name="lesson_file"
                                    id="lesson_file"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                                    class="hidden"
                                >


                                <label
                                    for="lesson_file"
                                    class="flex min-h-[180px]
                                           cursor-pointer items-center
                                           justify-center rounded-2xl
                                           border-2 border-dashed
                                           border-slate-200
                                           bg-slate-50 px-6 py-8
                                           text-center transition
                                           hover:border-teal-300
                                           hover:bg-teal-50/30"
                                >

                                    <div>

                                        <div
                                            class="mx-auto flex h-14 w-14
                                                   items-center justify-center
                                                   rounded-xl bg-white
                                                   text-2xl text-teal-600
                                                   shadow-sm"
                                        >
                                            ↑
                                        </div>

                                        <p
                                            id="documentFileName"
                                            class="mt-4 text-sm
                                                   font-bold text-slate-800"
                                        >
                                            {{
                                                $lesson->file_path
                                                ? 'Replace document'
                                                : 'Upload lesson document'
                                            }}
                                        </p>

                                        <p
                                            id="documentHelp"
                                            class="mt-1 text-xs
                                                   text-slate-500"
                                        >
                                            {{
                                                $lesson->file_path
                                                ? 'Leave empty to keep the current file'
                                                : 'Click to choose a file'
                                            }}
                                        </p>

                                        <p
                                            class="mt-3 text-[11px]
                                                   text-slate-400"
                                        >
                                            PDF, Word, PowerPoint,
                                            Excel or TXT · Max 20 MB
                                        </p>

                                    </div>

                                </label>


                                @error('lesson_file')

                                    <p class="mt-2 text-xs text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>



                            <div class="mt-5">

                                <label
                                    for="documentDescription"
                                    class="text-sm font-semibold
                                           text-slate-800"
                                >
                                    Document description
                                </label>

                                <textarea
                                    id="documentDescription"
                                    data-content-input
                                    rows="5"
                                    placeholder="Explain what this document contains..."
                                    class="pw-field mt-2.5
                                           resize-none px-4 py-3
                                           leading-6"
                                >{{ old('content', $lesson->content) }}</textarea>

                            </div>

                        </section>



                        {{-- =============================================
                            QUIZ CONTENT
                        ============================================== --}}

                        <section
                            id="quizSection"
                            class="pw-card hidden p-5 sm:p-7"
                        >

                            <div
                                class="rounded-2xl
                                       border border-orange-100
                                       bg-orange-50 p-5"
                            >

                                <div class="flex gap-4">

                                    <div
                                        class="flex h-11 w-11 shrink-0
                                               items-center justify-center
                                               rounded-xl bg-white
                                               text-xl font-black
                                               text-orange-500
                                               shadow-sm"
                                    >
                                        ?
                                    </div>


                                    <div>

                                        <h2
                                            class="text-base font-bold
                                                   text-slate-900"
                                        >
                                            Quiz lesson
                                        </h2>

                                        <p
                                            class="mt-1 text-sm
                                                   leading-6
                                                   text-slate-600"
                                        >
                                            Update the instructions
                                            for this assessment lesson.
                                            The actual questions will
                                            be managed in the Quiz Builder.
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="mt-5">

                                <label
                                    for="quizDescription"
                                    class="text-sm font-semibold
                                           text-slate-800"
                                >
                                    Quiz instructions
                                </label>

                                <textarea
                                    id="quizDescription"
                                    data-content-input
                                    rows="5"
                                    placeholder="Example: Complete this quiz after reviewing the previous lessons."
                                    class="pw-field mt-2.5
                                           resize-none px-4 py-3
                                           leading-6"
                                >{{ old('content', $lesson->content) }}</textarea>

                            </div>

                        </section>



                        {{-- REAL CONTENT VALUE --}}
                        <textarea
                            name="content"
                            id="content"
                            class="hidden"
                        >{{ old('content', $lesson->content) }}</textarea>



                        {{-- =============================================
                            ACCESS SETTINGS
                        ============================================== --}}

                        <section class="pw-card p-5 sm:p-7">

                            <h2 class="text-lg font-bold text-slate-900">
                                Lesson access
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Update the publication and preview settings.
                            </p>



                            <div class="mt-5 space-y-3">


                                {{-- PUBLISH --}}
                                <label
                                    class="flex cursor-pointer
                                           items-start justify-between
                                           gap-5 rounded-xl
                                           border border-slate-200
                                           bg-slate-50 p-4"
                                >

                                    <div>

                                        <p
                                            class="text-sm font-bold
                                                   text-slate-800"
                                        >
                                            Publish lesson
                                        </p>

                                        <p
                                            class="mt-1 text-xs
                                                   leading-5 text-slate-500"
                                        >
                                            Students can access the
                                            lesson when the course
                                            is available.
                                        </p>

                                    </div>


                                    <div class="relative shrink-0">

                                        <input
                                            type="checkbox"
                                            name="is_published"
                                            value="1"
                                            class="peer sr-only"
                                            @checked(
                                                old(
                                                    'is_published',
                                                    $lesson->is_published
                                                )
                                            )
                                        >

                                        <div
                                            class="h-6 w-11 rounded-full
                                                   bg-slate-300 transition
                                                   after:absolute
                                                   after:left-[2px]
                                                   after:top-[2px]
                                                   after:h-5 after:w-5
                                                   after:rounded-full
                                                   after:bg-white
                                                   after:transition-all
                                                   after:content-['']
                                                   peer-checked:bg-violet-600
                                                   peer-checked:after:translate-x-full"
                                        ></div>

                                    </div>

                                </label>



                                {{-- PREVIEW --}}
                                <label
                                    class="flex cursor-pointer
                                           items-start justify-between
                                           gap-5 rounded-xl
                                           border border-slate-200
                                           bg-slate-50 p-4"
                                >

                                    <div>

                                        <p
                                            class="text-sm font-bold
                                                   text-slate-800"
                                        >
                                            Free preview
                                        </p>

                                        <p
                                            class="mt-1 text-xs
                                                   leading-5 text-slate-500"
                                        >
                                            Allow learners to preview
                                            this lesson before enrolling.
                                        </p>

                                    </div>


                                    <div class="relative shrink-0">

                                        <input
                                            type="checkbox"
                                            name="is_preview"
                                            value="1"
                                            class="peer sr-only"
                                            @checked(
                                                old(
                                                    'is_preview',
                                                    $lesson->is_preview
                                                )
                                            )
                                        >

                                        <div
                                            class="h-6 w-11 rounded-full
                                                   bg-slate-300 transition
                                                   after:absolute
                                                   after:left-[2px]
                                                   after:top-[2px]
                                                   after:h-5 after:w-5
                                                   after:rounded-full
                                                   after:bg-white
                                                   after:transition-all
                                                   after:content-['']
                                                   peer-checked:bg-violet-600
                                                   peer-checked:after:translate-x-full"
                                        ></div>

                                    </div>

                                </label>

                            </div>

                        </section>



                        {{-- MOBILE ACTIONS --}}
                        <div
                            class="flex flex-col-reverse gap-3
                                   sm:flex-row xl:hidden"
                        >

                            <a
                                href="{{ route('teacher.lessons', $lesson->course) }}"
                                class="inline-flex h-12
                                       items-center justify-center
                                       rounded-xl border
                                       border-slate-200 bg-white
                                       px-5 text-sm font-semibold
                                       text-slate-600 transition
                                       hover:bg-slate-50"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="inline-flex h-12 flex-1
                                       items-center justify-center
                                       rounded-xl bg-violet-600
                                       px-5 text-sm font-semibold
                                       text-white transition
                                       hover:bg-violet-700"
                            >
                                Save Changes
                            </button>

                        </div>

                    </div>



                    {{-- =================================================
                        RIGHT SIDE
                    ================================================== --}}

                    <aside class="hidden xl:block">

                        <div class="sticky top-7 space-y-5">


                            {{-- LIVE PREVIEW --}}
                            <section class="pw-card overflow-hidden">

                                <div
                                    class="border-b border-slate-100
                                           px-5 py-4"
                                >
                                    <p
                                        class="text-sm font-bold
                                               text-slate-900"
                                    >
                                        Lesson preview
                                    </p>

                                    <p
                                        class="mt-1 text-xs
                                               text-slate-400"
                                    >
                                        Preview your changes.
                                    </p>
                                </div>


                                <div class="p-5">

                                    <div
                                        id="previewIcon"
                                        class="flex h-14 w-14
                                               items-center justify-center
                                               rounded-2xl bg-violet-50
                                               text-xl text-violet-600"
                                    >
                                        ▶
                                    </div>


                                    <p
                                        id="previewType"
                                        class="mt-4 text-[10px]
                                               font-bold uppercase
                                               tracking-[.12em]
                                               text-violet-600"
                                    >
                                        Video Lesson
                                    </p>


                                    <h3
                                        id="previewTitle"
                                        class="mt-2 text-lg
                                               font-bold leading-6
                                               text-slate-900"
                                    >
                                        {{ $lesson->title }}
                                    </h3>


                                    <p
                                        id="previewDescription"
                                        class="mt-2 line-clamp-4
                                               text-sm leading-6
                                               text-slate-500"
                                    >
                                        {{
                                            $lesson->content
                                            ?: 'Lesson content or description will appear here.'
                                        }}
                                    </p>


                                    <div
                                        class="mt-5 flex flex-wrap
                                               items-center gap-3
                                               border-t
                                               border-slate-100 pt-4
                                               text-xs text-slate-500"
                                    >

                                        <span>
                                            Lesson
                                            <strong
                                                id="previewOrder"
                                                class="text-slate-700"
                                            >
                                                {{ $lesson->lesson_order }}
                                            </strong>
                                        </span>

                                        <span class="text-slate-300">
                                            •
                                        </span>

                                        <span id="previewDuration">
                                            {{
                                                $lesson->duration_minutes
                                                ? $lesson->duration_minutes . ' min'
                                                : 'Duration not set'
                                            }}
                                        </span>

                                    </div>

                                </div>

                            </section>



                            {{-- COURSE INFORMATION --}}
                            <section
                                class="rounded-2xl
                                       border border-violet-100
                                       bg-violet-50/70 p-5"
                            >

                                <p
                                    class="text-[10px] font-bold
                                           uppercase tracking-[.12em]
                                           text-violet-500"
                                >
                                    Part of
                                </p>

                                <p
                                    class="mt-2 text-sm font-bold
                                           text-violet-950"
                                >
                                    {{ $lesson->course->title }}
                                </p>

                                <p
                                    class="mt-1 text-xs
                                           text-violet-700/70"
                                >
                                    {{
                                        $lesson->course->category->name
                                        ?? 'PathWise Course'
                                    }}
                                </p>

                            </section>



                            {{-- ACTIONS --}}
                            <div class="space-y-2">

                                <button
                                    type="submit"
                                    class="inline-flex h-12
                                           w-full items-center
                                           justify-center gap-2
                                           rounded-xl
                                           bg-gradient-to-r
                                           from-violet-600
                                           to-indigo-600
                                           text-sm font-semibold
                                           text-white shadow-md
                                           shadow-violet-200
                                           transition
                                           hover:-translate-y-0.5
                                           hover:shadow-lg"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M5 12l4 4L19 6"></path>
                                    </svg>

                                    Save Changes
                                </button>


                                <a
                                    href="{{ route('teacher.lessons', $lesson->course) }}"
                                    class="inline-flex h-11
                                           w-full items-center
                                           justify-center rounded-xl
                                           border border-slate-200
                                           bg-white text-sm
                                           font-semibold text-slate-600
                                           transition
                                           hover:bg-slate-50"
                                >
                                    Cancel
                                </a>

                            </div>

                        </div>

                    </aside>

                </div>

            </form>

        </div>

    </main>

</div>



<script>
    function initializeEditLessonPage() {

        const typeRadios =
            document.querySelectorAll(
                'input[name="lesson_type"]'
            );

        const videoSection =
            document.getElementById(
                'videoSection'
            );

        const textSection =
            document.getElementById(
                'textSection'
            );

        const documentSection =
            document.getElementById(
                'documentSection'
            );

        const quizSection =
            document.getElementById(
                'quizSection'
            );

        const hiddenContent =
            document.getElementById(
                'content'
            );

        const title =
            document.getElementById(
                'title'
            );

        const order =
            document.getElementById(
                'lesson_order'
            );

        const duration =
            document.getElementById(
                'duration_minutes'
            );


        if (!title) {
            return;
        }



        /*
        |--------------------------------------------------------------------------
        | CURRENT TYPE
        |--------------------------------------------------------------------------
        */

        function selectedType() {

            return document.querySelector(
                'input[name="lesson_type"]:checked'
            )?.value || 'video';

        }



        /*
        |--------------------------------------------------------------------------
        | CURRENT CONTENT EDITOR
        |--------------------------------------------------------------------------
        */

        function activeContentInput() {

            const type =
                selectedType();


            if (type === 'text') {

                return document.getElementById(
                    'readingContent'
                );

            }


            if (type === 'document') {

                return document.getElementById(
                    'documentDescription'
                );

            }


            if (type === 'quiz') {

                return document.getElementById(
                    'quizDescription'
                );

            }


            return document.getElementById(
                'videoDescription'
            );

        }



        /*
        |--------------------------------------------------------------------------
        | KEEP HIDDEN CONTENT FIELD UPDATED
        |--------------------------------------------------------------------------
        */

        function synchronizeContent() {

            const source =
                activeContentInput();


            if (
                hiddenContent
                &&
                source
            ) {

                hiddenContent.value =
                    source.value;

            }

        }



        /*
        |--------------------------------------------------------------------------
        | DISPLAY CORRECT EDITOR
        |--------------------------------------------------------------------------
        */

        function updateSections() {

            const type =
                selectedType();


            if (videoSection) {

                videoSection.classList.toggle(
                    'hidden',
                    type !== 'video'
                );

            }


            if (textSection) {

                textSection.classList.toggle(
                    'hidden',
                    type !== 'text'
                );

            }


            if (documentSection) {

                documentSection.classList.toggle(
                    'hidden',
                    type !== 'document'
                );

            }


            if (quizSection) {

                quizSection.classList.toggle(
                    'hidden',
                    type !== 'quiz'
                );

            }


            synchronizeContent();

            updatePreview();

        }



        /*
        |--------------------------------------------------------------------------
        | LIVE PREVIEW
        |--------------------------------------------------------------------------
        */

        function updatePreview() {

            const type =
                selectedType();


            const typeNames = {

                video:
                    'Video Lesson',

                text:
                    'Reading Lesson',

                document:
                    'Document Lesson',

                quiz:
                    'Quiz'

            };


            const icons = {

                video:
                    '▶',

                text:
                    '📖',

                document:
                    '📄',

                quiz:
                    '?'

            };


            const previewTitle =
                document.getElementById(
                    'previewTitle'
                );

            const previewDescription =
                document.getElementById(
                    'previewDescription'
                );

            const previewType =
                document.getElementById(
                    'previewType'
                );

            const previewIcon =
                document.getElementById(
                    'previewIcon'
                );

            const previewOrder =
                document.getElementById(
                    'previewOrder'
                );

            const previewDuration =
                document.getElementById(
                    'previewDuration'
                );


            if (previewTitle) {

                previewTitle.textContent =
                    title.value.trim()
                    ||
                    'Your lesson title';

            }


            const contentInput =
                activeContentInput();


            if (previewDescription) {

                previewDescription.textContent =
                    contentInput?.value.trim()
                    ||
                    'Lesson content or description will appear here.';

            }


            if (previewType) {

                previewType.textContent =
                    typeNames[type];

            }


            if (previewIcon) {

                previewIcon.textContent =
                    icons[type];

            }


            if (previewOrder) {

                previewOrder.textContent =
                    order.value || '1';

            }


            if (previewDuration) {

                previewDuration.textContent =
                    duration.value
                        ? duration.value + ' min'
                        : 'Duration not set';

            }

        }



        /*
        |--------------------------------------------------------------------------
        | TYPE EVENTS
        |--------------------------------------------------------------------------
        */

        typeRadios.forEach(
            function (radio) {

                radio.addEventListener(
                    'change',
                    updateSections
                );

            }
        );



        /*
        |--------------------------------------------------------------------------
        | CONTENT EVENTS
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '[data-content-input]'
            )
            .forEach(
                function (input) {

                    input.addEventListener(
                        'input',
                        function () {

                            if (
                                input
                                ===
                                activeContentInput()
                            ) {

                                synchronizeContent();

                            }


                            updatePreview();

                        }
                    );

                }
            );



        /*
        |--------------------------------------------------------------------------
        | BASIC INPUT EVENTS
        |--------------------------------------------------------------------------
        */

        [
            title,
            order,
            duration
        ].forEach(
            function (input) {

                if (!input) {
                    return;
                }


                input.addEventListener(
                    'input',
                    updatePreview
                );

            }
        );



        /*
        |--------------------------------------------------------------------------
        | DOCUMENT REPLACEMENT
        |--------------------------------------------------------------------------
        */

        const fileInput =
            document.getElementById(
                'lesson_file'
            );

        const fileName =
            document.getElementById(
                'documentFileName'
            );

        const fileHelp =
            document.getElementById(
                'documentHelp'
            );


        if (fileInput) {

            fileInput.addEventListener(
                'change',
                function () {

                    const file =
                        fileInput.files[0];


                    if (!file) {
                        return;
                    }


                    if (fileName) {

                        fileName.textContent =
                            file.name;

                    }


                    if (fileHelp) {

                        const size =
                            (
                                file.size
                                /
                                1024
                                /
                                1024
                            ).toFixed(2);


                        fileHelp.textContent =
                            size
                            +
                            ' MB selected — this will replace the current file';

                    }

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | FORM SUBMISSION
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById(
                'editLessonForm'
            );


        if (form) {

            form.addEventListener(
                'submit',
                function () {

                    synchronizeContent();

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | INITIAL PAGE
        |--------------------------------------------------------------------------
        */

        updateSections();

    }


    document.addEventListener(
        'DOMContentLoaded',
        initializeEditLessonPage
    );


    document.addEventListener(
        'livewire:navigated',
        initializeEditLessonPage
    );
</script>


</x-layouts::app>