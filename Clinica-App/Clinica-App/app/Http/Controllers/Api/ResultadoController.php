<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resultado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResultadoController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Resultado::with([
                'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'detalleOrden.examen',
                'enviosCorreo'
            ])->orderBy('id', 'desc')->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'detalle_orden_id' => ['required', 'exists:detalle_ordenes,id', 'unique:resultados,detalle_orden_id'],
            'fecha_resultado' => ['nullable', 'date'],
            'resultado_json' => ['required', 'array'],
            'observaciones_generales' => ['nullable', 'string', 'max:200'],
            'archivo_pdf' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['borrador', 'finalizado', 'enviado'])],
            'correo_enviado' => ['nullable', 'boolean'],
            'fecha_envio_correo' => ['nullable', 'date'],
        ]);

        $data['estado'] = $data['estado'] ?? 'borrador';
        $data['correo_enviado'] = $data['correo_enviado'] ?? false;

        $resultado = Resultado::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Resultado creado correctamente',
            'data' => $resultado->load([
                'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'detalleOrden.examen',
                'enviosCorreo'
            ])
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $resultado = Resultado::with([
            'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
            'detalleOrden.examen',
            'enviosCorreo'
        ])->find($id);

        if (!$resultado) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Resultado no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $resultado
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $resultado = Resultado::find($id);

        if (!$resultado) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Resultado no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'detalle_orden_id' => [
                'sometimes',
                'required',
                'exists:detalle_ordenes,id',
                Rule::unique('resultados', 'detalle_orden_id')->ignore($resultado->id),
            ],
            'fecha_resultado' => ['nullable', 'date'],
            'resultado_json' => ['sometimes', 'required', 'array'],
            'observaciones_generales' => ['nullable', 'string', 'max:200'],
            'archivo_pdf' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'required', Rule::in(['borrador', 'finalizado', 'enviado'])],
            'correo_enviado' => ['nullable', 'boolean'],
            'fecha_envio_correo' => ['nullable', 'date'],
        ]);

        $resultado->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Resultado actualizado correctamente',
            'data' => $resultado->load([
                'detalleOrden.orden.paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'detalleOrden.examen',
                'enviosCorreo'
            ])
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $resultado = Resultado::find($id);

        if (!$resultado) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Resultado no encontrado'
            ], 404);
        }

        $resultado->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Resultado eliminado correctamente'
        ]);
    }
}