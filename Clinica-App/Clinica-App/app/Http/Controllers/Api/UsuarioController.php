<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Usuario::select(
                'id',
                'nombre',
                'apellido',
                'correo',
                'rol',
                'estado',
                'created_at',
                'updated_at'
            )->orderBy('id', 'desc')->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'correo' => ['required', 'email', 'max:100', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::in(['paciente', 'recepcionista', 'laboratorio', 'administrador'])],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['estado'] = $data['estado'] ?? 'activo';

        $usuario = Usuario::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Usuario creado correctamente',
            'data' => $usuario->makeHidden(['password'])
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $usuario = Usuario::select(
            'id',
            'nombre',
            'apellido',
            'correo',
            'rol',
            'estado',
            'created_at',
            'updated_at'
        )->find($id);

        if (!$usuario) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $usuario
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Usuario no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:50'],
            'apellido' => ['sometimes', 'required', 'string', 'max:50'],
            'correo' => [
                'sometimes',
                'required',
                'email',
                'max:100',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'rol' => ['sometimes', 'required', Rule::in(['paciente', 'recepcionista', 'laboratorio', 'administrador'])],
            'estado' => ['sometimes', 'required', Rule::in(['activo', 'inactivo'])],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Usuario actualizado correctamente',
            'data' => $usuario->makeHidden(['password'])
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Usuario no encontrado'
            ], 404);
        }

        $usuario->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Usuario eliminado correctamente'
        ]);
    }
}