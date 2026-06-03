<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrdenController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Orden::with([
                'paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'cita',
                'detalles.examen'
            ])->orderBy('id', 'desc')->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'correlativo' => ['required', 'string', 'max:20', 'unique:ordenes,correlativo'],
            'cita_id' => ['required', 'exists:citas,id', 'unique:ordenes,cita_id'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'fecha_orden' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::in([
                'pendiente',
                'recepcionado',
                'en_laboratorio',
                'finalizado',
                'entregado',
                'cancelado'
            ])],
            'total' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['estado'] = $data['estado'] ?? 'pendiente';
        $data['total'] = $data['total'] ?? 0;

        $orden = Orden::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Orden creada correctamente',
            'data' => $orden->load([
                'paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'cita',
                'detalles.examen'
            ])
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $orden = Orden::with([
            'paciente.usuario:id,nombre,apellido,correo,rol,estado',
            'cita',
            'detalles.examen',
            'resultados'
        ])->find($id);

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Orden no encontrada'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $orden
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $orden = Orden::find($id);

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Orden no encontrada'
            ], 404);
        }

        $data = $request->validate([
            'correlativo' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('ordenes', 'correlativo')->ignore($orden->id),
            ],
            'cita_id' => [
                'sometimes',
                'required',
                'exists:citas,id',
                Rule::unique('ordenes', 'cita_id')->ignore($orden->id),
            ],
            'paciente_id' => ['sometimes', 'required', 'exists:pacientes,id'],
            'fecha_orden' => ['nullable', 'date'],
            'estado' => ['sometimes', 'required', Rule::in([
                'pendiente',
                'recepcionado',
                'en_laboratorio',
                'finalizado',
                'entregado',
                'cancelado'
            ])],
            'total' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $orden->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Orden actualizada correctamente',
            'data' => $orden->load([
                'paciente.usuario:id,nombre,apellido,correo,rol,estado',
                'cita',
                'detalles.examen'
            ])
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $orden = Orden::find($id);

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Orden no encontrada'
            ], 404);
        }

        $orden->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Orden eliminada correctamente'
        ]);
    }
}