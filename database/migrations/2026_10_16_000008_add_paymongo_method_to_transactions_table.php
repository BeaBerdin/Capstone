<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| The method the student actually used in PayMongo checkout (gcash, paymaya
| or card), shown as e.g. "Maya via PayMongo". payment_method stays
| "PayMongo" because the payment flow checks that value. Older transactions
| keep NULL here and keep showing "PayMongo".
*/
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transactions', 'paymongo_method')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('paymongo_method', 20)
                ->nullable()
                ->after('payment_method');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transactions', 'paymongo_method')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('paymongo_method');
        });
    }
};
