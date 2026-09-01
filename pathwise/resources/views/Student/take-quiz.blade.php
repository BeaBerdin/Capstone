<x-layouts::app :title="$quiz->title">

<div class="space-y-6">

    <div>
        <h1 class="text-3xl font-bold">
            {{ $quiz->title }}
        </h1>

        <p class="text-gray-500">
            {{ $quiz->course->title }}
        </p>
    </div>

    <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-neutral-900 dark:border-neutral-700">

        <div class="mb-4">
            <p>
                <strong>Passing Score:</strong>
                {{ $quiz->passing_score }}%
            </p>

            <p>
                <strong>Total Questions:</strong>
                {{ $quiz->questions->count() }}
            </p>
        </div>

        <form action="{{ route('student.quiz.submit', $quiz) }}"
              method="POST">

            @csrf

            @foreach($quiz->questions as $question)

                <div class="mb-6 border-b pb-4">

                    <h3 class="font-semibold mb-3">
                        {{ $loop->iteration }}.
                        {{ $question->question }}
                    </h3>

                    <div class="space-y-2">

                        <label class="block">
                            <input type="radio"
                                   name="question_{{ $question->id }}"
                                   value="A">
                            {{ $question->option_a }}
                        </label>

                        <label class="block">
                            <input type="radio"
                                   name="question_{{ $question->id }}"
                                   value="B">
                            {{ $question->option_b }}
                        </label>

                        <label class="block">
                            <input type="radio"
                                   name="question_{{ $question->id }}"
                                   value="C">
                            {{ $question->option_c }}
                        </label>

                        <label class="block">
                            <input type="radio"
                                   name="question_{{ $question->id }}"
                                   value="D">
                            {{ $question->option_d }}
                        </label>

                    </div>

                </div>

            @endforeach

            <button type="submit"
                    class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
                Submit Quiz
            </button>

        </form>

    </div>

</div>

</x-layouts::app>