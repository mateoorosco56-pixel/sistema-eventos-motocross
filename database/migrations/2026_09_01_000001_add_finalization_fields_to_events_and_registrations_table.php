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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('results_published')->default(false)->after('status');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->boolean('participated')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('participated');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('results_published');
        });
    }
};
