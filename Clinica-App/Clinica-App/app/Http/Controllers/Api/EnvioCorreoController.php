<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnvioCorreo;
use App\Models\Resultado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResultadoLaboratorioMail;

class EnvioCorreoController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => EnvioCorreo::with('resultado.detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado')
                ->orderBy('id', 'desc')
                ->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'resultado_id' => ['required', 'exists:resultados,id'],
            'correo_destino' => ['required', 'email', 'max:100'],
            'estado_envio' => ['nullable', Rule::in(['pendiente', 'enviado', 'fallido'])],
            'fecha_envio' => ['nullable', 'date'],
            'archivo_adjunto' => ['nullable', 'string', 'max:255'],
            'error_detalle' => ['nullable', 'string', 'max:255'],
        ]);

        $data['estado_envio'] = $data['estado_envio'] ?? 'pendiente';

        $envio = EnvioCorreo::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Registro de envío creado correctamente',
            'data' => $envio->load('resultado')
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $envio = EnvioCorreo::with('resultado.detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado')
            ->find($id);

        if (!$envio) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Registro de envío no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $envio
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $envio = EnvioCorreo::find($id);

        if (!$envio) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Registro de envío no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'resultado_id' => ['sometimes', 'required', 'exists:resultados,id'],
            'correo_destino' => ['sometimes', 'required', 'email', 'max:100'],
            'estado_envio' => ['sometimes', 'required', Rule::in(['pendiente', 'enviado', 'fallido'])],
            'fecha_envio' => ['nullable', 'date'],
            'archivo_adjunto' => ['nullable', 'string', 'max:255'],
            'error_detalle' => ['nullable', 'string', 'max:255'],
        ]);

        $envio->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Registro de envío actualizado correctamente',
            'data' => $envio->load('resultado')
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $envio = EnvioCorreo::find($id);

        if (!$envio) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Registro de envío no encontrado'
            ], 404);
        }

        $envio->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Registro de envío eliminado correctamente'
        ]);
    }

    // --- NUEVO MÉTODO: REENVIAR CORREO ---
    public function reenviar(Request $request, string $resultadoId)
    {
        $usuario = $request->user();
        
        // Verificamos que sea un usuario autorizado (recepción o admin)
        if (!$usuario || !in_array($usuario->rol, ['recepcionista', 'administrador'])) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permisos para reenviar correos.',
            ], 403);
        }

        $resultado = Resultado::with([
            'detalleOrden.orden.paciente.usuario',
            'detalleOrden.examen'
        ])->find($resultadoId);

        if (!$resultado) {
            return response()->json(['ok' => false, 'mensaje' => 'Resultado no encontrado.'], 404);
        }

        if (!$resultado->archivo_pdf) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se puede enviar el correo porque el PDF aún no se ha generado.',
            ], 422);
        }

        $paciente = $resultado->detalleOrden->orden->paciente;
        $examen = $resultado->detalleOrden->examen;

        // Creamos un nuevo registro de intento de envío
        $envio = EnvioCorreo::create([
            'resultado_id' => $resultado->id,
            'correo_destino' => $paciente->usuario->correo,
            'estado_envio' => 'pendiente',
        ]);

        try {
            // Disparamos el correo
            Mail::to($paciente->usuario->correo)->send(new ResultadoLaboratorioMail($paciente, $examen, $resultado->archivo_pdf));
            
            // Actualizamos estados a exitosos
            $envio->update([
                'estado_envio' => 'enviado', 
                'fecha_envio' => now()->timezone('America/El_Salvador')
            ]);
            
            $resultado->update(['correo_enviado' => true]);

            return response()->json([
                'ok' => true,
                'mensaje' => 'Correo reenviado exitosamente al paciente.',
            ]);

        } catch (\Exception $e) {
            // Si hay error, lo marcamos fallido y guardamos el log
            $envio->update([
                'estado_envio' => 'fallido', 
                'error_detalle' => substr($e->getMessage(), 0, 250)
            ]);

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al enviar el correo. Verifique la conexión a internet o credenciales.',
            ], 500);
        }
    }
}