<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes';

    protected $fillable = [
        'correlativo',
        'cita_id',
        'paciente_id',
        'fecha_orden',
        'estado',
        'total',
    ];

    protected $casts = [
        'fecha_orden' => 'datetime',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleOrden::class, 'orden_id');
    }

    public function resultados()
    {
        return $this->hasManyThrough(
            Resultado::class,
            DetalleOrden::class,
            'orden_id',
            'detalle_orden_id',
            'id',
            'id'
        );
    }

    public function pendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function finalizada(): bool
    {
        return $this->estado === 'finalizado';
    }

    public function entregada(): bool
    {
        return $this->estado === 'entregado';
    }

    public function cancelada(): bool
    {
        return $this->estado === 'cancelado';
    }
}