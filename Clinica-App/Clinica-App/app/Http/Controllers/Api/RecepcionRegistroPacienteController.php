<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RecepcionRegistroPacienteController extends Controller
{
    /**
     * Registra un nuevo paciente completo (usuario + perfil paciente).
     * Solo accesible para recepcionista y administrador.
     * POST /api/recepcion/registrar-paciente
     */
    public function store(Request $request)
    {
        $usuario = $request->user();

        if (!in_array($usuario->rol, ['recepcionista', 'administrador'])) {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        $request->validate([
            'nombres'          => ['required', 'string', 'max:60'],
            'apellidos'        => ['required', 'string', 'max:60'],
            'correo'           => ['required', 'email', 'max:100', 'unique:usuarios,correo'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'dui'              => ['nullable', 'string', 'size:9', 'unique:pacientes,dui'],
            'telefono'         => ['nullable', 'string', 'max:8'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'genero'           => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:150'],
        ], [
            'correo.unique'           => 'Este correo ya está registrado en el sistema.',
            'dui.unique'              => 'Este DUI ya está registrado en el sistema.',
            'dui.size'                => 'El DUI debe tener exactamente 9 dígitos sin guiones.',
            'password.min'            => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'      => 'Las contraseñas no coinciden.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ]);

        try {
            $paciente = DB::transaction(function () use ($request) {
                $nuevoUsuario = Usuario::create([
                    'nombre'   => $request->nombres,
                    'apellido' => $request->apellidos,
                    'correo'   => $request->correo,
                    'password' => Hash::make($request->password),
                    'rol'      => 'paciente',
                    'estado'   => 'activo',
                ]);

                return Paciente::create([
                    'usuario_id'       => $nuevoUsuario->id,
                    'nombres'          => $request->nombres,
                    'apellidos'        => $request->apellidos,
                    'dui'              => $request->dui,
                    'telefono'         => $request->telefono,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero'           => $request->genero,
                    'direccion'        => $request->direccion,
                ]);
            });

            return response()->json([
                'ok'     => true,
                'mensaje'=> 'Paciente registrado correctamente.',
                'data'   => $paciente->load('usuario:id,nombre,apellido,correo,rol,estado'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'Error al registrar el paciente. Intente nuevamente.',
            ], 500);
        }
    }
}