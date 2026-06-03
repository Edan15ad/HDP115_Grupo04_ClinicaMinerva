<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llamamos a tus seeders en el orden correcto
        $this->call([
            UsuarioSeeder::class,
            PacienteSeeder::class, // Agregamos este para que el paciente tenga perfil
            ExamenesBaseSeeder::class,
            ParametrosResultadoBaseSeeder::class,
        ]);
    }
}