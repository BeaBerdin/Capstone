<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Remove legacy placeholder tables only when both are empty. Check both
| before dropping either so existing rows block this migration entirely.
| down() recreates the original structure; it cannot restore deleted data.
*/
return new class extends Migration
{
    public function up(): void
    {
        foreach (['payments', 'course_reviews'] as $table) {
            if (Schema::hasTable($table) && Schema::getConnection()->table($table)->exists()) {
                throw new \RuntimeException(
                    "Migration stopped: {$table} contains existing data. Preserve or migrate its rows before removing the table."
                );
            }
        }

        Schema::dropIfExists('payments');
        Schema::dropIfExists('course_reviews');
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('course_reviews')) {
            Schema::create('course_reviews', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
};
