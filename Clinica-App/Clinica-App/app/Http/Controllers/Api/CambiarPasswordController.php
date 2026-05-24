<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CambiarPasswordController extends Controller
{
    /**
     * Cambia la contraseña del usuario autenticado.
     * Disponible para todos los roles.
     * PUT /api/usuario/cambiar-password
     */
    public function update(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'Usuario no autenticado.',
            ], 401);
        }

        $data = $request->validate([
            'password_actual'      => ['required', 'string'],
            'password_nuevo'       => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'password_nuevo_confirmation' => ['required', 'string'],
        ], [
            'password_actual.required'      => 'Debes ingresar tu contraseña actual.',
            'password_nuevo.required'       => 'La nueva contraseña es obligatoria.',
            'password_nuevo.confirmed'      => 'La confirmación no coincide con la nueva contraseña.',
            'password_nuevo.min'            => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_nuevo.letters'        => 'La nueva contraseña debe contener al menos una letra.',
            'password_nuevo.numbers'        => 'La nueva contraseña debe contener al menos un número.',
            'password_nuevo_confirmation.required' => 'Debes confirmar la nueva contraseña.',
        ]);

        // Verificar contraseña actual
        if (!Hash::check($data['password_actual'], $usuario->password)) {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'La contraseña actual es incorrecta.',
                'errors' => ['password_actual' => ['La contraseña actual es incorrecta.']],
            ], 422);
        }

        // Evitar reutilizar la misma contraseña
        if (Hash::check($data['password_nuevo'], $usuario->password)) {
            return response()->json([
                'ok'     => false,
                'mensaje'=> 'La nueva contraseña no puede ser igual a la contraseña actual.',
                'errors' => ['password_nuevo' => ['La nueva contraseña no puede ser igual a la contraseña actual.']],
            ], 422);
        }

        $usuario->update([
            'password' => Hash::make($data['password_nuevo']),
        ]);

        return response()->json([
            'ok'     => true,
            'mensaje'=> 'Contraseña actualizada correctamente.',
        ]);
    }
}