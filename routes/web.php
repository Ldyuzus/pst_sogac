<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminRequisitoController;
use App\Http\Controllers\AdminTipoSolicitudController;
use App\Http\Controllers\PreguntasFrecuentesController;
use App\Http\Controllers\UserSolicitudController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;


Route::get('/', function () {
    return view('app');
});

// ============================================================
// LOGIN / LOGOUT
// El LoginController ya existía completo, pero no tenía ninguna
// ruta apuntándole todavía. Sin esto, Auth::id() nunca sabría
// quién es el estudiante al momento de crear una solicitud.
// ============================================================
Route::get('/login', [LoginController::class, 'mostrarFormulario'])->name('login');
Route::post('/login', [LoginController::class, 'procesarLogin'])->name('login.post');
Route::post('/logout', [LoginController::class, 'cerrarSesion'])->name('logout');

// ============================================================
// GRUPO ESTUDIANTE
// La ruta original apuntaba a view('user.index'), que no existe
// (el archivo real es user.dashboard) — por eso tiraba error.
// Ahora sí pasa por el controlador, que filtra los trámites
// disponibles y arma el formulario de cada uno.
// ============================================================
Route::middleware('auth')->prefix('user')->group(function () {
    Route::get('/dashboard', [UserSolicitudController::class, 'index'])->name('dashboard');
    Route::get('/tramites/{id}/solicitar', [UserSolicitudController::class, 'create'])->name('user.tramites.solicitar');
    Route::post('/tramites/{id}/solicitar', [UserSolicitudController::class, 'store'])->name('user.tramites.store');
});

// ============================================================
// GRUPO ADMIN
// Nota: El panel queda SIN autenticación local porque el acceso
// se controlará con el doble inicio de sesión de la universidad
// (SSO) una vez el sistema esté en producción. Si se necesita
// proteger, basta con añadir ->middleware(['auth', 'admin'])
// al grupo de abajo y crear las rutas de login/logout.
// ============================================================
Route::prefix('admin')->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/estado/{id}/{accion}', [AdminDashboardController::class, 'cambiarEstado'])->name('admin.dashboard.estado');

    // CRUD completo de requisitos (Crear, Leer, Actualizar, Eliminar)
    Route::resource('requisitos', AdminRequisitoController::class)->names('admin.requisitos');

    // CRUD de los TRÁMITES en sí (tipo_solicitudes): aquí es donde el admin
    // crea el trámite, define su ventana de fechas y le asigna requisitos.
    // ->except(['show']): no existe (ni hace falta) una pantalla de "ver un
    // solo trámite" separada de "editar" — sin esto, entrar a
    // /admin/tipos-solicitud/{id} directo tiraba un error 500.
    Route::resource('tipos-solicitud', AdminTipoSolicitudController::class)
        ->except(['show'])
        ->names('admin.tipos-solicitud');
    Route::patch('/tipos-solicitud/{id}/alternar-estado', [AdminTipoSolicitudController::class, 'alternarEstado'])
        ->name('admin.tipos-solicitud.alternar-estado');
});

// Soporte / Preguntas frecuentes
Route::resource('soporte', PreguntasFrecuentesController::class);

// Rutas de Registro
Route::get('/register', [RegisterController::class, 'mostrarFormulario'])->name('register');
Route::post('/register', [RegisterController::class, 'registrar'])->name('register.post');
// LOGIN

// TEMPORAL VVV
Route::get('/prueba-chat-usuario', function () {
    $hilo = (object) [
        'hch_id' => 1,
        'hch_estado' => 'pendiente_cierre',
        'hch_etiqueta_tema' => null
    ];
    return view('soporte\chat_usuario', compact('hilo'));
});
//LOGOUT RAPIDO DE DEPURACION


Route::get('/prueba-chat-admin', function () {
    $hilo = (object) [
        'hch_id' => 1,
        'hch_estado' => 'activo',
    ];
    return view('soporte\chat_admin', compact('hilo'));
});
// TEMPORAL ^^^

