<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_category')) {
            Schema::create('event_category', function (Blueprint $table) {
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->primary(['event_id', 'category_id']);
            });
        }

        if (Schema::hasColumn('events', 'category_id')) {
            DB::table('events')
                ->whereNotNull('category_id')
                ->get(['id', 'category_id'])
                ->each(function (object $event): void {
                    DB::table('event_category')->insert([
                        'event_id' => $event->id,
                        'category_id' => $event->category_id,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_category');
    }
};
