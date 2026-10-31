<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_category', function (Blueprint $table): void {
            $table->decimal('entry_fee', 10, 2)->nullable()->after('category_id');
        });

        DB::table('event_category')
            ->join('events', 'events.id', '=', 'event_category.event_id')
            ->select('event_category.event_id', 'event_category.category_id', 'events.entry_fee')
            ->get()
            ->each(function (object $row): void {
                DB::table('event_category')
                    ->where('event_id', $row->event_id)
                    ->where('category_id', $row->category_id)
                    ->update(['entry_fee' => $row->entry_fee]);
            });
    }

    public function down(): void
    {
        Schema::table('event_category', function (Blueprint $table): void {
            $table->dropColumn('entry_fee');
        });
    }
};