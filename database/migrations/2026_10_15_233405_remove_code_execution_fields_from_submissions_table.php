<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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