<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('registrations')
            ->select('event_id', 'category_id', 'dorsal', DB::raw('MIN(id) as keeper_id'))
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->whereNotNull('category_id')
            ->whereNotNull('dorsal')
            ->groupBy('event_id', 'category_id', 'dorsal')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('registrations')
                ->where('event_id', $duplicate->event_id)
                ->where('category_id', $duplicate->category_id)
                ->where('dorsal', $duplicate->dorsal)
                ->whereIn('status', ['pendiente', 'confirmada'])
                ->where('id', '<>', $duplicate->keeper_id)
                ->update(['status' => 'rechazada']);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE registrations ADD active_dorsal_key VARCHAR(191) GENERATED ALWAYS AS (CASE WHEN status IN ('pendiente', 'confirmada') AND category_id IS NOT NULL AND dorsal IS NOT NULL THEN CONCAT(event_id, ':', category_id, ':', dorsal) ELSE NULL END) STORED");
            Schema::table('registrations', function (Blueprint $table) {
                $table->unique('active_dorsal_key', 'registrations_active_dorsal_unique');
            });

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX registrations_event_category_dorsal_unique ON registrations (event_id, category_id, dorsal) WHERE status IN ('pendiente', 'confirmada') AND category_id IS NOT NULL AND dorsal IS NOT NULL");

            return;
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->unique(['event_id', 'category_id', 'dorsal'], 'registrations_event_category_dorsal_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropUnique('registrations_active_dorsal_unique');
            });
            DB::statement('ALTER TABLE registrations DROP COLUMN active_dorsal_key');

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX registrations_event_category_dorsal_unique');

            return;
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique('registrations_event_category_dorsal_unique');
        });
    }
};
