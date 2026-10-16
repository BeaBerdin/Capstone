<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded: some databases already added this column through a
        // duplicate migration (2026_09_14_050113) from another branch.
        if (Schema::hasColumn('users', 'department_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('id')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'department_id')) {
            return;
        }

        $hasForeignKey = collect(Schema::getForeignKeys('users'))
            ->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['department_id']);

        Schema::table('users', function (Blueprint $table) use ($hasForeignKey) {
            if ($hasForeignKey) {
                $table->dropForeign(['department_id']);
            }

            $table->dropColumn('department_id');
        });
    }
};