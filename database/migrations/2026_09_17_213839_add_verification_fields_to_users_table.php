<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_status')->default('pendiente')->after('role');
            $table->string('document_path')->nullable()->after('verification_status');
            $table->string('selfie_path')->nullable()->after('document_path');
            $table->text('verification_result')->nullable()->after('selfie_path');
            $table->timestamp('verified_at')->nullable()->after('verification_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_status',
                'document_path',
                'selfie_path',
                'verification_result',
                'verified_at',
            ]);
        });
    }
};
