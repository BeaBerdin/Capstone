<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->longText('body')->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['lesson_id', 'position']);
        });
    }

    public function down(): void
    {
        // Dropping the table loses ordered Reading content and private file
        // references. Refuse before issuing any SQL.
        throw new RuntimeException(
            'The Reading lesson sections migration is forward-only. Rollback would discard ordered lesson content and private attachment metadata. Use a forward recovery migration or restore a verified backup.'
        );
    }
};
