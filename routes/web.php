<?php
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CircuitoController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\ParticipanteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\SolicitudOrganizadorController;
use App\Http\Controllers\AdministradorUsuariosController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\InicioController;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('home');
Route::get('/circuitos', [CircuitoController::class, 'publicIndex'])->name('circuitos.index');
Route::get('/circuitos/{circuit}', [CircuitoController::class, 'publicShow'])->whereNumber('circuit')->name('circuitos.show');

Route::get('/dashboard', function () {
    $role = Auth::user()?->role ?? 'usuario';
    $pilotRegistrations = $role === 'piloto'
        ? Inscripcion::query()
            ->with(['event.categories', 'event.category', 'event.circuit', 'category'])
            ->where(function ($query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('participant', function ($participantQuery) {
                        $participantQuery->where('email', Auth::user()->email)
                            ->orWhere('name', Auth::user()->name);
                    });
            })
            ->latest()
            ->get()
        : collect();

    $stats = [
        'events' => Evento::count(),
        'categories' => Categoria::count(),
        'participants' => Participante::count(),
        'registrations' => Inscripcion::count(),
        'upcomingEvents' => Evento::where('status', 'programado')->orderBy('event_date')->take(5)->get(),
    ];

    return match ($role) {
        'administrador' => view('dashboard', $stats),
        'organizador' => view('dashboard.organizador-panel', [
            'events' => $stats['events'],
            'pendingRegistrations' => Inscripcion::where('status', 'pendiente')->count(),
            'participants' => $stats['participants'],
            'updatedResults' => Inscripcion::where(function ($query) {
                $query->whereNotNull('result_position')
                    ->orWhereNotNull('result_points');
            })->count(),
            'nextEvents' => Evento::where('status', 'programado')->orderBy('event_date')->take(6)->get(),
        ]),
        'piloto' => view('dashboard.piloto-panel', [
            'upcomingEvents' => Evento::where('status', 'programado')->count(),
            'activeRegistrations' => $pilotRegistrations->filter(fn ($registration) =>
                in_array($registration->status, Inscripcion::ACTIVE_STATUSES, true)
                && in_array($registration->event?->status, ['programado', 'en_curso'], true)
            )->count(),
            'resultCount' => $pilotRegistrations->filter(fn ($registration) =>
                $registration->result_position !== null || $registration->result_points !== null
            )->count(),
            'pilotRegistrations' => $pilotRegistrations,
            'nextEvents' => Evento::where('status', 'programado')->orderBy('event_date')->take(4)->get(),
        ]),
        default => view('dashboard.usuario', [
            'availableEvents' => Evento::where('status', 'programado')->count(),
        ]),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin', function () {
    return 'Bienvenido al área exclusiva del Administrador AMC.';
})->middleware(['auth', 'role:administrador'])->name('admin');

Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
Route::get('/eventos/{event}', [EventoController::class, 'show'])->whereNumber('event')->name('eventos.show');
Route::get('/eventos/{event}/resultados', [EventoController::class, 'results'])->whereNumber('event')->name('eventos.resultados');
Route::get('/eventos/{event}/ranking', [EventoController::class, 'ranking'])->whereNumber('event')->name('eventos.ranking');

Route::get('/resultados', [ResultadoController::class, 'index'])->name('resultados.index');

Route::middleware(['auth', 'verified', 'role:organizador,administrador'])->group(function () {
    Route::patch('/resultados/{registration}', [ResultadoController::class, 'update'])->name('resultados.update');
    Route::delete('/resultados/{registration}', [ResultadoController::class, 'destroy'])->name('resultados.delete');

    Route::get('/eventos/crear', [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{event}/editar', [EventoController::class, 'edit'])->name('eventos.edit');
    Route::patch('/eventos/{event}', [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{event}', [EventoController::class, 'destroy'])->name('eventos.destroy');

    Route::get('/categorias/crear', [CategoriaController::class, 'create'])->name('categorias.create');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::get('/categorias/{category}/editar', [CategoriaController::class, 'edit'])->name('categorias.edit');
    Route::patch('/categorias/{category}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{category}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    Route::get('/participantes', [ParticipanteController::class, 'index'])->name('participantes.index');
    Route::get('/participantes/crear', [ParticipanteController::class, 'create'])->name('participantes.create');
    Route::post('/participantes', [ParticipanteController::class, 'store'])->name('participantes.store');
    Route::get('/participantes/{participant}/editar', [ParticipanteController::class, 'edit'])->name('participantes.edit');
    Route::patch('/participantes/{participant}', [ParticipanteController::class, 'update'])->name('participantes.update');
    Route::delete('/participantes/{participant}', [ParticipanteController::class, 'destroy'])->name('participantes.destroy');

    Route::get('/organizador/circuitos', [CircuitoController::class, 'index'])->name('organizador.circuitos.index');
    Route::post('/organizador/circuitos', [CircuitoController::class, 'store'])->name('organizador.circuitos.store');
    Route::delete('/organizador/circuitos/{circuit}', [CircuitoController::class, 'destroy'])->name('organizador.circuitos.destroy');

    Route::get('/inscripciones', [InscripcionController::class, 'index'])->name('inscripciones.index');
    Route::get('/inscripciones/exportar', [InscripcionController::class, 'export'])->name('inscripciones.export');
    Route::get('/inscripciones/crear', [InscripcionController::class, 'create'])->name('inscripciones.create');
    Route::post('/inscripciones', [InscripcionController::class, 'store'])->name('inscripciones.store');
    Route::get('/inscripciones/{registration}/editar', [InscripcionController::class, 'edit'])->name('inscripciones.edit');
    Route::patch('/inscripciones/{registration}', [InscripcionController::class, 'update'])->name('inscripciones.update');
    Route::delete('/inscripciones/{registration}', [InscripcionController::class, 'destroy'])->name('inscripciones.destroy');
    Route::patch('/inscripciones/{registration}/pago', [InscripcionController::class, 'validatePayment'])->name('inscripciones.validate-payment');
    Route::get('/inscripciones/{registration}/comprobante', [InscripcionController::class, 'downloadPaymentProof'])->name('inscripciones.payment-proof');
});

Route::middleware(['auth', 'verified', 'role:piloto'])->group(function () {
    Route::get('/mis-eventos', [EventoController::class, 'myEvents'])->name('piloto.mis-eventos');
    Route::get('/eventos/programados', [EventoController::class, 'programados'])->name('eventos.programados');
    Route::get('/mis-inscripciones/{registration}', [InscripcionController::class, 'show'])->name('piloto.inscripciones.show');
    Route::delete('/mis-inscripciones/{registration}', [InscripcionController::class, 'cancel'])->name('piloto.inscripciones.cancel');
    Route::post('/mis-inscripciones/{registration}/comprobante', [InscripcionController::class, 'submitPayment'])->name('piloto.inscripciones.payment');
    Route::get('/mis-inscripciones/{registration}/comprobante/ver', [InscripcionController::class, 'viewPaymentProof'])->name('piloto.inscripciones.payment-proof');
});

Route::middleware(['auth', 'verified', 'role:organizador,administrador'])->group(function () {
    Route::get('/organizador/mis-eventos', [EventoController::class, 'organizerEvents'])->name('organizador.mis-eventos');
});

Route::middleware(['auth', 'verified', 'role:usuario,piloto'])->group(function () {
    Route::get('/solicitud-organizador', [SolicitudOrganizadorController::class, 'create'])->name('organizer-requests.create');
    Route::post('/solicitud-organizador', [SolicitudOrganizadorController::class, 'store'])->name('organizer-requests.store');
});

Route::middleware(['auth', 'verified', 'role:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', [AdministradorUsuariosController::class, 'index'])->name('users.index');
    Route::patch('/usuarios/{user}', [AdministradorUsuariosController::class, 'update'])->name('users.update');
    Route::delete('/usuarios/{user}', [AdministradorUsuariosController::class, 'destroy'])->name('users.destroy');
    Route::get('/usuarios/{user}/documento', [AdministradorUsuariosController::class, 'viewDocument'])->name('users.document');
    Route::get('/usuarios/{user}/documento/reverso', [AdministradorUsuariosController::class, 'viewDocumentBack'])->name('users.document-back');
    Route::get('/usuarios/{user}/selfie', [AdministradorUsuariosController::class, 'viewSelfie'])->name('users.selfie');
    Route::get('/solicitudes-organizador', [SolicitudOrganizadorController::class, 'index'])->name('organizer-requests.index');
    Route::patch('/solicitudes-organizador/{organizerRequest}', [SolicitudOrganizadorController::class, 'update'])->name('organizer-requests.update');
});

Route::middleware(['auth', 'verified', 'role:piloto,organizador,administrador'])->group(function () {
    Route::get('/inscripciones', [InscripcionController::class, 'index'])->name('inscripciones.index');
    Route::get('/inscripciones/crear', [InscripcionController::class, 'create'])->name('inscripciones.create');
    Route::post('/inscripciones', [InscripcionController::class, 'store'])->name('inscripciones.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/notificaciones', function () {
        $pilot = \App\Models\User::query()->findOrFail(Auth::id());

        return view('notificaciones.index', [
            'notifications' => $pilot->notifications()->latest()->take(4)->get(),
        ]);
    })->middleware(['verified', 'role:piloto'])->name('piloto.notificaciones.index');
    Route::get('/notificaciones/{notification}/leer', [InscripcionController::class, 'readNotification'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/verification', [ProfileController::class, 'storeVerification'])
        ->middleware('throttle:6,1')
        ->name('profile.verification');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// CONFIGURACIÓN TEMPORAL PARA ASIGNAR ADMINISTRADOR

Route::get('/configurar-admin-temporal', function () {
    return view('admin-setup');
})->middleware('throttle:5,1');

Route::post('/configurar-admin-temporal', function (Request $request) {

    $expected = (string) config('app.admin_setup_token');
    $provided = (string) $request->input('token');

    if ($expected === '' || !hash_equals($expected, $provided)) {
        abort(403, 'Clave de configuración incorrecta.');
    }

    $user = User::where('email', 'mateoorosco56@gmail.com')->firstOrFail();

    $user->role = 'administrador';
    $user->save();

    return response(
        'Rol de administrador asignado correctamente. Retira estas rutas temporales.',
        200
    );

})->middleware('throttle:3,1');

require __DIR__.'/auth.php';