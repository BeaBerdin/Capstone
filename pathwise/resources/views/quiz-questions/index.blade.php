<x-layouts::app :title="__('Quiz Questions')">

<div class="flex justify-between items-center mb-6">

    <h1 class="text-2xl font-bold">
        Quiz Questions
    </h1>

    <a href="{{ route('quiz-questions.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded">
        Add Question
    </a>

</div>

@if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="overflow-x-auto">

<table class="w-full border">

    <thead>
        <tr>
            <th class="border p-2">Quiz</th>
            <th class="border p-2">Question</th>
            <th class="border p-2">Answer</th>
            <th class="border p-2">Points</th>
            <th class="border p-2">Actions</th>
        </tr>
    </thead>

    <tbody>

        @foreach($questions as $question)

        <tr>
            <td class="border p-2">
                {{ $question->quiz->title }}
            </td>

            <td class="border p-2">
                {{ $question->question }}
            </td>

            <td class="border p-2">
                {{ $question->correct_answer }}
            </td>

            <td class="border p-2">
                {{ $question->points }}
            </td>

            <td class="border p-2">

                <a href="{{ route('quiz-questions.edit',$question) }}"
                   class="text-blue-500">
                    Edit
                </a>

                <form action="{{ route('quiz-questions.destroy',$question) }}"
                      method="POST"
                      class="inline">

                    @csrf
                    @method('DELETE')

                    <button class="text-red-500">
                        Delete
                    </button>

                </form>

            </td>
        </tr>

        @endforeach

    </tbody>

</table>

</div>

</x-layouts::app>