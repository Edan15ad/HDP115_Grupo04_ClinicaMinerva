<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'password',
        'rol',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'usuario_id');
    }

    public function esPaciente(): bool
    {
        return $this->rol === 'paciente';
    }

    public function esRecepcionista(): bool
    {
        return $this->rol === 'recepcionista';
    }

    public function esLaboratorio(): bool
    {
        return $this->rol === 'laboratorio';
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function activo(): bool
    {
        return $this->estado === 'activo';
    }
}