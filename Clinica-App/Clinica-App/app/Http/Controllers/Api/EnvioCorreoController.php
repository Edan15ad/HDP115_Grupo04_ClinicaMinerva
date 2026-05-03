<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnvioCorreo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
}