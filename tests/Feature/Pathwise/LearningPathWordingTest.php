<?php

/*
| Learning paths are rule-based (quiz performance, category, difficulty
| order), not AI: the student page must not call them AI-powered.
*/

beforeEach(function () {
    $this->withoutVite();
});

test('the learning paths page describes them as personalized, not AI-powered', function () {
    $html = $this->actingAs(pathwiseUser('student'))
        ->get(route('student.learning-paths'))
        ->assertOk()
        ->assertSee('Generate your first personalized learning path to receive a structured course sequence based on your quiz performance.')
        ->getContent();

    // Only the main content: the sidebar legitimately links to "AI Recommendations".
    preg_match('/<main\b.*<\/main>/s', $html, $main);
    $content = $main[0] ?? $html;

    expect($content)->not->toContain('AI-powered')
        ->and($content)->not->toContain('AI-generated')
        ->and($content)->not->toContain('Gemini');
});
