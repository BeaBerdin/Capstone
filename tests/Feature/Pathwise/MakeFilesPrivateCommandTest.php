<?php

use Illuminate\Support\Facades\Storage;

/*
| A11: `php artisan files:make-private` moves receipts, submissions and
| lesson files from the public disk to the private disk. It copies, checks
| size and SHA-256, and only then deletes the public copy.
*/

function publicFilesBeforeA11(): void
{
    Storage::disk('public')->put('payment-proofs/receipt.png', 'RECEIPT');
    Storage::disk('public')->put('assignment-submissions/4/Main.java', 'class Main {}');
    Storage::disk('public')->put('lesson-files/2/notes.pdf', '%PDF-1.4 notes');

    // Public files that stay public.
    Storage::disk('public')->put('courses/thumb.png', 'THUMBNAIL');
    Storage::disk('public')->put('profile-photos/me.png', 'PHOTO');
}

test('it moves receipts, submissions and lesson files to the private disk', function () {
    fakeFileDisks();
    publicFilesBeforeA11();

    $this->artisan('files:make-private')
        ->expectsTable(['Result', 'Files'], [
            ['moved', 3],
            ['copied', 0],
            ['already private', 0],
            ['conflicts', 0],
            ['failed', 0],
        ])
        ->assertSuccessful();

    foreach (['payment-proofs/receipt.png', 'assignment-submissions/4/Main.java', 'lesson-files/2/notes.pdf'] as $path) {
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    expect(Storage::disk('local')->get('assignment-submissions/4/Main.java'))->toBe('class Main {}');

    Storage::disk('public')->assertExists('courses/thumb.png');
    Storage::disk('public')->assertExists('profile-photos/me.png');
    Storage::disk('local')->assertMissing('courses/thumb.png');
});

test('--dry-run changes nothing', function () {
    fakeFileDisks();
    publicFilesBeforeA11();

    $this->artisan('files:make-private --dry-run')
        ->expectsOutputToContain('Dry run')
        ->expectsTable(['Result', 'Files'], [
            ['moved', 3],
            ['copied', 0],
            ['already private', 0],
            ['conflicts', 0],
            ['failed', 0],
        ])
        ->assertSuccessful();

    Storage::disk('public')->assertExists('payment-proofs/receipt.png');
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('--keep-public copies and keeps the public copies; a later run removes them', function () {
    fakeFileDisks();
    publicFilesBeforeA11();

    $this->artisan('files:make-private --keep-public')
        ->expectsTable(['Result', 'Files'], [
            ['moved', 0],
            ['copied', 3],
            ['already private', 0],
            ['conflicts', 0],
            ['failed', 0],
        ])
        ->assertSuccessful();

    Storage::disk('local')->assertExists('lesson-files/2/notes.pdf');
    Storage::disk('public')->assertExists('lesson-files/2/notes.pdf');

    // Second run: the copies are verified identical, so the public ones go.
    $this->artisan('files:make-private')
        ->expectsTable(['Result', 'Files'], [
            ['moved', 0],
            ['copied', 0],
            ['already private', 3],
            ['conflicts', 0],
            ['failed', 0],
        ])
        ->assertSuccessful();

    Storage::disk('local')->assertExists('lesson-files/2/notes.pdf');
    Storage::disk('public')->assertMissing('lesson-files/2/notes.pdf');
});

test('running it twice is safe', function () {
    fakeFileDisks();
    publicFilesBeforeA11();

    $this->artisan('files:make-private')->assertSuccessful();

    $this->artisan('files:make-private')
        ->expectsTable(['Result', 'Files'], [
            ['moved', 0],
            ['copied', 0],
            ['already private', 0],
            ['conflicts', 0],
            ['failed', 0],
        ])
        ->assertSuccessful();

    expect(Storage::disk('local')->get('payment-proofs/receipt.png'))->toBe('RECEIPT');
});

test('a different file already on the private disk is never overwritten', function () {
    fakeFileDisks();
    publicFilesBeforeA11();

    Storage::disk('local')->put('payment-proofs/receipt.png', 'A DIFFERENT RECEIPT');

    $this->artisan('files:make-private')
        ->expectsOutputToContain('Conflict, skipped: payment-proofs/receipt.png')
        ->expectsTable(['Result', 'Files'], [
            ['moved', 2],
            ['copied', 0],
            ['already private', 0],
            ['conflicts', 1],
            ['failed', 0],
        ])
        ->assertFailed();

    // Both copies are kept as they were.
    expect(Storage::disk('local')->get('payment-proofs/receipt.png'))->toBe('A DIFFERENT RECEIPT')
        ->and(Storage::disk('public')->get('payment-proofs/receipt.png'))->toBe('RECEIPT');
});

test('a same-size file with different contents is a conflict', function () {
    fakeFileDisks();

    Storage::disk('public')->put('lesson-files/2/notes.txt', 'AAAA');
    Storage::disk('local')->put('lesson-files/2/notes.txt', 'BBBB');

    $this->artisan('files:make-private')->assertFailed();

    expect(Storage::disk('local')->get('lesson-files/2/notes.txt'))->toBe('BBBB');
    Storage::disk('public')->assertExists('lesson-files/2/notes.txt');
});
