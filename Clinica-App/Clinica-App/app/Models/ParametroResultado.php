<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroResultado extends Model
{
    protected $table = 'parametros_resultado';

    protected $fillable = [
        'examen_id',
        'nombre_parametro',
        'etiqueta',
        'tipo_dato',
        'unidad_medida',
        'valor_referencia',
        'obligatorio',
        'orden_visual',
        'estado',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'orden_visual' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function activo(): bool
    {
        return $this->estado === 'activo';
    }

    public function obligatorio(): bool
    {
        return $this->obligatorio === true;
    }
}