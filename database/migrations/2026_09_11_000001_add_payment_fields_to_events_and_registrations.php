<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('payment_qr_path')->nullable()->after('entry_fee');
            $table->text('payment_instructions')->nullable()->after('payment_qr_path');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable()->after('status');
            $table->string('payment_status')->default('pendiente')->after('payment_proof_path');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['payment_proof_path', 'payment_status', 'payment_submitted_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['payment_qr_path', 'payment_instructions']);
        });
    }
};