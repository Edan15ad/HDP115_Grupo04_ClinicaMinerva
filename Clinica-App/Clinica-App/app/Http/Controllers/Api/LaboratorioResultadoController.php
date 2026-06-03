<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleOrden;
use App\Models\Resultado;
use App\Models\EnvioCorreo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\ResultadoLaboratorioMail;

class LaboratorioResultadoController extends Controller
{
    private function validarLaboratorista(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || $usuario->rol !== 'laboratorio') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permisos para registrar resultados.',
            ], 403);
        }

        return null;
    }

    public function pendientes(Request $request)
    {
        if ($respuesta = $this->validarLaboratorista($request)) {
            return $respuesta;
        }

        $detalles = DetalleOrden::with([
                'orden.cita',
                'orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'examen.parametrosResultado',
                'resultado',
            ])
            ->where('estado', 'en_proceso')
            ->whereHas('orden', function ($query) {
                $query->where('estado', 'en_laboratorio');
            })
            ->whereDoesntHave('resultado')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $detalles,
        ]);
    }

    public function formulario(Request $request, string $detalleOrdenId)
    {
        if ($respuesta = $this->validarLaboratorista($request)) {
            return $respuesta;
        }

        $detalle = DetalleOrden::with([
                'orden.cita',
                'orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'examen.parametrosResultado' => function ($query) {
                    $query->where('estado', 'activo')
                        ->orderBy('orden_visual');
                },
                'resultado',
            ])
            ->find($detalleOrdenId);

        if (!$detalle) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Detalle de orden no encontrado.',
            ], 404);
        }

        if ($detalle->resultado) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Este examen ya tiene resultado registrado.',
            ], 422);
        }

        if ($detalle->estado !== 'en_proceso') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Este examen no está listo para registrar resultados.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'detalle_orden' => $detalle,
                'parametros' => $detalle->examen->parametrosResultado,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if ($respuesta = $this->validarLaboratorista($request)) {
            return $respuesta;
        }

        $data = $request->validate([
            'detalle_orden_id' => ['required', 'exists:detalle_ordenes,id', 'unique:resultados,detalle_orden_id'],
            'resultado_json' => ['required', 'array'],
            'observaciones_generales' => ['nullable', 'string', 'max:200'],
        ]);

        $detalle = DetalleOrden::with([
                'orden.cita',
                'orden.paciente.usuario',
                'examen.parametrosResultado',
            ])
            ->find($data['detalle_orden_id']);

        if (!$detalle) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Detalle de orden no encontrado.',
            ], 404);
        }

        if ($detalle->estado !== 'en_proceso') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El examen no está en proceso de laboratorio.',
            ], 422);
        }

        $parametros = $detalle->examen->parametrosResultado()
            ->where('estado', 'activo')
            ->get();

        foreach ($parametros as $parametro) {
            if ($parametro->obligatorio && blank($data['resultado_json'][$parametro->nombre_parametro] ?? null)) {
                return response()->json([
                    'ok' => false,
                    'mensaje' => "El campo {$parametro->etiqueta} es obligatorio.",
                ], 422);
            }
        }

        // Transacción de BD y Generación
        $resultado = DB::transaction(function () use ($detalle, $data, $parametros) {
            $resultado = Resultado::create([
                'detalle_orden_id' => $detalle->id,
                'fecha_resultado' => now()->timezone('America/El_Salvador'),
                'resultado_json' => $data['resultado_json'],
                'observaciones_generales' => $data['observaciones_generales'] ?? null,
                'estado' => 'finalizado',
                'correo_enviado' => false,
            ]);

            $detalle->update([
                'estado' => 'finalizado',
            ]);

            $orden = $detalle->orden()->with('detalles')->first();

            $todosFinalizados = $orden->detalles
                ->every(function ($item) {
                    return in_array($item->estado, ['finalizado', 'cancelado']);
                });

            if ($todosFinalizados) {
                $orden->update([
                    'estado' => 'finalizado',
                ]);

                if ($orden->cita) {
                    $orden->cita->update([
                        'estado' => 'finalizada',
                    ]);
                }
            }

            return $resultado;
        });

        // Bloque de Generación PDF
        $paciente = $detalle->orden->paciente;
        $examen = $detalle->examen;

        $pdf = Pdf::loadView('pdf.resultado', [
            'resultado' => $resultado, 
            'paciente' => $paciente, 
            'examen' => $examen,
            'parametros' => $parametros
        ]);
        
        // Se guarda en storage/app/public/resultados/
        $fileName = 'resultados/resultado_' . $resultado->id . '_' . time() . '.pdf';
        Storage::disk('public')->put($fileName, $pdf->output());
        
        $resultado->update(['archivo_pdf' => $fileName]);

        // Bloque de Envío de Correo
        $envio = EnvioCorreo::create([
            'resultado_id' => $resultado->id,
            'correo_destino' => $paciente->usuario->correo,
            'estado_envio' => 'pendiente',
        ]);

        try {
            Mail::to($paciente->usuario->correo)->send(new ResultadoLaboratorioMail($paciente, $examen, $fileName));
            
            $resultado->update([
                'estado' => 'enviado', 
                'correo_enviado' => true, 
                'fecha_envio_correo' => now()->timezone('America/El_Salvador')
            ]);
            $envio->update([
                'estado_envio' => 'enviado', 
                'fecha_envio' => now()->timezone('America/El_Salvador')
            ]);

        } catch (\Exception $e) {
            $envio->update([
                'estado_envio' => 'fallido', 
                'error_detalle' => substr($e->getMessage(), 0, 250)
            ]);
        }

        return response()->json([
            'ok' => true,
            'mensaje' => 'Resultado registrado, PDF generado y procesado en cola de correo.',
            'data' => $resultado->load([
                'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'detalleOrden.examen',
                'enviosCorreo',
            ]),
        ], 201);
    }
}