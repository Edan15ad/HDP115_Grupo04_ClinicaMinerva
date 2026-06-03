<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'fecha_cita',
        'hora_cita',
        'estado',
    ];

    protected $casts = [
        'fecha_cita' => 'date',
        'hora_cita' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function orden()
    {
        return $this->hasOne(Orden::class, 'cita_id');
    }

    public function agendada(): bool
    {
        return $this->estado === 'agendada';
    }

    public function finalizada(): bool
    {
        return $this->estado === 'finalizada';
    }

    public function cancelada(): bool
    {
        return $this->estado === 'cancelada';
    }
}