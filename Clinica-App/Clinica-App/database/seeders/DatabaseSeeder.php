<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // LLAMAMOS SOLO A TU SEEDER PERSONALIZADO
        $this->call([
            UsuarioSeeder::class,
        ]);
    }
}
