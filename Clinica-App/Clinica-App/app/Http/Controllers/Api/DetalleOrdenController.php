<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleOrden;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DetalleOrdenController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => DetalleOrden::with(['orden', 'examen', 'resultado'])
                ->orderBy('id', 'desc')
                ->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'orden_id' => ['required', 'exists:ordenes,id'],
            'examen_id' => ['required', 'exists:examenes,id'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'estado' => ['nullable', Rule::in([
                'pendiente',
                'muestra_tomada',
                'en_proceso',
                'finalizado',
                'cancelado'
            ])],
            'fecha_muestra' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:100'],
        ]);

        $data['estado'] = $data['estado'] ?? 'pendiente';

        $detalle = DetalleOrden::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Detalle de orden creado correctamente',
            'data' => $detalle->load(['orden', 'examen', 'resultado'])
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $detalle = DetalleOrden::with(['orden', 'examen', 'resultado'])->find($id);

        if (!$detalle) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Detalle de orden no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $detalle
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $detalle = DetalleOrden::find($id);

        if (!$detalle) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Detalle de orden no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'orden_id' => ['sometimes', 'required', 'exists:ordenes,id'],
            'examen_id' => ['sometimes', 'required', 'exists:examenes,id'],
            'precio_unitario' => ['sometimes', 'required', 'numeric', 'min:0'],
            'estado' => ['sometimes', 'required', Rule::in([
                'pendiente',
                'muestra_tomada',
                'en_proceso',
                'finalizado',
                'cancelado'
            ])],
            'fecha_muestra' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:100'],
        ]);

        $detalle->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Detalle de orden actualizado correctamente',
            'data' => $detalle->load(['orden', 'examen', 'resultado'])
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $detalle = DetalleOrden::find($id);

        if (!$detalle) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Detalle de orden no encontrado'
            ], 404);
        }

        $detalle->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Detalle de orden eliminado correctamente'
        ]);
    }
}