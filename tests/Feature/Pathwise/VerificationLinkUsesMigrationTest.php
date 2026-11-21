<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Notification::fake();
});

test('verification link use migration refuses rollback before deleting replay protection', function () {
    $user = pathwiseUser('student');
    $linkHash = hash('sha256', 'used-signed-link');

    DB::table('verification_link_uses')->insert([
        'user_id' => $user->id,
        'link_hash' => $linkHash,
        'used_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_11_15_000002_create_verification_link_uses_table.php');
    $db = DB::connection();
    $db->enableQueryLog();
    $db->flushQueryLog();

    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'forward-only');
    $rollbackQueries = $db->getQueryLog();

    expect($rollbackQueries)->toBe([])
        ->and(DB::table('verification_link_uses')->where('link_hash', $linkHash)->exists())->toBeTrue()
        ->and(Schema::hasTable('verification_link_uses'))->toBeTrue();
});
