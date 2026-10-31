<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_starting_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->time('scheduled_time')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['event_id', 'category_id']);
            $table->unique(['event_id', 'position']);
            $table->index(['event_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_starting_orders');
    }
};
