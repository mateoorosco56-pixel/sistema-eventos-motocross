<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntuaciones_campeonato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categorias')->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('points');
            $table->timestamps();

            $table->unique(['championship_id', 'category_id', 'round_number', 'position'], 'campeonato_puntuacion_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntuaciones_campeonato');
    }
};
