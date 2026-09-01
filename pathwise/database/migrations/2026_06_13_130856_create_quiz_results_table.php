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
    Schema::create('quiz_results', function (Blueprint $table) {
        $table->id();

        $table->foreignId('student_id')
            ->constrained('users')
            ->cascadeOnDelete();

        $table->foreignId('quiz_id')
            ->constrained()
            ->cascadeOnDelete();

        $table->integer('score');

        $table->integer('total_items');

        $table->decimal('percentage', 5, 2);

        $table->enum('remarks', [
            'passed',
            'failed'
        ]);

        $table->integer('attempt_number')->default(1);

        $table->timestamp('completed_at')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
