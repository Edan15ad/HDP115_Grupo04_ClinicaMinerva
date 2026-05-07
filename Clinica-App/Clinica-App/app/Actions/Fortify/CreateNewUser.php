<?php

namespace App\Actions\Fortify;

use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): Usuario
    {
        Validator::make($input, [
            'nombre' => ['required', 'string', 'max:50'],
            'apellido' => ['required', 'string', 'max:50'],
            'correo' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('usuarios', 'correo'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $usuario = Usuario::create([
                'nombre' => $input['nombre'],
                'apellido' => $input['apellido'],
                'correo' => $input['correo'],
                'password' => Hash::make($input['password']),
                'rol' => 'paciente',
                'estado' => 'activo',
            ]);

            Paciente::create([
                'usuario_id' => $usuario->id,
                'nombres' => $input['nombre'],
                'apellidos' => $input['apellido'],
            ]);

            return $usuario;
        });
    }
}