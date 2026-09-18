<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\OrganizerRequestController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\HomeController;
use App\Models\Category;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', function () {
    $role = Auth::user()?->role ?? 'usuario';

    $stats = [
        'events' => Event::count(),
        'categories' => Category::count(),
        'participants' => Participant::count(),
        'registrations' => Registration::count(),
        'upcomingEvents' => Event::where('status', 'programado')->orderBy('event_date')->take(5)->get(),
    ];

    return match ($role) {
        'administrador' => view('dashboard', $stats),
        'organizador' => view('dashboard.organizador-panel', [
            'events' => $stats['events'],
            'pendingRegistrations' => Registration::where('status', 'pendiente')->count(),
            'participants' => $stats['participants'],
            'updatedResults' => Registration::where(function ($query) {
                $query->whereNotNull('result_position')
                    ->orWhereNotNull('result_points');
            })->count(),
            'nextEvents' => Event::where('status', 'programado')->orderBy('event_date')->take(6)->get(),
        ]),
        'piloto' => view('dashboard.piloto-panel', [
            'upcomingEvents' => Event::where('status', 'programado')->count(),
            'activeRegistrations' => Registration::whereIn('status', ['pendiente', 'confirmada'])->count(),
            'resultCount' => Registration::where(function ($query) {
                $query->whereNotNull('result_position')
                    ->orWhereNotNull('result_points');
            })->count(),
            'nextEvents' => Event::where('status', 'programado')->orderBy('event_date')->take(4)->get(),
        ]),
        default => view('dashboard.usuario', [
            'availableEvents' => Event::where('status', 'programado')->count(),
        ]),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/admin', function () {
    return 'Bienvenido al área exclusiva del Administrador AMC.';
})->middleware(['auth', 'role:administrador'])->name('admin');

Route::get('/eventos', [EventController::class, 'index'])->name('eventos.index');
Route::get('/categorias', [CategoryController::class, 'index'])->name('categorias.index');
Route::get('/eventos/{event}', [EventController::class, 'show'])->whereNumber('event')->name('eventos.show');
Route::get('/eventos/{event}/resultados', [EventController::class, 'results'])->whereNumber('event')->name('eventos.resultados');
Route::get('/eventos/{event}/ranking', [EventController::class, 'ranking'])->whereNumber('event')->name('eventos.ranking');

Route::get('/resultados', [ResultController::class, 'index'])->name('resultados.index');

Route::middleware(['auth', 'verified', 'role:organizador,administrador'])->group(function () {
    Route::patch('/resultados/{registration}', [ResultController::class, 'update'])->name('resultados.update');
    Route::delete('/resultados/{registration}', [ResultController::class, 'destroy'])->name('resultados.delete');

    Route::get('/eventos/crear', [EventController::class, 'create'])->name('eventos.create');
    Route::post('/eventos', [EventController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{event}/editar', [EventController::class, 'edit'])->name('eventos.edit');
    Route::patch('/eventos/{event}', [EventController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{event}', [EventController::class, 'destroy'])->name('eventos.destroy');

    Route::get('/categorias/crear', [CategoryController::class, 'create'])->name('categorias.create');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categorias.store');
    Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categorias.edit');
    Route::patch('/categorias/{category}', [CategoryController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])->name('categorias.destroy');

    Route::get('/participantes', [ParticipantController::class, 'index'])->name('participantes.index');
    Route::get('/participantes/crear', [ParticipantController::class, 'create'])->name('participantes.create');
    Route::post('/participantes', [ParticipantController::class, 'store'])->name('participantes.store');
    Route::get('/participantes/{participant}/editar', [ParticipantController::class, 'edit'])->name('participantes.edit');
    Route::patch('/participantes/{participant}', [ParticipantController::class, 'update'])->name('participantes.update');
    Route::delete('/participantes/{participant}', [ParticipantController::class, 'destroy'])->name('participantes.destroy');

    Route::get('/inscripciones', [RegistrationController::class, 'index'])->name('inscripciones.index');
    Route::get('/inscripciones/crear', [RegistrationController::class, 'create'])->name('inscripciones.create');
    Route::post('/inscripciones', [RegistrationController::class, 'store'])->name('inscripciones.store');
    Route::get('/inscripciones/{registration}/editar', [RegistrationController::class, 'edit'])->name('inscripciones.edit');
    Route::patch('/inscripciones/{registration}', [RegistrationController::class, 'update'])->name('inscripciones.update');
    Route::delete('/inscripciones/{registration}', [RegistrationController::class, 'destroy'])->name('inscripciones.destroy');
    Route::patch('/inscripciones/{registration}/pago', [RegistrationController::class, 'validatePayment'])->name('inscripciones.validate-payment');
    Route::get('/inscripciones/{registration}/comprobante', [RegistrationController::class, 'downloadPaymentProof'])->name('inscripciones.payment-proof');
});

Route::middleware(['auth', 'verified', 'role:piloto'])->group(function () {
    Route::get('/mis-eventos', [EventController::class, 'myEvents'])->name('piloto.mis-eventos');
    Route::get('/eventos/programados', [EventController::class, 'programados'])->name('eventos.programados');
    Route::get('/mis-inscripciones/{registration}', [RegistrationController::class, 'show'])->name('piloto.inscripciones.show');
    Route::delete('/mis-inscripciones/{registration}', [RegistrationController::class, 'cancel'])->name('piloto.inscripciones.cancel');
    Route::post('/mis-inscripciones/{registration}/comprobante', [RegistrationController::class, 'submitPayment'])->name('piloto.inscripciones.payment');
    Route::get('/mis-inscripciones/{registration}/comprobante/ver', [RegistrationController::class, 'viewPaymentProof'])->name('piloto.inscripciones.payment-proof');
});

Route::middleware(['auth', 'verified', 'role:organizador,administrador'])->group(function () {
    Route::get('/organizador/mis-eventos', [EventController::class, 'organizerEvents'])->name('organizador.mis-eventos');
});

Route::middleware(['auth', 'verified', 'role:usuario,piloto'])->group(function () {
    Route::get('/solicitud-organizador', [OrganizerRequestController::class, 'create'])->name('organizer-requests.create');
    Route::post('/solicitud-organizador', [OrganizerRequestController::class, 'store'])->name('organizer-requests.store');
});

Route::middleware(['auth', 'verified', 'role:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/usuarios/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/usuarios/{user}/documento', [AdminUserController::class, 'viewDocument'])->name('users.document');
    Route::get('/usuarios/{user}/selfie', [AdminUserController::class, 'viewSelfie'])->name('users.selfie');
    Route::get('/solicitudes-organizador', [OrganizerRequestController::class, 'index'])->name('organizer-requests.index');
    Route::patch('/solicitudes-organizador/{organizerRequest}', [OrganizerRequestController::class, 'update'])->name('organizer-requests.update');
});

Route::middleware(['auth', 'verified', 'role:piloto,organizador,administrador'])->group(function () {
    Route::get('/inscripciones', [RegistrationController::class, 'index'])->name('inscripciones.index');
    Route::get('/inscripciones/crear', [RegistrationController::class, 'create'])->name('inscripciones.create');
    Route::post('/inscripciones', [RegistrationController::class, 'store'])->name('inscripciones.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/notificaciones/{notification}/leer', [RegistrationController::class, 'readNotification'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/verification', [ProfileController::class, 'storeVerification'])->name('profile.verification');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';