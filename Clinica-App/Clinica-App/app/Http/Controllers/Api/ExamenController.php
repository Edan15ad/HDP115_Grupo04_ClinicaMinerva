<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamenController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Examen::with('parametrosResultado')
                ->orderBy('id', 'desc')
                ->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:examenes,codigo'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'precio' => ['required', 'numeric', 'min:0'],
            'tiempo_entrega_horas' => ['required', 'integer', 'min:1'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $data['estado'] = $data['estado'] ?? 'activo';

        $examen = Examen::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Examen creado correctamente',
            'data' => $examen
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $examen = Examen::with('parametrosResultado')->find($id);

        if (!$examen) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Examen no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $examen
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $examen = Examen::find($id);

        if (!$examen) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Examen no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('examenes', 'codigo')->ignore($examen->id),
            ],
            'nombre' => ['sometimes', 'required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'precio' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tiempo_entrega_horas' => ['sometimes', 'required', 'integer', 'min:1'],
            'estado' => ['sometimes', 'required', Rule::in(['activo', 'inactivo'])],
        ]);

        $examen->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Examen actualizado correctamente',
            'data' => $examen
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $examen = Examen::find($id);

        if (!$examen) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Examen no encontrado'
            ], 404);
        }

        $examen->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Examen eliminado correctamente'
        ]);
    }
}