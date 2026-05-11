<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Paciente;
use App\Models\Usuario;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        // Buscamos al usuario "paciente" que acabamos de crear en UsuarioSeeder
        $usuarioPaciente = Usuario::where('correo', 'paciente@minerva.com')->first();

        // Si lo encuentra, le creamos su perfil en la tabla pacientes
        if ($usuarioPaciente) {
            Paciente::updateOrCreate(
                ['usuario_id' => $usuarioPaciente->id], 
                [
                    'nombres' => 'Paciente',
                    'apellidos' => 'Prueba',
                    'dui' => '123456789',
                    'fecha_nacimiento' => '1995-10-15',
                    'genero' => 'Femenino',
                    'telefono' => '77778888',
                    'direccion' => 'Chalatenango, El Salvador'
                ]
            );
        }
    }
}