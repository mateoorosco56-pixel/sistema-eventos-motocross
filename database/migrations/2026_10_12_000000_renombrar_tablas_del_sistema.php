<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('events', 'eventos');
        Schema::rename('categories', 'categorias');
        Schema::rename('participants', 'participantes');
        Schema::rename('registrations', 'inscripciones');
        Schema::rename('organizer_requests', 'solicitudes_organizador');
        Schema::rename('event_category', 'categoria_evento');
        Schema::rename('event_prizes', 'premios_evento');
        Schema::rename('event_starting_orders', 'ordenes_salida_evento');
        Schema::rename('circuits', 'circuitos');

        DB::table('notifications')->where('type', 'App\\Notifications\\EventPublished')
            ->update(['type' => 'App\\Notifications\\EventoPublicado']);
        DB::table('notifications')->where('type', 'App\\Notifications\\NewRegistrationRequest')
            ->update(['type' => 'App\\Notifications\\NuevaSolicitudInscripcion']);
        DB::table('notifications')->where('type', 'App\\Notifications\\ResultPublished')
            ->update(['type' => 'App\\Notifications\\ResultadoPublicado']);
    }

    public function down(): void
    {
        DB::table('notifications')->where('type', 'App\\Notifications\\EventoPublicado')
            ->update(['type' => 'App\\Notifications\\EventPublished']);
        DB::table('notifications')->where('type', 'App\\Notifications\\NuevaSolicitudInscripcion')
            ->update(['type' => 'App\\Notifications\\NewRegistrationRequest']);
        DB::table('notifications')->where('type', 'App\\Notifications\\ResultadoPublicado')
            ->update(['type' => 'App\\Notifications\\ResultPublished']);

        Schema::rename('circuitos', 'circuits');
        Schema::rename('ordenes_salida_evento', 'event_starting_orders');
        Schema::rename('premios_evento', 'event_prizes');
        Schema::rename('categoria_evento', 'event_category');
        Schema::rename('solicitudes_organizador', 'organizer_requests');
        Schema::rename('inscripciones', 'registrations');
        Schema::rename('participantes', 'participants');
        Schema::rename('categorias', 'categories');
        Schema::rename('eventos', 'events');
    }
};
