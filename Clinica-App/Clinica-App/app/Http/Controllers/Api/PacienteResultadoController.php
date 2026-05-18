<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resultado;
use Illuminate\Http\Request;

class PacienteResultadoController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario autenticado no tiene paciente asociado.',
            ], 404);
        }

        $pacienteId = $usuario->paciente->id;

        $resultados = Resultado::with([
                'detalleOrden.orden.cita',
                'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'detalleOrden.examen.parametrosResultado' => function ($query) {
                    $query->where('estado', 'activo')
                        ->orderBy('orden_visual');
                },
            ])
            ->whereHas('detalleOrden.orden', function ($query) use ($pacienteId) {
                $query->where('paciente_id', $pacienteId);
            })
            ->whereIn('estado', ['finalizado', 'enviado'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $resultados,
        ]);
    }

    public function show(Request $request, string $id)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario autenticado no tiene paciente asociado.',
            ], 404);
        }

        $pacienteId = $usuario->paciente->id;

        $resultado = Resultado::with([
                'detalleOrden.orden.cita',
                'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'detalleOrden.examen.parametrosResultado' => function ($query) {
                    $query->where('estado', 'activo')
                        ->orderBy('orden_visual');
                },
            ])
            ->whereHas('detalleOrden.orden', function ($query) use ($pacienteId) {
                $query->where('paciente_id', $pacienteId);
            })
            ->whereIn('estado', ['finalizado', 'enviado'])
            ->find($id);

        if (!$resultado) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Resultado no encontrado o no pertenece al paciente autenticado.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $resultado,
        ]);
    }
}