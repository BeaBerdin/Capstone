<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Department short code (e.g. "CCS"), shown in the admin dashboard greeting.
|
| ADDITIVE ONLY: one new nullable column. Existing departments keep a null
| code (the full name is used until a code is set). Unique when set: a
| unique index allows several NULLs on MySQL/MariaDB and SQLite.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
