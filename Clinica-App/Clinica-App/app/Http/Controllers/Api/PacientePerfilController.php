<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacientePerfilController extends Controller
{
    public function show(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario autenticado no tiene paciente asociado.',
            ], 404);
        }

        $paciente = $usuario->paciente;

        return response()->json([
            'ok' => true,
            'data' => [
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'correo' => $usuario->correo,
                    'rol' => $usuario->rol,
                    'estado' => $usuario->estado,
                ],
                'paciente' => [
                    'id' => $paciente->id,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'dui' => $paciente->dui,
                    'fecha_nacimiento' => optional($paciente->fecha_nacimiento)->format('Y-m-d'),
                    'telefono' => $paciente->telefono,
                    'direccion' => $paciente->direccion,
                ],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario autenticado no tiene paciente asociado.',
            ], 404);
        }

        $paciente = $usuario->paciente;

        $data = $request->validate([
            'correo' => [
                'required',
                'email',
                'max:100',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id),
            ],
            'dui' => [
                'nullable',
                'string',
                'max:9',
                Rule::unique('pacientes', 'dui')->ignore($paciente->id),
            ],
            'telefono' => ['nullable', 'string', 'max:8'],
            'direccion' => ['nullable', 'string', 'max:150'],
        ]);

        $usuario->update([
            'correo' => $data['correo'],
        ]);

        $paciente->update([
            'dui' => $data['dui'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Perfil actualizado correctamente.',
            'data' => [
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'correo' => $usuario->correo,
                    'rol' => $usuario->rol,
                    'estado' => $usuario->estado,
                ],
                'paciente' => [
                    'id' => $paciente->id,
                    'nombres' => $paciente->nombres,
                    'apellidos' => $paciente->apellidos,
                    'dui' => $paciente->dui,
                    'fecha_nacimiento' => optional($paciente->fecha_nacimiento)->format('Y-m-d'),
                    'telefono' => $paciente->telefono,
                    'direccion' => $paciente->direccion,
                ],
            ],
        ]);
    }
}