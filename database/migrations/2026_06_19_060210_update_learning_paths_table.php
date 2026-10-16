<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (used only by the automated tests) cannot add a required
        // column without a default value, so the column is optional there.
        // MySQL keeps the original definition.
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('learning_paths', function (Blueprint $table) use ($isSqlite) {
            $name = $table->string('name')->after('id');

            if ($isSqlite) {
                $name->nullable();
            }

            $table->text('description')
                ->nullable()
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('learning_paths', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
            ]);
        });
    }
};