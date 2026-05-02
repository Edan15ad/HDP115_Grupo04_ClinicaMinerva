<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio',
        'tiempo_entrega_horas',
        'estado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'tiempo_entrega_horas' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function detalleOrdenes()
    {
        return $this->hasMany(DetalleOrden::class, 'examen_id');
    }

    public function parametrosResultado()
    {
        return $this->hasMany(ParametroResultado::class, 'examen_id')
            ->orderBy('orden_visual');
    }

    public function activo(): bool
    {
        return $this->estado === 'activo';
    }
}