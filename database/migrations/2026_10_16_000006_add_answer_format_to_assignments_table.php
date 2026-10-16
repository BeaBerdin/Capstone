<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| How students may answer an essay or code assignment: typed text/pasted
| code only ("text"), a file upload only ("file"), or either ("either").
| Existing assignments get "either", which is how they already worked.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assignments', 'answer_format')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('answer_format', 20)
                ->default('either')
                ->after('submission_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('assignments', 'answer_format')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('answer_format');
        });
    }
};
