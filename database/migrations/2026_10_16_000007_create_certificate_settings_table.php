<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Certificate template settings chosen by the Administrator (title, body
| text, signatory name and position). One row; the certificate PDF reads it.
| The certificates table is not changed, so issued certificates stay valid.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificate_settings')) {
            return;
        }

        Schema::create('certificate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('body_text');
            $table->string('signatory_name', 120);
            $table->string('signatory_position', 120);
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_settings');
    }
};
