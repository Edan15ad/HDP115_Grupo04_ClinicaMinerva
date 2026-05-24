<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetUserPasswordCommand extends Command
{
    /**
     * Uso:  php artisan usuario:reset-password
     * O con argumento directo:
     *        php artisan usuario:reset-password correo@dominio.com NuevaPass123
     */
    protected $signature   = 'usuario:reset-password
                                {correo? : Correo del usuario}
                                {password? : Nueva contraseña (min 8 chars)}';

    protected $description = 'Resetea la contraseña de un usuario directamente desde la terminal (sin correo)';

    public function handle(): int
    {
        $correo   = $this->argument('correo')   ?? $this->ask('Correo del usuario');
        $password = $this->argument('password') ?? $this->secret('Nueva contraseña (mínimo 8 caracteres)');

        if (strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');
            return self::FAILURE;
        }

        $usuario = Usuario::where('correo', $correo)->first();

        if (!$usuario) {
            $this->error("No se encontró un usuario con el correo: {$correo}");
            return self::FAILURE;
        }

        $usuario->update(['password' => Hash::make($password)]);

        $this->info("✓ Contraseña actualizada correctamente.");
        $this->line("  Usuario : {$usuario->nombre} {$usuario->apellido}");
        $this->line("  Correo  : {$usuario->correo}");
        $this->line("  Rol     : {$usuario->rol}");

        return self::SUCCESS;
    }
}