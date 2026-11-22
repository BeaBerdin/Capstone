<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Verification links already used to sign a student in on another browser
| (App\Support\VerificationLogin).
|
| One row per USED link. Only a SHA-256 hash of the link's signature is
| stored, never the signed URL or the signature itself. The unique index is
| what makes each link single-use: of two simultaneous attempts, only one
| insert succeeds. Kept in the database (not the cache) so clearing the
| cache (`php artisan optimize:clear`, `cache:clear`) never makes a used link
| usable again.
|
| Different links for the same account have different signatures, so each
| is consumed independently; there is no per-account limit.
|
| New table only: no existing table or row is changed. Rolling back drops
| this table; the code that reads it must be reverted with it (otherwise the
| sign-in from another browser is refused, and the student logs in normally).
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_link_uses', function (Blueprint $table) {
            $table->id();

            // Accounts are deactivated, never deleted (as for
            // student_applications): the database refuses to delete an
            // account that has used a verification link.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // hex SHA-256 of the link's `signature` query value.
            $table->char('link_hash', 64)->unique();

            $table->timestamp('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_link_uses');
    }
};
