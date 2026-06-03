<?php

namespace Database\Seeders;

use App\Models\Examen;
use Illuminate\Database\Seeder;

class ExamenesBaseSeeder extends Seeder
{
    public function run(): void
    {
        $examenes = [
            [
                'codigo' => 'HEM001',
                'nombre' => 'Hemograma completo',
                'descripcion' => 'Evaluación general de células sanguíneas.',
                'precio' => 12.00,
                'tiempo_entrega_horas' => 24,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'ORI001',
                'nombre' => 'Examen general de orina',
                'descripcion' => 'Análisis físico, químico y microscópico de orina.',
                'precio' => 8.00,
                'tiempo_entrega_horas' => 24,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'COP001',
                'nombre' => 'Examen general de heces',
                'descripcion' => 'Análisis general de muestra fecal.',
                'precio' => 8.00,
                'tiempo_entrega_horas' => 24,
                'estado' => 'activo',
            ],
        ];

        foreach ($examenes as $examen) {
            Examen::updateOrCreate(
                ['codigo' => $examen['codigo']],
                $examen
            );
        }
    }
}