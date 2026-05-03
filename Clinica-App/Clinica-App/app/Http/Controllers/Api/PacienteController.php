<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Paciente::with('usuario:id,nombre,apellido,correo,rol,estado')
                ->orderBy('id', 'desc')
                ->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id', 'unique:pacientes,usuario_id'],
            'nombres' => ['required', 'string', 'max:60'],
            'apellidos' => ['required', 'string', 'max:60'],
            'dui' => ['nullable', 'string', 'max:9', 'unique:pacientes,dui'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'genero' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:8'],
            'direccion' => ['nullable', 'string', 'max:150'],
        ]);

        $paciente = Paciente::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Paciente creado correctamente',
            'data' => $paciente->load('usuario:id,nombre,apellido,correo,rol,estado')
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $paciente = Paciente::with([
            'usuario:id,nombre,apellido,correo,rol,estado',
            'citas',
            'ordenes'
        ])->find($id);

        if (!$paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Paciente no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $paciente
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $paciente = Paciente::find($id);

        if (!$paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Paciente no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'usuario_id' => [
                'sometimes',
                'required',
                'exists:usuarios,id',
                Rule::unique('pacientes', 'usuario_id')->ignore($paciente->id),
            ],
            'nombres' => ['sometimes', 'required', 'string', 'max:60'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:60'],
            'dui' => [
                'nullable',
                'string',
                'max:9',
                Rule::unique('pacientes', 'dui')->ignore($paciente->id),
            ],
            'fecha_nacimiento' => ['nullable', 'date'],
            'genero' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:8'],
            'direccion' => ['nullable', 'string', 'max:150'],
        ]);

        $paciente->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Paciente actualizado correctamente',
            'data' => $paciente->load('usuario:id,nombre,apellido,correo,rol,estado')
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $paciente = Paciente::find($id);

        if (!$paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Paciente no encontrado'
            ], 404);
        }

        $paciente->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Paciente eliminado correctamente'
        ]);
    }
}