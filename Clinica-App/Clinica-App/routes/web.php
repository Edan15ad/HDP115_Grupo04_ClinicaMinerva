<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ControladorUsuarioRegistrado;
use App\Http\Controllers\Api\PacienteExamenController;
use App\Http\Controllers\Api\PacientePerfilController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\LaboratorioResultadoController;
use App\Http\Controllers\Api\PacienteResultadoController;
use App\Http\Controllers\Api\EnvioCorreoController;
use App\Http\Controllers\Api\CambiarPasswordController; // <-- NUEVO

// Pantalla de Bienvenida
Route::get('/', function () {
    return inertia('Welcome');
})->name('home');

// Rutas de Registro (Solo Invitados)
Route::middleware('guest')->group(function () {
    Route::get('/register', [ControladorUsuarioRegistrado::class, 'create'])->name('register');
    Route::post('/register', [ControladorUsuarioRegistrado::class, 'store']);
});

// Rutas Protegidas
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');

    Route::get('/paciente/mis-examenes', function () {
        return inertia('Paciente/MisExamenes');
    })->name('paciente.mis-examenes');

    // API interna para el módulo del paciente
    Route::get('/api/paciente/mis-examenes', [PacienteExamenController::class, 'misExamenes'])
        ->name('api.paciente.mis-examenes');

    Route::get('/api/paciente/examenes-disponibles', [PacienteExamenController::class, 'examenesDisponibles'])
        ->name('api.paciente.examenes-disponibles');

    Route::get('/api/paciente/horarios-disponibles', [PacienteExamenController::class, 'horariosDisponibles'])
        ->name('api.paciente.horarios-disponibles');

    Route::post('/api/paciente/solicitar-examen', [PacienteExamenController::class, 'solicitarExamen'])
        ->name('api.paciente.solicitar-examen');

    Route::get('/api/paciente/perfil', [PacientePerfilController::class, 'show'])
        ->name('api.paciente.perfil.show');

    Route::put('/api/paciente/perfil', [PacientePerfilController::class, 'update'])
        ->name('api.paciente.perfil.update');

    // NUEVO: Cambiar contraseña (disponible para todos los roles)
    Route::put('/api/usuario/cambiar-password', [CambiarPasswordController::class, 'update'])
        ->name('api.usuario.cambiar-password');

    Route::put('/api/recepcion/citas/{id}/muestra-tomada', [CitaController::class, 'marcarMuestraTomada'])
        ->name('api.recepcion.citas.muestra-tomada');

    Route::get('/api/laboratorio/resultados-pendientes', [LaboratorioResultadoController::class, 'pendientes'])
        ->name('api.laboratorio.resultados-pendientes');

    Route::get('/api/laboratorio/resultados-formulario/{detalleOrdenId}', [LaboratorioResultadoController::class, 'formulario'])
        ->name('api.laboratorio.resultados-formulario');

    Route::post('/api/laboratorio/resultados', [LaboratorioResultadoController::class, 'store'])
        ->name('api.laboratorio.resultados.store');

    Route::get('/api/paciente/resultados', [PacienteResultadoController::class, 'index'])
        ->name('api.paciente.resultados.index');

    Route::get('/api/paciente/resultados/{id}', [PacienteResultadoController::class, 'show'])
        ->name('api.paciente.resultados.show');

    Route::post('/api/correos/reenviar/{resultadoId}', [EnvioCorreoController::class, 'reenviar'])
        ->name('api.correos.reenviar');
});

require __DIR__.'/settings.php';