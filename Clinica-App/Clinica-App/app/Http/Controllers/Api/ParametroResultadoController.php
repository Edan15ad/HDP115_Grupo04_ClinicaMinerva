<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametroResultado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParametroResultadoController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => ParametroResultado::with('examen')
                ->orderBy('examen_id')
                ->orderBy('orden_visual')
                ->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'examen_id' => ['required', 'exists:examenes,id'],
            'nombre_parametro' => ['required', 'string', 'max:60'],
            'etiqueta' => ['required', 'string', 'max:100'],
            'tipo_dato' => ['required', Rule::in([
                'texto',
                'numero',
                'decimal',
                'booleano',
                'fecha',
                'opcion'
            ])],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
            'valor_referencia' => ['nullable', 'string', 'max:100'],
            'obligatorio' => ['nullable', 'boolean'],
            'orden_visual' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $data['obligatorio'] = $data['obligatorio'] ?? false;
        $data['orden_visual'] = $data['orden_visual'] ?? 1;
        $data['estado'] = $data['estado'] ?? 'activo';

        $parametro = ParametroResultado::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Parámetro de resultado creado correctamente',
            'data' => $parametro->load('examen')
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $parametro = ParametroResultado::with('examen')->find($id);

        if (!$parametro) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Parámetro de resultado no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $parametro
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $parametro = ParametroResultado::find($id);

        if (!$parametro) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Parámetro de resultado no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'examen_id' => ['sometimes', 'required', 'exists:examenes,id'],
            'nombre_parametro' => ['sometimes', 'required', 'string', 'max:60'],
            'etiqueta' => ['sometimes', 'required', 'string', 'max:100'],
            'tipo_dato' => ['sometimes', 'required', Rule::in([
                'texto',
                'numero',
                'decimal',
                'booleano',
                'fecha',
                'opcion'
            ])],
            'unidad_medida' => ['nullable', 'string', 'max:30'],
            'valor_referencia' => ['nullable', 'string', 'max:100'],
            'obligatorio' => ['nullable', 'boolean'],
            'orden_visual' => ['nullable', 'integer', 'min:1'],
            'estado' => ['sometimes', 'required', Rule::in(['activo', 'inactivo'])],
        ]);

        $parametro->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Parámetro de resultado actualizado correctamente',
            'data' => $parametro->load('examen')
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $parametro = ParametroResultado::find($id);

        if (!$parametro) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Parámetro de resultado no encontrado'
            ], 404);
        }

        $parametro->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Parámetro de resultado eliminado correctamente'
        ]);
    }
}