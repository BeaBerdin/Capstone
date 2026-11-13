<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Even empty strings are stored values: only NULL is safe to discard.
        foreach (['execution_output', 'execution_error', 'execution_status', 'code_language'] as $column) {
            if (Schema::getConnection()->table('submissions')->whereNotNull($column)->exists()) {
                throw new \RuntimeException(
                    "Migration stopped: submissions.{$column} contains existing data. Preserve it before removing the column."
                );
            }
        }

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'execution_output',
                'execution_error',
                'execution_status',
                'code_language',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->longText('execution_output')->nullable();
            $table->longText('execution_error')->nullable();
            $table->string('execution_status')->nullable();
            $table->string('code_language')->nullable();
        });
    }
};
