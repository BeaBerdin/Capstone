<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('certificates', function (Blueprint $table) {
        $table->id();

        $table->foreignId('student_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->foreignId('course_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->string('certificate_number')->unique();

        $table->date('issued_date');

        $table->string('certificate_file')->nullable();

        $table->enum('status', [
            'issued',
            'revoked'
        ])->default('issued');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
