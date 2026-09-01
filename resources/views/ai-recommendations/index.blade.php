<x-layouts::app :title="__('AI Recommendations')">

    @php
        $totalRecommendations = $recommendations->count();
        $viewedRecommendations = $recommendations->where('is_viewed', true)->count();
        $unviewedRecommendations = $recommendations->where('is_viewed', false)->count();
        $averageScore = $totalRecommendations > 0
            ? round($recommendations->avg('recommendation_score'), 2)
            : 0;
    @endphp

    <style>
        .pw-card {
            background: #ffffff;
            border: 1px solid #e7e9ef;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.035);
        }
    </style>

    <div class="min-h-screen bg-[#f8f9fc]">

        <main class="px-5 py-7 sm:px-6 lg:px-8 lg:py-9">

            <div class="mx-auto max-w-[1500px] space-y-6">


                {{-- =====================================================
                    HEADER
                ====================================================== --}}

                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

                    <div>

                        <p class="text-xs font-bold uppercase tracking-[.12em] text-violet-600">
                            Artificial Intelligence
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                            AI Recommendations
                        </h1>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            Review personalized course recommendations generated from student quiz performance.
                        </p>

                    </div>


                    <a
                        href="{{ route('ai-recommendations.create') }}"
                        class="inline-flex h-11 self-start items-center justify-center
                               gap-2 rounded-xl bg-violet-600 px-5
                               text-sm font-semibold text-white transition
                               hover:bg-violet-700"
                    >
                        Generate Recommendation
                    </a>

                </div>



                {{-- =====================================================
                    STATS CARDS
                ====================================================== --}}

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">


                    {{-- TOTAL --}}
                    <div class="pw-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Total Recommendations
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">
                                    {{ $totalRecommendations }}
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M12 2l3 6 6 .9-4.5 4.4 1.1 6.2L12 16.5 6.4 19.5l1.1-6.2L3 8.9 9 8l3-6z"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            AI-generated suggestions
                        </p>

                    </div>



                    {{-- AVERAGE SCORE --}}
                    <div class="pw-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Average AI Score
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-violet-600">
                                    {{ $averageScore }}%
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M3 3v18h18"></path>
                                    <path d="m7 16 4-5 4 3 5-7"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Confidence level
                        </p>

                    </div>



                    {{-- VIEWED --}}
                    <div class="pw-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Viewed
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                    {{ $viewedRecommendations }}
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Seen by students
                        </p>

                    </div>



                    {{-- NOT VIEWED --}}
                    <div class="pw-card p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-xs font-semibold text-slate-500">
                                    Not Viewed
                                </p>

                                <p class="mt-2 text-3xl font-bold tracking-tight text-orange-500">
                                    {{ $unviewedRecommendations }}
                                </p>

                            </div>

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-500">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>

                            </div>

                        </div>

                        <p class="mt-2 text-xs text-slate-400">
                            Pending review
                        </p>

                    </div>

                </section>



                {{-- =====================================================
                    ALERTS
                ====================================================== --}}

                @if(session('success'))

                    <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">

                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            ✓
                        </div>

                        <p class="text-sm font-medium text-emerald-700">
                            {{ session('success') }}
                        </p>

                    </div>

                @endif


                @if(session('error'))

                    <div class="flex items-center gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">

                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                            !
                        </div>

                        <p class="text-sm font-medium text-red-700">
                            {{ session('error') }}
                        </p>

                    </div>

                @endif



                {{-- =====================================================
                    TABLE SECTION
                ====================================================== --}}

                <section class="pw-card overflow-hidden">


                    {{-- TABLE HEADER --}}
                    <div class="border-b border-slate-100 px-6 py-5">

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                            <div>

                                <h2 class="text-lg font-bold text-slate-900">
                                    Recommendation Records
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    AI-generated learning suggestions for students.
                                </p>

                            </div>


                            <div class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2 text-xs font-bold text-violet-700">

                                <span class="h-2 w-2 rounded-full bg-violet-500"></span>

                                AI Engine Active

                            </div>

                        </div>

                    </div>


                    {{-- TABLE --}}
                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[900px] text-left text-sm">

                            <thead class="border-b border-slate-100 bg-slate-50/80">

                                <tr class="text-[10px] font-bold uppercase tracking-[.08em] text-slate-400">

                                    <th class="px-6 py-4">Student</th>
                                    <th class="px-6 py-4">Recommended Course</th>
                                    <th class="px-6 py-4">AI Score</th>
                                    <th class="px-6 py-4">Reason</th>
                                    <th class="px-6 py-4">Viewed</th>
                                    <th class="px-6 py-4 text-right">Actions</th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse($recommendations as $recommendation)

                                    @php
                                        $score = $recommendation->recommendation_score ?? 0;

                                        $scoreClass = $score >= 85
                                            ? 'bg-emerald-500'
                                            : ($score >= 70 ? 'bg-yellow-500' : 'bg-red-500');

                                        $scoreTextClass = $score >= 85
                                            ? 'text-emerald-600'
                                            : ($score >= 70 ? 'text-yellow-600' : 'text-red-600');
                                    @endphp

                                    <tr class="transition hover:bg-slate-50/70">

                                        <td class="px-6 py-5">

                                            <div class="flex items-center gap-3">

                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-sm font-bold text-violet-600">
                                                    {{ strtoupper(substr($recommendation->student->name ?? 'S', 0, 1)) }}
                                                </div>

                                                <div class="min-w-0">

                                                    <p class="truncate text-sm font-semibold text-slate-800">
                                                        {{ $recommendation->student->name ?? 'N/A' }}
                                                    </p>

                                                    <p class="mt-0.5 text-[11px] text-slate-400">
                                                        Learner
                                                    </p>

                                                </div>

                                            </div>

                                        </td>

                                        <td class="px-6 py-5">

                                            <p class="text-sm font-semibold text-slate-800">
                                                {{ $recommendation->course->title ?? 'N/A' }}
                                            </p>

                                            <p class="mt-0.5 text-[11px] text-slate-400">
                                                Recommended Course
                                            </p>

                                        </td>

                                        <td class="px-6 py-5">

                                            <div class="flex items-center gap-3">

                                                <div class="h-2 w-28 overflow-hidden rounded-full bg-slate-100">

                                                    <div
                                                        class="h-full rounded-full {{ $scoreClass }}"
                                                        style="width: {{ min($score, 100) }}%"
                                                    ></div>

                                                </div>

                                                <span class="text-xs font-bold {{ $scoreTextClass }}">
                                                    {{ number_format($score, 2) }}%
                                                </span>

                                            </div>

                                        </td>

                                        <td class="max-w-xs px-6 py-5 text-xs leading-5 text-slate-500">
                                            {{ $recommendation->reason }}
                                        </td>

                                        <td class="px-6 py-5">

                                            @if($recommendation->is_viewed)

                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-[10px] font-bold text-emerald-700">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>

                                                    Viewed

                                                </span>

                                            @else

                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-3 py-1.5 text-[10px] font-bold text-orange-600">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>

                                                    Not Viewed

                                                </span>

                                            @endif

                                        </td>

                                        <td class="px-6 py-5 text-right">

                                            <form
                                                action="{{ route('ai-recommendations.destroy', $recommendation) }}"
                                                method="POST"
                                                class="inline"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="h-8 rounded-lg bg-red-600 px-3 text-[11px] font-bold text-white transition hover:bg-red-700"
                                                    onclick="return confirm('Delete recommendation?')"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="px-6 py-16 text-center">

                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">

                                                <svg
                                                    class="h-6 w-6"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path d="M12 2l3 6 6 .9-4.5 4.4 1.1 6.2L12 16.5 6.4 19.5l1.1-6.2L3 8.9 9 8l3-6z"></path>
                                                </svg>

                                            </div>

                                            <h3 class="mt-4 text-sm font-bold text-slate-800">
                                                No recommendations found
                                            </h3>

                                            <p class="mt-1 text-xs text-slate-400">
                                                Generate recommendations after students have quiz results.
                                            </p>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </section>

            </div>

        </main>

    </div>

</x-layouts::app>