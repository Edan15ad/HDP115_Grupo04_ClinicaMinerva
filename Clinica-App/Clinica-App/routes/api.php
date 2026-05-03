<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\ExamenController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\OrdenController;
use App\Http\Controllers\Api\DetalleOrdenController;
use App\Http\Controllers\Api\ParametroResultadoController;
use App\Http\Controllers\Api\ResultadoController;
use App\Http\Controllers\Api\EnvioCorreoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas base CRUD
Route::apiResource('usuarios', UsuarioController::class);
Route::apiResource('pacientes', PacienteController::class);
Route::apiResource('examenes', ExamenController::class);
Route::apiResource('citas', CitaController::class);
Route::apiResource('ordenes', OrdenController::class);
Route::apiResource('detalle-ordenes', DetalleOrdenController::class);
Route::apiResource('parametros-resultado', ParametroResultadoController::class);
Route::apiResource('resultados', ResultadoController::class);
Route::apiResource('envios-correo', EnvioCorreoController::class);