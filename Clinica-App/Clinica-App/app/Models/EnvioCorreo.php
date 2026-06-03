<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioCorreo extends Model
{
    protected $table = 'envios_correo';

    public const UPDATED_AT = null;

    protected $fillable = [
        'resultado_id',
        'correo_destino',
        'estado_envio',
        'fecha_envio',
        'archivo_adjunto',
        'error_detalle',
    ];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function resultado()
    {
        return $this->belongsTo(Resultado::class, 'resultado_id');
    }

    public function pendiente(): bool
    {
        return $this->estado_envio === 'pendiente';
    }

    public function enviado(): bool
    {
        return $this->estado_envio === 'enviado';
    }

    public function fallido(): bool
    {
        return $this->estado_envio === 'fallido';
    }
}