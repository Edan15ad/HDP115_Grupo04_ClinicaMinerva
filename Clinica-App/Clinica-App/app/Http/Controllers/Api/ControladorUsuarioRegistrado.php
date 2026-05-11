<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ControladorUsuarioRegistrado extends Controller
{
    public function create()
    {
        return Inertia::render('auth/Register');
    }

    public function store(Request $request)
    {

        $request->validate([
            'nombres' => 'required|string|max:60',
            'apellidos' => 'required|string|max:60',
            'correo' => 'required|string|email|max:100|unique:usuarios,correo',
            'password' => 'required|string|min:6|confirmed', 
            'dui' => 'nullable|string|size:9|unique:pacientes,dui', 
            'telefono' => 'nullable|string|max:8',
  
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:150',
        ], [
            
            'dui.unique' => 'Este número de DUI ya está registrado en el sistema. Por favor, verifícalo e insértalo correctamente.',
            'dui.size' => 'El número de DUI debe tener exactamente 9 dígitos sin guiones.',
            'correo.unique' => 'Este correo electrónico ya está en uso. Por favor, intenta iniciar sesión.',
        ]);

        try {
            $usuario = DB::transaction(function () use ($request) {
                
                $user = Usuario::create([
                    'nombre' => $request->nombres,
                    'apellido' => $request->apellidos,
                    'correo' => $request->correo,
                    'password' => Hash::make($request->password),
                    'rol' => 'paciente', 
                    'estado' => 'activo',
                ]);

                Paciente::create([
                    'usuario_id' => $user->id,
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'dui' => $request->dui,
                    'telefono' => $request->telefono,
                    // Guardamos los nuevos datos en la BD
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero' => $request->genero,
                    'direccion' => $request->direccion,
                ]);

                return $user;
            });

            Auth::login($usuario);
            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear la cuenta. Intente nuevamente.']);
        }
    }
}