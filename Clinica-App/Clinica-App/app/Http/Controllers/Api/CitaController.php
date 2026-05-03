<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CitaController extends Controller
{
    // INDEX -> búsqueda general
    public function index()
    {
        return response()->json([
            'ok' => true,
            'data' => Cita::with('paciente.usuario:id,nombre,apellido,correo,rol,estado')
                ->orderBy('fecha_cita', 'desc')
                ->orderBy('hora_cita', 'desc')
                ->get()
        ]);
    }

    // STORE -> guardar
    public function store(Request $request)
    {
        $data = $request->validate([
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'fecha_cita' => ['required', 'date'],
            'hora_cita' => ['required', 'date_format:H:i'],
            'estado' => ['nullable', Rule::in([
                'agendada',
                'confirmada',
                'muestra_tomada',
                'en_laboratorio',
                'finalizada',
                'cancelada'
            ])],
        ]);

        $data['estado'] = $data['estado'] ?? 'agendada';

        $cita = Cita::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cita creada correctamente',
            'data' => $cita->load('paciente.usuario:id,nombre,apellido,correo,rol,estado')
        ], 201);
    }

    // SHOW -> búsqueda por id
    public function show(string $id)
    {
        $cita = Cita::with([
            'paciente.usuario:id,nombre,apellido,correo,rol,estado',
            'orden'
        ])->find($id);

        if (!$cita) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Cita no encontrada'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $cita
        ]);
    }

    // UPDATE -> actualizar por id
    public function update(Request $request, string $id)
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Cita no encontrada'
            ], 404);
        }

        $data = $request->validate([
            'paciente_id' => ['sometimes', 'required', 'exists:pacientes,id'],
            'fecha_cita' => ['sometimes', 'required', 'date'],
            'hora_cita' => ['sometimes', 'required', 'date_format:H:i'],
            'estado' => ['sometimes', 'required', Rule::in([
                'agendada',
                'confirmada',
                'muestra_tomada',
                'en_laboratorio',
                'finalizada',
                'cancelada'
            ])],
        ]);

        $cita->update($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cita actualizada correctamente',
            'data' => $cita->load('paciente.usuario:id,nombre,apellido,correo,rol,estado')
        ]);
    }

    // DESTROY -> borrar por id
    public function destroy(string $id)
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Cita no encontrada'
            ], 404);
        }

        $cita->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cita eliminada correctamente'
        ]);
    }
}