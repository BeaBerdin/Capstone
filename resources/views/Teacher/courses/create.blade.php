<x-layouts::app :title="'Create Course'">

<style>

    .pw-create-card {
        background: #ffffff;
        border: 1px solid #e8eaf0;
        border-radius: 20px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .035);
    }

    .pw-field {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #dfe2e9;
        background: #ffffff;
        color: #172033;
        font-size: 14px;
        transition: all 160ms ease;
    }

    .pw-field:focus {
        border-color: #8b5cf6 !important;
        outline: none !important;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, .10) !important;
    }

    .pw-difficulty-option input:checked + div {
        border-color: #7c3aed;
        background: #f5f3ff;
        box-shadow: 0 0 0 2px rgba(124, 58, 237, .08);
    }

    .pw-difficulty-option input:checked + div .pw-radio-dot {
        border-color: #7c3aed;
    }

    .pw-difficulty-option input:checked + div .pw-radio-dot::after {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        background: #7c3aed;
        display: block;
    }

</style>


<div class="min-h-screen bg-[#f8f9fc]">

    <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

        <div class="mx-auto max-w-[1400px]">


            {{-- ==========================================================
                BREADCRUMB
            =========================================================== --}}

            <div
                class="mb-6 flex flex-wrap items-center gap-2
                       text-xs text-slate-400"
            >

                <a
                    href="{{ route('teacher.my-courses') }}"
                    class="font-medium transition
                           hover:text-violet-600"
                >
                    My Courses
                </a>

                <svg
                    class="h-3.5 w-3.5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="m9 18 6-6-6-6"></path>
                </svg>

                <span class="font-medium text-slate-600">
                    Create Course
                </span>

            </div>



            {{-- ==========================================================
                HEADER
            =========================================================== --}}

            <div class="mb-7">

                <p
                    class="text-xs font-bold uppercase
                           tracking-[0.12em] text-violet-600"
                >
                    Course Builder
                </p>

                <h1
                    class="mt-2 text-3xl font-bold
                           tracking-tight text-slate-950"
                >
                    Create a new course
                </h1>

                <p
                    class="mt-2 max-w-2xl text-sm
                           leading-6 text-slate-500"
                >
                    Add the basic information students will see
                    when discovering your course. You can add
                    lessons and learning content after creating it.
                </p>

            </div>



            {{-- ==========================================================
                ERRORS
            =========================================================== --}}

            @if($errors->any())

                <div
                    class="mb-6 rounded-2xl border
                           border-red-200 bg-red-50
                           p-5"
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
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                ></circle>

                                <path d="M12 8v4"></path>
                                <path d="M12 16h.01"></path>
                            </svg>
                        </div>


                        <div>

                            <p
                                class="text-sm font-bold
                                       text-red-800"
                            >
                                Please check your course information.
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



            <form
                action="{{ route('teacher.courses.store') }}"
                method="POST"
                enctype="multipart/form-data"
                id="createCourseForm"
            >

                @csrf


                <div
                    class="grid grid-cols-1 gap-6
                           xl:grid-cols-[minmax(0,1fr)_360px]"
                >


                    {{-- ==================================================
                        LEFT FORM
                    =================================================== --}}

                    <div class="space-y-6">


                        {{-- ===============================================
                            COURSE BASICS
                        ================================================ --}}

                        <section class="pw-create-card p-5 sm:p-7">

                            <div
                                class="flex items-start gap-4
                                       border-b border-slate-100
                                       pb-5"
                            >

                                <div
                                    class="flex h-11 w-11
                                           shrink-0 items-center
                                           justify-center
                                           rounded-xl
                                           bg-violet-50
                                           text-violet-600"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M12 20h9"
                                        ></path>

                                        <path
                                            d="M16.5 3.5a2.121
                                            2.121 0 013 3L7
                                            19l-4 1 1-4
                                            12.5-12.5z"
                                        ></path>
                                    </svg>
                                </div>


                                <div>

                                    <h2
                                        class="text-lg font-bold
                                               text-slate-900"
                                    >
                                        Course information
                                    </h2>

                                    <p
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        Give students a clear idea
                                        of what your course is about.
                                    </p>

                                </div>

                            </div>



                            <div class="mt-6 space-y-6">


                                {{-- Course title --}}
                                <div>

                                    <label
                                        for="title"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Course title

                                        <span class="text-red-500">
                                            *
                                        </span>
                                    </label>


                                    <p
                                        class="mt-1 text-xs
                                               text-slate-400"
                                    >
                                        Use a clear and descriptive
                                        title students can understand
                                        immediately.
                                    </p>


                                    <input
                                        type="text"
                                        name="title"
                                        id="title"
                                        maxlength="255"
                                        required
                                        value="{{ old('title') }}"
                                        placeholder="e.g. Basic Accounting Fundamentals"
                                        class="pw-field mt-2.5 h-12 px-4"
                                    >


                                    <div
                                        class="mt-2 flex
                                               justify-between"
                                    >

                                        @error('title')

                                            <p
                                                class="text-xs
                                                       text-red-600"
                                            >
                                                {{ $message }}
                                            </p>

                                        @else

                                            <span></span>

                                        @enderror


                                        <span
                                            id="titleCounter"
                                            class="text-[11px]
                                                   text-slate-400"
                                        >
                                            0 / 255
                                        </span>

                                    </div>

                                </div>



                                {{-- Category --}}
                                <div>

                                    <label
                                        for="category_id"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Category

                                        <span class="text-red-500">
                                            *
                                        </span>
                                    </label>


                                    <select
                                        name="category_id"
                                        id="category_id"
                                        required
                                        class="pw-field mt-2.5 h-12 px-4"
                                    >

                                        <option value="">
                                            Choose a category
                                        </option>


                                        @foreach($categories as $category)

                                            <option
                                                value="{{ $category->id }}"
                                                @selected(
                                                    old('category_id')
                                                    == $category->id
                                                )
                                            >
                                                {{ $category->name }}
                                            </option>

                                        @endforeach

                                    </select>


                                    @error('category_id')

                                        <p
                                            class="mt-2 text-xs
                                                   text-red-600"
                                        >
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>



                                {{-- Description --}}
                                <div>

                                    <label
                                        for="description"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Course description

                                        <span class="text-red-500">
                                            *
                                        </span>
                                    </label>


                                    <p
                                        class="mt-1 text-xs
                                               text-slate-400"
                                    >
                                        Explain what students will
                                        learn and who the course is for.
                                    </p>


                                    <textarea
                                        name="description"
                                        id="description"
                                        rows="6"
                                        required
                                        maxlength="2000"
                                        placeholder="Describe the course objectives, topics, and what students can expect to learn..."
                                        class="pw-field mt-2.5
                                               resize-none px-4 py-3
                                               leading-6"
                                    >{{ old('description') }}</textarea>


                                    <div
                                        class="mt-2 flex
                                               justify-between"
                                    >

                                        @error('description')

                                            <p
                                                class="text-xs
                                                       text-red-600"
                                            >
                                                {{ $message }}
                                            </p>

                                        @else

                                            <span></span>

                                        @enderror


                                        <span
                                            id="descriptionCounter"
                                            class="text-[11px]
                                                   text-slate-400"
                                        >
                                            0 / 2000
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </section>



                        {{-- ===============================================
                            COURSE COVER
                        ================================================ --}}

                        <section class="pw-create-card p-5 sm:p-7">

                            <div
                                class="flex items-start gap-4
                                       border-b border-slate-100
                                       pb-5"
                            >

                                <div
                                    class="flex h-11 w-11
                                           shrink-0 items-center
                                           justify-center
                                           rounded-xl bg-blue-50
                                           text-blue-600"
                                >

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <rect
                                            x="3"
                                            y="4"
                                            width="18"
                                            height="16"
                                            rx="2"
                                        ></rect>

                                        <circle
                                            cx="8.5"
                                            cy="9"
                                            r="1.5"
                                        ></circle>

                                        <path
                                            d="m21 15-5-5L5 20"
                                        ></path>
                                    </svg>

                                </div>


                                <div>

                                    <h2
                                        class="text-lg font-bold
                                               text-slate-900"
                                    >
                                        Course cover
                                    </h2>

                                    <p
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        Add an attractive cover image
                                        for the course marketplace and
                                        course cards.
                                    </p>

                                </div>

                            </div>



                            <div class="mt-6">

                                <input
                                    type="file"
                                    name="thumbnail"
                                    id="thumbnail"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    class="hidden"
                                >


                                <label
                                    for="thumbnail"
                                    id="thumbnailDropzone"
                                    class="group relative flex
                                           min-h-[240px] cursor-pointer
                                           items-center justify-center
                                           overflow-hidden
                                           rounded-2xl
                                           border-2 border-dashed
                                           border-slate-200
                                           bg-slate-50
                                           transition
                                           hover:border-violet-300
                                           hover:bg-violet-50/30"
                                >


                                    {{-- Empty uploader --}}
                                    <div
                                        id="thumbnailEmpty"
                                        class="px-6 py-10
                                               text-center"
                                    >

                                        <div
                                            class="mx-auto flex
                                                   h-14 w-14
                                                   items-center
                                                   justify-center
                                                   rounded-2xl
                                                   bg-white
                                                   text-violet-600
                                                   shadow-sm"
                                        >

                                            <svg
                                                class="h-6 w-6"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    d="M12 16V4"
                                                ></path>

                                                <path
                                                    d="m7 9 5-5 5 5"
                                                ></path>

                                                <path
                                                    d="M20 15v4a2 2
                                                    0 01-2 2H6a2
                                                    2 0 01-2-2v-4"
                                                ></path>
                                            </svg>

                                        </div>


                                        <p
                                            class="mt-4 text-sm
                                                   font-bold
                                                   text-slate-800"
                                        >
                                            Upload course cover
                                        </p>

                                        <p
                                            class="mt-1 text-xs
                                                   text-slate-500"
                                        >
                                            Click to browse or drag
                                            an image here.
                                        </p>

                                        <p
                                            class="mt-3 text-[11px]
                                                   text-slate-400"
                                        >
                                            JPG, PNG or WEBP ·
                                            Maximum 5 MB
                                        </p>

                                        <p
                                            class="mt-1 text-[11px]
                                                   text-slate-400"
                                        >
                                            Recommended:
                                            1280 × 720 pixels
                                        </p>

                                    </div>



                                    {{-- Preview --}}
                                    <div
                                        id="thumbnailPreviewWrapper"
                                        class="absolute inset-0 hidden"
                                    >

                                        <img
                                            id="thumbnailPreview"
                                            src=""
                                            alt="Course cover preview"
                                            class="h-full w-full
                                                   object-cover"
                                        >


                                        <div
                                            class="absolute inset-0
                                                   flex items-end
                                                   bg-gradient-to-t
                                                   from-black/55
                                                   via-transparent
                                                   to-transparent
                                                   p-5"
                                        >

                                            <div
                                                class="flex w-full
                                                       items-end
                                                       justify-between
                                                       gap-4"
                                            >

                                                <div>

                                                    <p
                                                        class="text-sm
                                                               font-semibold
                                                               text-white"
                                                    >
                                                        Course cover
                                                    </p>

                                                    <p
                                                        id="thumbnailFileName"
                                                        class="mt-1
                                                               max-w-[360px]
                                                               truncate
                                                               text-xs
                                                               text-white/75"
                                                    ></p>

                                                </div>


                                                <span
                                                    class="rounded-lg
                                                           bg-white/90
                                                           px-3 py-2
                                                           text-xs
                                                           font-semibold
                                                           text-slate-700
                                                           backdrop-blur"
                                                >
                                                    Change image
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                </label>


                                @error('thumbnail')

                                    <p
                                        class="mt-2 text-xs
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </section>



                        {{-- ===============================================
                            LEVEL AND DURATION
                        ================================================ --}}

                        <section class="pw-create-card p-5 sm:p-7">

                            <div
                                class="flex items-start gap-4
                                       border-b border-slate-100
                                       pb-5"
                            >

                                <div
                                    class="flex h-11 w-11
                                           shrink-0 items-center
                                           justify-center
                                           rounded-xl
                                           bg-emerald-50
                                           text-emerald-600"
                                >

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M3 3v18h18"
                                        ></path>

                                        <path
                                            d="m7 16 4-5
                                            3 3 5-7"
                                        ></path>
                                    </svg>

                                </div>


                                <div>

                                    <h2
                                        class="text-lg font-bold
                                               text-slate-900"
                                    >
                                        Learning level & duration
                                    </h2>

                                    <p
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        Help students understand
                                        the expected difficulty and
                                        time commitment.
                                    </p>

                                </div>

                            </div>



                            <div class="mt-6">


                                <label
                                    class="text-sm font-semibold
                                           text-slate-800"
                                >
                                    Difficulty level

                                    <span class="text-red-500">
                                        *
                                    </span>
                                </label>



                                <div
                                    class="mt-3 grid grid-cols-1
                                           gap-3 sm:grid-cols-3"
                                >


                                    {{-- Beginner --}}
                                    <label
                                        class="pw-difficulty-option
                                               cursor-pointer"
                                    >

                                        <input
                                            type="radio"
                                            name="difficulty_level"
                                            value="beginner"
                                            class="sr-only"
                                            @checked(
                                                old(
                                                    'difficulty_level',
                                                    'beginner'
                                                )
                                                === 'beginner'
                                            )
                                        >


                                        <div
                                            class="flex h-full
                                                   items-start gap-3
                                                   rounded-xl
                                                   border
                                                   border-slate-200
                                                   p-4 transition
                                                   hover:border-violet-200"
                                        >

                                            <div
                                                class="pw-radio-dot
                                                       mt-0.5 flex
                                                       h-4 w-4
                                                       shrink-0
                                                       items-center
                                                       justify-center
                                                       rounded-full
                                                       border-2
                                                       border-slate-300"
                                            ></div>


                                            <div>

                                                <p
                                                    class="text-sm
                                                           font-bold
                                                           text-slate-800"
                                                >
                                                    Beginner
                                                </p>

                                                <p
                                                    class="mt-1
                                                           text-[11px]
                                                           leading-4
                                                           text-slate-400"
                                                >
                                                    No prior knowledge
                                                    required.
                                                </p>

                                            </div>

                                        </div>

                                    </label>



                                    {{-- Intermediate --}}
                                    <label
                                        class="pw-difficulty-option
                                               cursor-pointer"
                                    >

                                        <input
                                            type="radio"
                                            name="difficulty_level"
                                            value="intermediate"
                                            class="sr-only"
                                            @checked(
                                                old(
                                                    'difficulty_level'
                                                )
                                                === 'intermediate'
                                            )
                                        >


                                        <div
                                            class="flex h-full
                                                   items-start gap-3
                                                   rounded-xl
                                                   border
                                                   border-slate-200
                                                   p-4 transition
                                                   hover:border-violet-200"
                                        >

                                            <div
                                                class="pw-radio-dot
                                                       mt-0.5 flex
                                                       h-4 w-4
                                                       shrink-0
                                                       items-center
                                                       justify-center
                                                       rounded-full
                                                       border-2
                                                       border-slate-300"
                                            ></div>


                                            <div>

                                                <p
                                                    class="text-sm
                                                           font-bold
                                                           text-slate-800"
                                                >
                                                    Intermediate
                                                </p>

                                                <p
                                                    class="mt-1
                                                           text-[11px]
                                                           leading-4
                                                           text-slate-400"
                                                >
                                                    Some previous
                                                    knowledge helpful.
                                                </p>

                                            </div>

                                        </div>

                                    </label>



                                    {{-- Advanced --}}
                                    <label
                                        class="pw-difficulty-option
                                               cursor-pointer"
                                    >

                                        <input
                                            type="radio"
                                            name="difficulty_level"
                                            value="advanced"
                                            class="sr-only"
                                            @checked(
                                                old(
                                                    'difficulty_level'
                                                )
                                                === 'advanced'
                                            )
                                        >


                                        <div
                                            class="flex h-full
                                                   items-start gap-3
                                                   rounded-xl
                                                   border
                                                   border-slate-200
                                                   p-4 transition
                                                   hover:border-violet-200"
                                        >

                                            <div
                                                class="pw-radio-dot
                                                       mt-0.5 flex
                                                       h-4 w-4
                                                       shrink-0
                                                       items-center
                                                       justify-center
                                                       rounded-full
                                                       border-2
                                                       border-slate-300"
                                            ></div>


                                            <div>

                                                <p
                                                    class="text-sm
                                                           font-bold
                                                           text-slate-800"
                                                >
                                                    Advanced
                                                </p>

                                                <p
                                                    class="mt-1
                                                           text-[11px]
                                                           leading-4
                                                           text-slate-400"
                                                >
                                                    Designed for
                                                    experienced learners.
                                                </p>

                                            </div>

                                        </div>

                                    </label>

                                </div>


                                @error('difficulty_level')

                                    <p
                                        class="mt-2 text-xs
                                               text-red-600"
                                    >
                                        {{ $message }}
                                    </p>

                                @enderror



                                <div
                                    class="mt-6 grid grid-cols-1
                                           gap-5 sm:grid-cols-2"
                                >


                                    {{-- Hours --}}
                                    <div>

                                        <label
                                            for="estimated_hours"
                                            class="text-sm font-semibold
                                                   text-slate-800"
                                        >
                                            Estimated duration
                                        </label>


                                        <div class="relative mt-2.5">

                                            <input
                                                type="number"
                                                name="estimated_hours"
                                                id="estimated_hours"
                                                min="1"
                                                value="{{ old('estimated_hours') }}"
                                                placeholder="e.g. 8"
                                                class="pw-field h-12
                                                       px-4 pr-16"
                                            >

                                            <span
                                                class="pointer-events-none
                                                       absolute right-4
                                                       top-1/2
                                                       -translate-y-1/2
                                                       text-xs
                                                       text-slate-400"
                                            >
                                                hours
                                            </span>

                                        </div>


                                        @error('estimated_hours')

                                            <p
                                                class="mt-2 text-xs
                                                       text-red-600"
                                            >
                                                {{ $message }}
                                            </p>

                                        @enderror

                                    </div>



                                    {{-- Price --}}
                                    <div>

                                        <label
                                            for="price"
                                            class="text-sm font-semibold
                                                   text-slate-800"
                                        >
                                            Course price
                                        </label>


                                        <div class="relative mt-2.5">

                                            <span
                                                class="pointer-events-none
                                                       absolute left-4
                                                       top-1/2
                                                       -translate-y-1/2
                                                       text-sm font-medium
                                                       text-slate-500"
                                            >
                                                ₱
                                            </span>


                                            <input
                                                type="number"
                                                name="price"
                                                id="price"
                                                min="0"
                                                step="0.01"
                                                value="{{ old('price', 0) }}"
                                                placeholder="0.00"
                                                class="pw-field h-12
                                                       pl-9 pr-4"
                                            >

                                        </div>


                                        <p
                                            class="mt-1.5 text-[11px]
                                                   text-slate-400"
                                        >
                                            Enter 0.00 to make
                                            this course free.
                                        </p>


                                        @error('price')

                                            <p
                                                class="mt-2 text-xs
                                                       text-red-600"
                                            >
                                                {{ $message }}
                                            </p>

                                        @enderror

                                    </div>

                                </div>

                            </div>

                        </section>



                        {{-- ===============================================
                            ADDITIONAL OPTIONS
                        ================================================ --}}

                        <section class="pw-create-card p-5 sm:p-7">

                            <div
                                class="flex items-start gap-4
                                       border-b border-slate-100
                                       pb-5"
                            >

                                <div
                                    class="flex h-11 w-11
                                           shrink-0 items-center
                                           justify-center
                                           rounded-xl
                                           bg-orange-50
                                           text-orange-500"
                                >

                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="3"
                                        ></circle>

                                        <path
                                            d="M19.4 15a1.7 1.7
                                            0 00.34 1.88l.06.06
                                            a2 2 0 01-2.83
                                            2.83l-.06-.06a1.7
                                            1.7 0 00-1.88-.34
                                            1.7 1.7 0 00-1
                                            1.55V21a2 2 0
                                            01-4 0v-.09a1.7
                                            1.7 0 00-1-1.55
                                            1.7 1.7 0 00-1.88
                                            .34l-.06.06a2 2 0
                                            01-2.83-2.83l.06
                                            -.06A1.7 1.7 0
                                            004.6 15a1.7 1.7
                                            0 00-1.55-1H3a2
                                            2 0 010-4h.09a1.7
                                            1.7 0 001.55-1
                                            1.7 1.7 0 00-.34
                                            -1.88l-.06-.06
                                            a2 2 0 012.83
                                            -2.83l.06.06A1.7
                                            1.7 0 009 4.6a1.7
                                            1.7 0 001-1.55V3
                                            a2 2 0 014 0v.09
                                            a1.7 1.7 0 001
                                            1.55 1.7 1.7 0
                                            001.88-.34l.06-.06
                                            a2 2 0 012.83
                                            2.83l-.06.06A1.7
                                            1.7 0 0019.4 9c
                                            .12.4.53.9 1.55
                                            1H21a2 2 0 010
                                            4h-.09a1.7 1.7
                                            0 00-1.51 1z"
                                        ></path>
                                    </svg>

                                </div>


                                <div>

                                    <h2
                                        class="text-lg font-bold
                                               text-slate-900"
                                    >
                                        Additional settings
                                    </h2>

                                    <p
                                        class="mt-1 text-sm
                                               text-slate-500"
                                    >
                                        Optional information and
                                        course completion settings.
                                    </p>

                                </div>

                            </div>



                            <div class="mt-6 space-y-6">


                                {{-- Intro Video --}}
                                <div>

                                    <label
                                        for="intro_video"
                                        class="text-sm font-semibold
                                               text-slate-800"
                                    >
                                        Course introduction video
                                    </label>


                                    <p
                                        class="mt-1 text-xs
                                               text-slate-400"
                                    >
                                        Optional YouTube or other
                                        public video URL.
                                    </p>


                                    <div class="relative mt-2.5">

                                        <svg
                                            class="pointer-events-none
                                                   absolute left-4
                                                   top-1/2 h-4.5 w-4.5
                                                   -translate-y-1/2
                                                   text-slate-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <polygon
                                                points="5 3 19 12 5 21 5 3"
                                            ></polygon>
                                        </svg>


                                        <input
                                            type="url"
                                            name="intro_video"
                                            id="intro_video"
                                            maxlength="255"
                                            value="{{ old('intro_video') }}"
                                            placeholder="https://youtube.com/watch?v=..."
                                            class="pw-field h-12
                                                   pl-11 pr-4"
                                        >

                                    </div>


                                    @error('intro_video')

                                        <p
                                            class="mt-2 text-xs
                                                   text-red-600"
                                        >
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>



                                {{-- Certificate --}}
                                <div
                                    class="flex items-start
                                           justify-between gap-5
                                           rounded-xl border
                                           border-slate-200
                                           bg-slate-50 p-4"
                                >

                                    <div class="flex gap-3">

                                        <div
                                            class="flex h-10 w-10
                                                   shrink-0 items-center
                                                   justify-center
                                                   rounded-xl
                                                   bg-white
                                                   text-amber-500
                                                   shadow-sm"
                                        >

                                            <svg
                                                class="h-5 w-5"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <circle
                                                    cx="12"
                                                    cy="8"
                                                    r="5"
                                                ></circle>

                                                <path
                                                    d="M8.5 12
                                                    7 22l5-3 5
                                                    3-1.5-10"
                                                ></path>
                                            </svg>

                                        </div>


                                        <div>

                                            <p
                                                class="text-sm
                                                       font-bold
                                                       text-slate-800"
                                            >
                                                Completion certificate
                                            </p>

                                            <p
                                                class="mt-1
                                                       text-xs
                                                       leading-5
                                                       text-slate-500"
                                            >
                                                Allow qualified
                                                students to earn a
                                                certificate after
                                                completing the course.
                                            </p>

                                        </div>

                                    </div>



                                    <label
                                        class="relative mt-1
                                               inline-flex
                                               cursor-pointer
                                               items-center"
                                    >

                                        <input
                                            type="checkbox"
                                            name="certificate_available"
                                            value="1"
                                            class="peer sr-only"
                                            @checked(
                                                old(
                                                    'certificate_available',
                                                    1
                                                )
                                            )
                                        >

                                        <div
                                            class="h-6 w-11
                                                   rounded-full
                                                   bg-slate-300
                                                   transition
                                                   after:absolute
                                                   after:left-[2px]
                                                   after:top-[2px]
                                                   after:h-5
                                                   after:w-5
                                                   after:rounded-full
                                                   after:bg-white
                                                   after:transition-all
                                                   after:content-['']
                                                   peer-checked:bg-violet-600
                                                   peer-checked:after:translate-x-full"
                                        ></div>

                                    </label>

                                </div>

                            </div>

                        </section>



                        {{-- ===============================================
                            MOBILE ACTIONS
                        ================================================ --}}

                        <div
                            class="flex flex-col-reverse gap-3
                                   sm:flex-row xl:hidden"
                        >

                            <a
                                href="{{ route('teacher.my-courses') }}"
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
                                       gap-2 rounded-xl
                                       bg-gradient-to-r
                                       from-violet-600
                                       to-indigo-600
                                       px-5 text-sm font-semibold
                                       text-white shadow-md
                                       shadow-violet-200
                                       transition
                                       hover:-translate-y-0.5
                                       hover:shadow-lg"
                            >
                                Create Course
                            </button>

                        </div>

                    </div>



                    {{-- ==================================================
                        RIGHT PREVIEW SIDEBAR
                    =================================================== --}}

                    <aside class="hidden xl:block">

                        <div class="sticky top-7 space-y-5">


                            {{-- Course preview --}}
                            <section
                                class="pw-create-card
                                       overflow-hidden"
                            >

                                <div
                                    class="border-b
                                           border-slate-100
                                           px-5 py-4"
                                >

                                    <p
                                        class="text-sm font-bold
                                               text-slate-900"
                                    >
                                        Course preview
                                    </p>

                                    <p
                                        class="mt-1 text-xs
                                               text-slate-400"
                                    >
                                        How your course card
                                        may appear.
                                    </p>

                                </div>



                                {{-- Preview cover --}}
                                <div
                                    class="relative h-[190px]
                                           overflow-hidden
                                           bg-gradient-to-br
                                           from-violet-600
                                           via-purple-600
                                           to-indigo-700"
                                    id="previewCover"
                                >

                                    <img
                                        id="sidebarThumbnailPreview"
                                        src=""
                                        alt=""
                                        class="hidden h-full w-full
                                               object-cover"
                                    >


                                    <div
                                        id="sidebarFallbackCover"
                                        class="absolute inset-0
                                               flex items-center
                                               justify-center"
                                    >

                                        <div
                                            class="absolute -right-10
                                                   -top-12 h-40 w-40
                                                   rounded-full
                                                   border-[28px]
                                                   border-white/10"
                                        ></div>


                                        <div
                                            class="absolute
                                                   -bottom-14
                                                   -left-9 h-40 w-40
                                                   rounded-full
                                                   bg-white/10"
                                        ></div>


                                        <div
                                            class="relative
                                                   text-center"
                                        >

                                            <div
                                                class="mx-auto flex
                                                       h-14 w-14
                                                       items-center
                                                       justify-center
                                                       rounded-2xl
                                                       bg-white/15
                                                       text-white
                                                       backdrop-blur"
                                            >

                                                <svg
                                                    class="h-7 w-7"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.7"
                                                >
                                                    <path
                                                        d="M12 6.042A8.967
                                                        8.967 0 006
                                                        3.75c-1.052 0
                                                        -2.062.18-3
                                                        .512v14.25A8.987
                                                        8.987 0 016
                                                        18c2.305 0
                                                        4.408.867 6
                                                        2.292m0-14.25
                                                        a8.966 8.966 0
                                                        016-2.292c1.052
                                                        0 2.062.18 3
                                                        .512v14.25A8.987
                                                        8.987 0 0018
                                                        18a8.967 8.967
                                                        0 00-6
                                                        2.292m0-14.25
                                                        v14.25"
                                                    ></path>
                                                </svg>

                                            </div>


                                            <p
                                                class="mt-3 text-[10px]
                                                       font-bold uppercase
                                                       tracking-[.18em]
                                                       text-white/75"
                                            >
                                                PathWise Course
                                            </p>

                                        </div>

                                    </div>



                                    <div
                                        class="absolute left-3 top-3"
                                    >

                                        <span
                                            class="inline-flex
                                                   items-center gap-1.5
                                                   rounded-full
                                                   bg-white/95
                                                   px-3 py-1.5
                                                   text-[10px]
                                                   font-bold
                                                   text-slate-600
                                                   shadow-sm"
                                        >
                                            <span
                                                class="h-1.5 w-1.5
                                                       rounded-full
                                                       bg-slate-400"
                                            ></span>

                                            Draft
                                        </span>

                                    </div>

                                </div>



                                <div class="p-5">

                                    <div
                                        class="flex items-center
                                               gap-2 text-xs"
                                    >

                                        <span
                                            id="previewCategory"
                                            class="font-semibold
                                                   text-violet-600"
                                        >
                                            Course Category
                                        </span>

                                        <span class="text-slate-300">
                                            •
                                        </span>

                                        <span
                                            id="previewDifficulty"
                                            class="font-medium
                                                   text-slate-400"
                                        >
                                            Beginner
                                        </span>

                                    </div>



                                    <h3
                                        id="previewTitle"
                                        class="mt-2 text-[17px]
                                               font-bold leading-6
                                               text-slate-900"
                                    >
                                        Your course title
                                    </h3>



                                    <p
                                        id="previewDescription"
                                        class="mt-2 line-clamp-3
                                               text-sm leading-6
                                               text-slate-500"
                                    >
                                        Your course description
                                        will appear here for students.
                                    </p>



                                    <div
                                        class="mt-5 grid grid-cols-3
                                               divide-x
                                               divide-slate-100
                                               rounded-xl
                                               border
                                               border-slate-100
                                               bg-slate-50"
                                    >

                                        <div
                                            class="py-3
                                                   text-center"
                                        >
                                            <p
                                                class="text-[9px]
                                                       font-bold
                                                       uppercase
                                                       tracking-wide
                                                       text-slate-400"
                                            >
                                                Lessons
                                            </p>

                                            <p
                                                class="mt-1 text-sm
                                                       font-bold
                                                       text-slate-700"
                                            >
                                                0
                                            </p>
                                        </div>


                                        <div
                                            class="py-3
                                                   text-center"
                                        >
                                            <p
                                                class="text-[9px]
                                                       font-bold
                                                       uppercase
                                                       tracking-wide
                                                       text-slate-400"
                                            >
                                                Duration
                                            </p>

                                            <p
                                                id="previewDuration"
                                                class="mt-1 text-sm
                                                       font-bold
                                                       text-slate-700"
                                            >
                                                —
                                            </p>
                                        </div>


                                        <div
                                            class="py-3
                                                   text-center"
                                        >
                                            <p
                                                class="text-[9px]
                                                       font-bold
                                                       uppercase
                                                       tracking-wide
                                                       text-slate-400"
                                            >
                                                Price
                                            </p>

                                            <p
                                                id="previewPrice"
                                                class="mt-1 text-sm
                                                       font-bold
                                                       text-slate-700"
                                            >
                                                Free
                                            </p>
                                        </div>

                                    </div>

                                </div>

                            </section>



                            {{-- Status information --}}
                            <section
                                class="rounded-2xl
                                       border border-violet-100
                                       bg-violet-50/70 p-5"
                            >

                                <div class="flex gap-3">

                                    <div
                                        class="flex h-9 w-9
                                               shrink-0 items-center
                                               justify-center
                                               rounded-xl
                                               bg-white
                                               text-violet-600
                                               shadow-sm"
                                    >
                                        <svg
                                            class="h-4.5 w-4.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="9"
                                            ></circle>

                                            <path d="M12 8v4"></path>
                                            <path d="M12 16h.01"></path>
                                        </svg>
                                    </div>


                                    <div>

                                        <p
                                            class="text-sm font-bold
                                                   text-violet-900"
                                        >
                                            Saved as draft
                                        </p>

                                        <p
                                            class="mt-1 text-xs
                                                   leading-5
                                                   text-violet-700/80"
                                        >
                                            Your new course will
                                            initially be saved as a
                                            draft. Add your lessons
                                            before submitting it for
                                            approval.
                                        </p>

                                    </div>

                                </div>

                            </section>



                            {{-- Desktop actions --}}
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
                                           px-5 text-sm
                                           font-semibold text-white
                                           shadow-md
                                           shadow-violet-200
                                           transition
                                           hover:-translate-y-0.5
                                           hover:shadow-lg"
                                >

                                    <svg
                                        class="h-4.5 w-4.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M12 5v14M5 12h14"
                                        ></path>
                                    </svg>

                                    Create Course

                                </button>


                                <a
                                    href="{{ route('teacher.my-courses') }}"
                                    class="inline-flex h-11
                                           w-full items-center
                                           justify-center
                                           rounded-xl
                                           border border-slate-200
                                           bg-white px-5
                                           text-sm font-semibold
                                           text-slate-600
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

    function initializeCreateCoursePage() {

        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const title =
            document.getElementById('title');

        const description =
            document.getElementById('description');

        const category =
            document.getElementById('category_id');

        const price =
            document.getElementById('price');

        const hours =
            document.getElementById('estimated_hours');

        const thumbnail =
            document.getElementById('thumbnail');

        const dropzone =
            document.getElementById('thumbnailDropzone');


        if (!title) {
            return;
        }



        /*
        |--------------------------------------------------------------------------
        | CHARACTER COUNTERS
        |--------------------------------------------------------------------------
        */

        function updateCounters() {

            const titleCounter =
                document.getElementById(
                    'titleCounter'
                );

            const descriptionCounter =
                document.getElementById(
                    'descriptionCounter'
                );


            if (titleCounter) {

                titleCounter.textContent =
                    title.value.length +
                    ' / 255';

            }


            if (descriptionCounter) {

                descriptionCounter.textContent =
                    description.value.length +
                    ' / 2000';

            }

        }



        /*
        |--------------------------------------------------------------------------
        | LIVE CARD PREVIEW
        |--------------------------------------------------------------------------
        */

        function updatePreview() {

            const previewTitle =
                document.getElementById(
                    'previewTitle'
                );

            const previewDescription =
                document.getElementById(
                    'previewDescription'
                );

            const previewCategory =
                document.getElementById(
                    'previewCategory'
                );

            const previewPrice =
                document.getElementById(
                    'previewPrice'
                );

            const previewDuration =
                document.getElementById(
                    'previewDuration'
                );

            const previewDifficulty =
                document.getElementById(
                    'previewDifficulty'
                );


            if (previewTitle) {

                previewTitle.textContent =
                    title.value.trim()
                    || 'Your course title';

            }


            if (previewDescription) {

                previewDescription.textContent =
                    description.value.trim()
                    || 'Your course description will appear here for students.';

            }


            if (previewCategory) {

                previewCategory.textContent =
                    category.options[
                        category.selectedIndex
                    ]?.text
                    || 'Course Category';

            }


            if (previewPrice) {

                const amount =
                    parseFloat(
                        price.value || 0
                    );


                previewPrice.textContent =
                    amount > 0
                    ? '₱' +
                      amount.toLocaleString(
                          undefined,
                          {
                              minimumFractionDigits: 2,
                              maximumFractionDigits: 2
                          }
                      )
                    : 'Free';

            }


            if (previewDuration) {

                previewDuration.textContent =
                    hours.value
                    ? hours.value + ' hr'
                    : '—';

            }


            const checkedDifficulty =
                document.querySelector(
                    'input[name="difficulty_level"]:checked'
                );


            if (
                previewDifficulty
                &&
                checkedDifficulty
            ) {

                previewDifficulty.textContent =
                    checkedDifficulty.value
                        .charAt(0)
                        .toUpperCase()
                    +
                    checkedDifficulty.value
                        .slice(1);

            }

        }



        /*
        |--------------------------------------------------------------------------
        | IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        function previewImage(file) {

            if (!file) {
                return;
            }


            if (!file.type.startsWith('image/')) {
                return;
            }


            const reader =
                new FileReader();


            reader.onload = function(event) {

                const previewWrapper =
                    document.getElementById(
                        'thumbnailPreviewWrapper'
                    );

                const preview =
                    document.getElementById(
                        'thumbnailPreview'
                    );

                const empty =
                    document.getElementById(
                        'thumbnailEmpty'
                    );

                const fileName =
                    document.getElementById(
                        'thumbnailFileName'
                    );

                const sidebarPreview =
                    document.getElementById(
                        'sidebarThumbnailPreview'
                    );

                const sidebarFallback =
                    document.getElementById(
                        'sidebarFallbackCover'
                    );


                if (preview) {
                    preview.src =
                        event.target.result;
                }


                if (previewWrapper) {
                    previewWrapper.classList
                        .remove('hidden');
                }


                if (empty) {
                    empty.classList
                        .add('invisible');
                }


                if (fileName) {
                    fileName.textContent =
                        file.name;
                }


                if (sidebarPreview) {

                    sidebarPreview.src =
                        event.target.result;

                    sidebarPreview.classList
                        .remove('hidden');

                }


                if (sidebarFallback) {
                    sidebarFallback.classList
                        .add('hidden');
                }

            };


            reader.readAsDataURL(file);

        }



        /*
        |--------------------------------------------------------------------------
        | FILE INPUT
        |--------------------------------------------------------------------------
        */

        if (thumbnail) {

            thumbnail.onchange =
                function(event) {

                    const file =
                        event.target.files[0];

                    previewImage(file);

                };

        }



        /*
        |--------------------------------------------------------------------------
        | DRAG / DROP
        |--------------------------------------------------------------------------
        */

        if (dropzone && thumbnail) {

            [
                'dragenter',
                'dragover'
            ].forEach(function(eventName) {

                dropzone.addEventListener(
                    eventName,
                    function(event) {

                        event.preventDefault();

                        dropzone.classList.add(
                            'border-violet-400',
                            'bg-violet-50'
                        );

                    }
                );

            });


            [
                'dragleave',
                'drop'
            ].forEach(function(eventName) {

                dropzone.addEventListener(
                    eventName,
                    function(event) {

                        event.preventDefault();

                        dropzone.classList.remove(
                            'border-violet-400',
                            'bg-violet-50'
                        );

                    }
                );

            });


            dropzone.addEventListener(
                'drop',
                function(event) {

                    const file =
                        event.dataTransfer
                            .files[0];


                    if (!file) {
                        return;
                    }


                    const dataTransfer =
                        new DataTransfer();


                    dataTransfer.items.add(
                        file
                    );


                    thumbnail.files =
                        dataTransfer.files;


                    previewImage(file);

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | EVENTS
        |--------------------------------------------------------------------------
        */

        [
            title,
            description,
            category,
            price,
            hours
        ].forEach(function(element) {

            if (!element) {
                return;
            }


            element.addEventListener(
                'input',
                function() {

                    updateCounters();
                    updatePreview();

                }
            );


            element.addEventListener(
                'change',
                updatePreview
            );

        });



        document
            .querySelectorAll(
                'input[name="difficulty_level"]'
            )
            .forEach(function(radio) {

                radio.addEventListener(
                    'change',
                    updatePreview
                );

            });



        /*
        |--------------------------------------------------------------------------
        | INITIAL STATE
        |--------------------------------------------------------------------------
        */

        updateCounters();
        updatePreview();

    }



    document.addEventListener(
        'DOMContentLoaded',
        initializeCreateCoursePage
    );


    document.addEventListener(
        'livewire:navigated',
        initializeCreateCoursePage
    );

</script>


</x-layouts::app>