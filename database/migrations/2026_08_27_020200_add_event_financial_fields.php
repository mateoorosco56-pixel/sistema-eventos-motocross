<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'entry_fee')) {
            Schema::table('events', function (Blueprint $table) {
                $table->decimal('entry_fee', 10, 2)->nullable()->after('location');
            });
        }

        if (! Schema::hasColumn('events', 'max_participants')) {
            Schema::table('events', function (Blueprint $table) {
                $table->integer('max_participants')->nullable()->after('entry_fee');
            });
        }
    }

    public function down(): void
    {
        $columns = [];

        if (Schema::hasColumn('events', 'entry_fee')) {
            $columns[] = 'entry_fee';
        }

        if (Schema::hasColumn('events', 'max_participants')) {
            $columns[] = 'max_participants';
        }

        if ($columns) {
            Schema::table('events', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
