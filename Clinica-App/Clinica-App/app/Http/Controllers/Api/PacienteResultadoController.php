<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resultado;
use Illuminate\Support\Facades\Storage;
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

    public function pdf(Request $request, string $id)
{
    $usuario = $request->user();

    if (!$usuario) {
        return response()->json([
            'ok' => false,
            'mensaje' => 'No autenticado.',
        ], 401);
    }

    $resultado = Resultado::with([
        'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
        'detalleOrden.examen',
    ])->find($id);

    if (!$resultado) {
        abort(404, 'Resultado no encontrado.');
    }

    $pacienteResultado = $resultado->detalleOrden?->orden?->paciente;

    $esDuenio = $usuario->rol === 'paciente'
        && $usuario->paciente
        && $pacienteResultado
        && $usuario->paciente->id === $pacienteResultado->id;

    $rolAutorizado = in_array($usuario->rol, [
        'laboratorio',
        'recepcionista',
        'administrador',
    ]);

    if (!$esDuenio && !$rolAutorizado) {
        abort(403, 'No tienes permisos para ver este PDF.');
    }

    if (!$resultado->archivo_pdf) {
        abort(404, 'Este resultado no tiene PDF generado.');
    }

    if (!Storage::disk('public')->exists($resultado->archivo_pdf)) {
        abort(404, 'El archivo PDF no existe en el almacenamiento.');
    }

    return Storage::disk('public')->response(
        $resultado->archivo_pdf,
        'Resultado_' . ($resultado->detalleOrden?->examen?->codigo ?? $resultado->id) . '.pdf',
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Resultado_' . ($resultado->detalleOrden?->examen?->codigo ?? $resultado->id) . '.pdf"',
        ]
    );
}
}