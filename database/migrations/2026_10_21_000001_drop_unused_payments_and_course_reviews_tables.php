<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| The payments and course_reviews tables were empty placeholders (only id
| and timestamps) that nothing in the system uses: payments are stored in
| transactions, and course reviews were never built (see the manuscript
| revisions MR-12 and MR-26). down() recreates them exactly as the original
| create migrations did.
*/
return new class extends Migration
{
    public function up(): void
    {
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
