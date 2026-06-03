<?php

namespace Database\Seeders;

use App\Models\Examen;
use App\Models\ParametroResultado;
use Illuminate\Database\Seeder;

class ParametrosResultadoBaseSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            'HEM001' => [
                ['hemoglobina', 'Hemoglobina', 'decimal', 'g/dL', '12.0 - 16.0', true, 1],
                ['hematocrito', 'Hematocrito', 'decimal', '%', '36.0 - 48.0', true, 2],
                ['globulos_rojos', 'Glóbulos rojos', 'decimal', 'millones/µL', '4.2 - 5.9', true, 3],
                ['globulos_blancos', 'Glóbulos blancos', 'numero', 'células/µL', '4000 - 11000', true, 4],
                ['plaquetas', 'Plaquetas', 'numero', 'células/µL', '150000 - 450000', true, 5],
                ['neutrofilos', 'Neutrófilos', 'decimal', '%', '40 - 70', true, 6],
                ['linfocitos', 'Linfocitos', 'decimal', '%', '20 - 45', true, 7],
                ['monocitos', 'Monocitos', 'decimal', '%', '2 - 10', false, 8],
                ['eosinofilos', 'Eosinófilos', 'decimal', '%', '1 - 6', false, 9],
                ['basofilos', 'Basófilos', 'decimal', '%', '0 - 2', false, 10],
                ['observacion', 'Observación', 'texto', null, null, false, 11],
            ],

            'ORI001' => [
                ['color', 'Color', 'opcion', null, null, true, 1],
                ['aspecto', 'Aspecto', 'opcion', null, null, true, 2],
                ['densidad', 'Densidad', 'decimal', null, '1.005 - 1.030', true, 3],
                ['ph', 'pH', 'decimal', null, '5.0 - 8.0', true, 4],
                ['proteinas', 'Proteínas', 'opcion', null, 'Negativo', true, 5],
                ['glucosa', 'Glucosa', 'opcion', null, 'Negativo', true, 6],
                ['cetonas', 'Cetonas', 'opcion', null, 'Negativo', true, 7],
                ['nitritos', 'Nitritos', 'opcion', null, 'Negativo', true, 8],
                ['leucocitos', 'Leucocitos', 'opcion', null, 'Ausentes o escasos', true, 9],
                ['eritrocitos', 'Eritrocitos', 'opcion', null, 'Ausentes o escasos', true, 10],
                ['bacterias', 'Bacterias', 'opcion', null, 'Ausentes', true, 11],
                ['observacion', 'Observación', 'texto', null, null, false, 12],
            ],

            'COP001' => [
                ['color', 'Color', 'opcion', null, null, true, 1],
                ['consistencia', 'Consistencia', 'opcion', null, null, true, 2],
                ['moco', 'Moco', 'opcion', null, 'Ausente', true, 3],
                ['sangre_oculta', 'Sangre oculta', 'opcion', null, 'Negativo', true, 4],
                ['parasitos', 'Parásitos', 'opcion', null, 'No se observan', true, 5],
                ['leucocitos', 'Leucocitos', 'opcion', null, 'Ausentes', true, 6],
                ['eritrocitos', 'Eritrocitos', 'opcion', null, 'Ausentes', true, 7],
                ['restos_alimenticios', 'Restos alimenticios', 'opcion', null, 'Escasos', false, 8],
                ['observacion', 'Observación', 'texto', null, null, false, 9],
            ],
        ];

        foreach ($parametros as $codigoExamen => $items) {
            $examen = Examen::where('codigo', $codigoExamen)->first();

            if (!$examen) {
                continue;
            }

            foreach ($items as $item) {
                ParametroResultado::updateOrCreate(
                    [
                        'examen_id' => $examen->id,
                        'nombre_parametro' => $item[0],
                    ],
                    [
                        'etiqueta' => $item[1],
                        'tipo_dato' => $item[2],
                        'unidad_medida' => $item[3],
                        'valor_referencia' => $item[4],
                        'obligatorio' => $item[5],
                        'orden_visual' => $item[6],
                        'estado' => 'activo',
                    ]
                );
            }
        }
    }
}