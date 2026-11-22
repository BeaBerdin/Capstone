<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add metadata only: never reinterpret or rewrite existing bodies.
        Schema::table('lesson_sections', function (Blueprint $table) {
            $table->string('body_format', 16)->default('plain');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'The Reading body format migration is forward-only. Removing the format marker would lose the distinction between literal text and rich HTML. Use a reviewed forward recovery migration or restore a verified backup.'
        );
    }
};
