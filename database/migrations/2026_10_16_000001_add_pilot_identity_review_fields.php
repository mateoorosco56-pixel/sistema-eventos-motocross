<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 127)->nullable()->after('name');
            $table->string('last_name', 127)->nullable()->after('first_name');
            $table->string('identity_document_number', 30)->nullable()->after('last_name');
            $table->string('verification_method')->nullable()->after('verification_result');
            $table->unsignedBigInteger('verification_reviewed_by')->nullable()->after('verified_at');
            $table->timestamp('verification_reviewed_at')->nullable()->after('verification_reviewed_by');
            $table->text('verification_rejection_reason')->nullable()->after('verification_reviewed_at');
            $table->timestamp('identity_processing_consent_at')->nullable()->after('verification_rejection_reason');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_status')->default('pending_review')->change();
        });

        DB::table('users')
            ->where('verification_status', 'pendiente')
            ->update(['verification_status' => 'pending_review']);

        DB::table('users')
            ->where('verification_status', 'verificada')
            ->update([
                'verification_status' => 'pending_review',
                'verification_method' => 'legacy_review_required',
            ]);

        DB::table('users')
            ->where('verification_status', 'rechazada')
            ->update(['verification_status' => 'rejected']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('verification_status', 'pending_review')
            ->update(['verification_status' => 'pendiente']);

        DB::table('users')
            ->where('verification_status', 'verified')
            ->update(['verification_status' => 'verificada']);

        DB::table('users')
            ->where('verification_status', 'rejected')
            ->update(['verification_status' => 'rechazada']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_status')->default('pendiente')->change();
            $table->dropColumn([
                'first_name',
                'last_name',
                'identity_document_number',
                'verification_method',
                'verification_reviewed_by',
                'verification_reviewed_at',
                'verification_rejection_reason',
                'identity_processing_consent_at',
            ]);
        });
    }
};
