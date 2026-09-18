<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'registration_deadline')) {
            Schema::table('events', function (Blueprint $table) {
                $table->date('registration_deadline')->nullable()->after('event_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'registration_deadline')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('registration_deadline');
            });
        }
    }
};
