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
        Schema::create('campeonatos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->unsignedInteger('total_rounds')->default(1);
            $table->string('status')->default('programado');
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->foreignId('championship_id')->nullable()->constrained('campeonatos')->nullOnDelete();
            $table->unsignedInteger('round_number')->nullable()->after('championship_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('championship_id');
            $table->dropColumn('round_number');
        });

        Schema::dropIfExists('campeonatos');
    }
};
