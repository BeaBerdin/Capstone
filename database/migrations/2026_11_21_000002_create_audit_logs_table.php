<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Audit log of sensitive Super Admin actions: role changes, manual email
| verification, account activation/deactivation and application decisions.
|
| ADDITIVE ONLY: a new table. No existing table, column or row is changed.
| Rows are written once and never edited (see App\Models\AuditLog). Passwords
| and file contents are never stored here.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who acted and on whom. If an account were ever removed, the
            // log entry stays (the reference becomes empty).
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();

            // e.g. user.role_changed, user.email_verified, user.deactivated
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
