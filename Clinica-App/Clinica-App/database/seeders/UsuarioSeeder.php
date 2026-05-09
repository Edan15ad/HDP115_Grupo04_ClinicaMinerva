<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario; 
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'nombre' => 'Admin',
                'apellido' => 'Sistema',
                'correo' => 'admin@minerva.com',
                'rol' => 'administrador',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Recepcionista',
                'apellido' => 'Minerva',
                'correo' => 'recepcionista@minerva.com',
                'rol' => 'recepcionista',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Laboratorio',
                'apellido' => 'Minerva',
                'correo' => 'laboratorio@minerva.com',
                'rol' => 'laboratorio',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Paciente',
                'apellido' => 'Prueba',
                'correo' => 'paciente@minerva.com',
                'rol' => 'paciente',
                'estado' => 'activo',
            ],
        ];

        foreach ($usuarios as $u) {
            Usuario::updateOrCreate(
                ['correo' => $u['correo']], // Usamos 'correo' no 'email'
                [
                    'nombre'   => $u['nombre'],
                    'apellido' => $u['apellido'],
                    'rol'      => $u['rol'],
                    'estado'   => $u['estado'],
                    'password' => Hash::make('123456'), 
                ]
            );
        }
    }
}