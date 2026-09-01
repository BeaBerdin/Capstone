<x-layouts::app :title="__('Generate Recommendation')">

<h1 class="text-2xl font-bold mb-6">
    Generate AI Recommendation
</h1>

@if(session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

<form action="{{ route('ai-recommendations.store') }}"
      method="POST"
      class="space-y-4">

    @csrf

    <div>
        <label class="block font-medium">
            Student
        </label>

        <select name="student_id"
                class="border rounded w-full p-2"
                required>

            <option value="">
                Select Student
            </option>

            @foreach($students as $student)
                <option value="{{ $student->id }}">
                    {{ $student->name }}
                </option>
            @endforeach

        </select>
    </div>

    <div class="bg-blue-50 text-blue-700 p-4 rounded">
        The system will analyze the selected student's quiz results and automatically recommend a course based on performance.
    </div>

    <button type="submit"
            class="bg-green-600 text-white px-4 py-2 rounded">
        Generate Recommendation
    </button>

</form>

</x-layouts::app>