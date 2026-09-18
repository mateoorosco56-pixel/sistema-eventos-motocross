<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        DB::table('users')->where('role', 'piloto')->get(['id', 'email'])->each(function (object $user): void {
            DB::table('registrations')
                ->join('participants', 'participants.id', '=', 'registrations.participant_id')
                ->where('participants.email', $user->email)
                ->whereNull('registrations.user_id')
                ->update(['registrations.user_id' => $user->id]);
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};