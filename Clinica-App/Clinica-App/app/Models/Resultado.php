<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultado extends Model
{
    protected $table = 'resultados';

    protected $fillable = [
        'detalle_orden_id',
        'fecha_resultado',
        'resultado_json',
        'observaciones_generales',
        'archivo_pdf',
        'estado',
        'correo_enviado',
        'fecha_envio_correo',
    ];

    protected $casts = [
        'fecha_resultado' => 'datetime',
        'resultado_json' => 'array',
        'correo_enviado' => 'boolean',
        'fecha_envio_correo' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function detalleOrden()
    {
        return $this->belongsTo(DetalleOrden::class, 'detalle_orden_id');
    }

    public function enviosCorreo()
    {
        return $this->hasMany(EnvioCorreo::class, 'resultado_id');
    }

    public function borrador(): bool
    {
        return $this->estado === 'borrador';
    }

    public function finalizado(): bool
    {
        return $this->estado === 'finalizado';
    }

    public function enviado(): bool
    {
        return $this->estado === 'enviado';
    }
}