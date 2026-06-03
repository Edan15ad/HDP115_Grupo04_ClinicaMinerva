<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminRegistroUsuarioController extends Controller
{
    /**
     * Registra un nuevo usuario con cualquier rol.
     * Si el rol es 'paciente', también crea el perfil de paciente.
     * Solo accesible para administrador.
     * POST /api/admin/registrar-usuario
     */
    public function store(Request $request)
    {
        $usuarioAuth = $request->user();

        if ($usuarioAuth->rol !== 'administrador') {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'Solo el administrador puede crear usuarios.',
            ], 403);
        }

        $request->validate([
            'nombre'           => ['required', 'string', 'max:50'],
            'apellido'         => ['required', 'string', 'max:50'],
            'correo'           => ['required', 'email', 'max:100', 'unique:usuarios,correo'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'rol'              => ['required', Rule::in(['paciente', 'recepcionista', 'laboratorio', 'administrador'])],
            'estado'           => ['nullable', Rule::in(['activo', 'inactivo'])],
            // Campos de paciente (solo requeridos si rol = paciente)
            'nombres'          => ['nullable', 'required_if:rol,paciente', 'string', 'max:60'],
            'apellidos'        => ['nullable', 'required_if:rol,paciente', 'string', 'max:60'],
            'dui'              => ['nullable', 'string', 'size:9', 'unique:pacientes,dui'],
            'telefono'         => ['nullable', 'string', 'max:8'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'genero'           => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:150'],
        ], [
            'correo.unique'           => 'Este correo ya está registrado.',
            'dui.unique'              => 'Este DUI ya está registrado.',
            'dui.size'                => 'El DUI debe tener exactamente 9 dígitos.',
            'password.min'            => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'      => 'Las contraseñas no coinciden.',
            'nombres.required_if'     => 'Los nombres son obligatorios para el rol paciente.',
            'apellidos.required_if'   => 'Los apellidos son obligatorios para el rol paciente.',
        ]);

        try {
            $resultado = DB::transaction(function () use ($request) {
                $nuevoUsuario = Usuario::create([
                    'nombre'   => $request->nombre,
                    'apellido' => $request->apellido,
                    'correo'   => $request->correo,
                    'password' => Hash::make($request->password),
                    'rol'      => $request->rol,
                    'estado'   => $request->estado ?? 'activo',
                ]);

                // Si el rol es paciente, crear también el perfil
                if ($request->rol === 'paciente') {
                    Paciente::create([
                        'usuario_id'       => $nuevoUsuario->id,
                        'nombres'          => $request->nombres ?? $request->nombre,
                        'apellidos'        => $request->apellidos ?? $request->apellido,
                        'dui'              => $request->dui,
                        'telefono'         => $request->telefono,
                        'fecha_nacimiento' => $request->fecha_nacimiento,
                        'genero'           => $request->genero,
                        'direccion'        => $request->direccion,
                    ]);
                }

                return $nuevoUsuario;
            });

            return response()->json([
                'ok'     => true,
                'mensaje'=> 'Usuario creado correctamente.',
                'data'   => $resultado->makeHidden(['password']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'Error al crear el usuario. Intente nuevamente.',
            ], 500);
        }
    }
}