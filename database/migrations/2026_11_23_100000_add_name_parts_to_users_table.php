<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| First name, middle initial and last name as separate fields.
|
| ADDITIVE ONLY: three new nullable columns. The existing `name` column stays
| and is still filled as "First M. Last" for every screen that shows a name.
| Existing users keep their current name; their new columns stay null until
| their name is edited.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('middle_initial', 5)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_initial');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_initial', 'last_name']);
        });
    }
};
