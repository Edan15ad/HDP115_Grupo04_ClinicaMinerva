<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\DetalleOrden;
use App\Models\Examen;
use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PacienteExamenController extends Controller
{
    public function misExamenes(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario autenticado no tiene paciente asociado.',
                'data' => [],
            ], 403);
        }

        // Corrección de privacidad: forzamos la consulta desde la relación del paciente
        $query = $usuario->paciente->ordenes()->with([
                'cita',
                'detalles.examen',
                'detalles.resultado',
            ])
            ->orderBy('fecha_orden', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha')) {
            $query->whereHas('cita', function ($q) use ($request) {
                $q->whereDate('fecha_cita', $request->fecha);
            });
        }

        return response()->json([
            'ok' => true,
            'data' => $query->get(),
        ]);
    }

    public function examenesDisponibles()
    {
        return response()->json([
            'ok' => true,
            'data' => Examen::where('estado', 'activo')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function horariosDisponibles(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $horariosBase = $this->generarHorariosBase();

        $ocupados = Cita::whereDate('fecha_cita', $data['fecha'])
            ->whereIn('estado', [
                'agendada',
                'confirmada',
                'muestra_tomada',
                'en_laboratorio',
            ])
            ->pluck('hora_cita')
            ->map(function ($hora) {
                return substr((string) $hora, 0, 5);
            })
            ->toArray();

        $disponibles = collect($horariosBase)
            ->map(function ($hora) use ($ocupados) {
                return [
                    'hora' => $hora,
                    'disponible' => !in_array($hora, $ocupados),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $disponibles,
        ]);
    }

    public function solicitarExamen(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->paciente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario autenticado no tiene paciente asociado.',
            ], 403);
        }

        $paciente = $usuario->paciente;
        $horariosPermitidos = $this->generarHorariosBase();

        $data = $request->validate([
            'examen_id' => ['required', 'exists:examenes,id'],
            'fecha_cita' => ['required', 'date', 'after_or_equal:today'],
            'hora_cita' => ['required', 'date_format:H:i', Rule::in($horariosPermitidos)],
            'observaciones' => ['nullable', 'string', 'max:100'],
        ]);

        $examen = Examen::where('estado', 'activo')->find($data['examen_id']);

        if (!$examen) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El examen seleccionado no está disponible.',
            ], 422);
        }

        $horaOcupada = Cita::whereDate('fecha_cita', $data['fecha_cita'])
            ->where('hora_cita', $data['hora_cita'])
            ->whereIn('estado', [
                'agendada',
                'confirmada',
                'muestra_tomada',
                'en_laboratorio',
            ])
            ->exists();

        if ($horaOcupada) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El horario seleccionado ya no está disponible.',
            ], 422);
        }

        $orden = DB::transaction(function () use ($paciente, $data, $examen) {
            $cita = Cita::create([
                'paciente_id' => $paciente->id,
                'fecha_cita' => $data['fecha_cita'],
                'hora_cita' => $data['hora_cita'],
                'estado' => 'agendada',
            ]);

            $orden = Orden::create([
                'correlativo' => $this->generarCorrelativo(),
                'cita_id' => $cita->id,
                'paciente_id' => $paciente->id,
                'estado' => 'pendiente',
                'total' => $examen->precio,
            ]);

            DetalleOrden::create([
                'orden_id' => $orden->id,
                'examen_id' => $examen->id,
                'precio_unitario' => $examen->precio,
                'estado' => 'pendiente',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            return $orden->load([
                'cita',
                'detalles.examen',
                'detalles.resultado',
            ]);
        });

        return response()->json([
            'ok' => true,
            'mensaje' => 'Solicitud de examen enviada correctamente a recepción.',
            'data' => $orden,
        ], 201);
    }

    private function generarHorariosBase(): array
    {
        $horarios = [];

        for ($hora = 8; $hora <= 19; $hora++) {
            $horarios[] = str_pad((string) $hora, 2, '0', STR_PAD_LEFT) . ':00';
        }

        return $horarios;
    }

    private function generarCorrelativo(): string
    {
        $prefijo = 'ORD-' . now()->format('Ymd') . '-';

        $ultimo = Orden::where('correlativo', 'like', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimo) {
            return $prefijo . '0001';
        }

        $numero = (int) substr($ultimo->correlativo, -4);

        return $prefijo . str_pad((string) ($numero + 1), 4, '0', STR_PAD_LEFT);
    }
}