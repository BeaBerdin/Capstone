<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('code', 32)
                ->unique();

            $table->timestamp('expires_at')
                ->nullable();

            $table->timestamp('accepted_at')
                ->nullable();

            $table->foreignId('accepted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['course_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_invitations');
    }
};