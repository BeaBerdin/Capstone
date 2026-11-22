<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('lesson_type', 20)->default('video')->change();
        });

        if (! Schema::hasColumn('assignments', 'is_activity')) {
            Schema::table('assignments', function (Blueprint $table) {
                $table->boolean('is_activity')->default(false)->after('lesson_id');
            });
        }
    }

    public function down(): void
    {
        // MySQL schema changes can commit implicitly. Refuse before issuing any SQL.
        throw new RuntimeException(
            'The Activity migration cannot be rolled back safely. Use a forward migration that preserves Activity metadata or restore a verified backup as part of a planned recovery.'
        );
    }
};
