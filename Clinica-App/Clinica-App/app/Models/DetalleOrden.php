<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOrden extends Model
{
    protected $table = 'detalle_ordenes';

    protected $fillable = [
        'orden_id',
        'examen_id',
        'precio_unitario',
        'estado',
        'fecha_muestra',
        'observaciones',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'fecha_muestra' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function orden()
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function resultado()
    {
        return $this->hasOne(Resultado::class, 'detalle_orden_id');
    }

    public function pendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function enProceso(): bool
    {
        return $this->estado === 'en_proceso';
    }

    public function finalizado(): bool
    {
        return $this->estado === 'finalizado';
    }

    public function cancelado(): bool
    {
        return $this->estado === 'cancelado';
    }
}