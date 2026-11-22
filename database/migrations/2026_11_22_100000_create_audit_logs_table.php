<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Audit log of sensitive Super Admin actions: role changes, promotions to
| Super Admin, manual email verification, activation/deactivation, name or
| email edits, and EDP application decisions.
|
| ADDITIVE ONLY: a new table. No existing table, column or row is changed.
| Entries are written once and never edited (see App\Models\AuditLog).
| Passwords, tokens and file contents are never stored here.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who acted and on whom. If an account were ever removed, the
            // entry stays and the reference becomes empty.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();

            // e.g. user.role_changed, user.promoted_super_admin
            $table->string('action', 64);

            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();

            // IPv4 or IPv6.
            $table->string('ip_address', 45)->nullable();

            // Explicit default: never "ON UPDATE CURRENT_TIMESTAMP" on MySQL.
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
