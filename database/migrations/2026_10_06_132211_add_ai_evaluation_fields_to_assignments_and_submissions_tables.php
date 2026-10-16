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
        /*
        |--------------------------------------------------------------------------
        | ASSIGNMENTS
        |--------------------------------------------------------------------------
        */

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('submission_type')
                ->default('essay')
                ->after('description');

            $table->text('evaluation_criteria')
                ->nullable()
                ->after('submission_type');

            $table->string('programming_language')
                ->nullable()
                ->after('evaluation_criteria');

            $table->boolean('ai_evaluation_enabled')
                ->default(false)
                ->after('programming_language');
        });

        /*
        |--------------------------------------------------------------------------
        | SUBMISSIONS
        |--------------------------------------------------------------------------
        */

        Schema::table('submissions', function (Blueprint $table) {
            $table->unsignedInteger('ai_score')
                ->nullable()
                ->after('score');

            $table->text('ai_feedback')
                ->nullable()
                ->after('feedback');

            $table->text('ai_strengths')
                ->nullable()
                ->after('ai_feedback');

            $table->text('ai_improvements')
                ->nullable()
                ->after('ai_strengths');

            $table->string('ai_evaluation_status')
                ->default('not_requested')
                ->after('ai_improvements');

            $table->timestamp('ai_evaluated_at')
                ->nullable()
                ->after('ai_evaluation_status');

            $table->longText('execution_output')
                ->nullable()
                ->after('ai_evaluated_at');

            $table->longText('execution_error')
                ->nullable()
                ->after('execution_output');

            $table->string('execution_status')
                ->nullable()
                ->after('execution_error');

            $table->string('code_language')
                ->nullable()
                ->after('execution_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'ai_score',
                'ai_feedback',
                'ai_strengths',
                'ai_improvements',
                'ai_evaluation_status',
                'ai_evaluated_at',
                'execution_output',
                'execution_error',
                'execution_status',
                'code_language',
            ]);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'submission_type',
                'evaluation_criteria',
                'programming_language',
                'ai_evaluation_enabled',
            ]);
        });
    }
};