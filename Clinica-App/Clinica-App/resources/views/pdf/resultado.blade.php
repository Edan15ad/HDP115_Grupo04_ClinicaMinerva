<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado de Examen</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #06b6d4; padding-bottom: 10px; margin-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #0f172a; margin: 0; }
        .subtitle { font-size: 14px; color: #64748b; margin: 0; }
        .patient-info { margin-bottom: 20px; padding: 10px; background-color: #f8fafc; border-radius: 5px; }
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .results-table th, .results-table td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        .results-table th { background-color: #f1f5f9; color: #334155; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <p class="title">Clínica Minerva</p>
        <p class="subtitle">Reporte Oficial de Laboratorio</p>
    </div>

    <div class="patient-info">
        <p><strong>Paciente:</strong> {{ $paciente->nombres }} {{ $paciente->apellidos }}</p>
        <p><strong>DUI:</strong> {{ $paciente->dui ?? 'No registrado' }}</p>
        <p><strong>Examen Realizado:</strong> {{ $examen->nombre }} (Código: {{ $examen->codigo }})</p>
        <p><strong>Fecha de Resultado:</strong> {{ \Carbon\Carbon::parse($resultado->fecha_resultado)->format('d/m/Y h:i A') }}</p>
    </div>

    <table class="results-table">
        <thead>
            <tr>
                <th>Parámetro</th>
                <th>Resultado Obtenido</th>
                <th>Valores de Referencia</th>
                <th>Unidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parametros as $param)
                <tr>
                    <td>{{ $param->etiqueta }}</td>
                    <td><strong>{{ $resultado->resultado_json[$param->nombre_parametro] ?? 'N/A' }}</strong></td>
                    <td>{{ $param->valor_referencia ?? '—' }}</td>
                    <td>{{ $param->unidad_medida ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($resultado->observaciones_generales)
    <div>
        <p><strong>Observaciones del Laboratorio:</strong></p>
        <p>{{ $resultado->observaciones_generales }}</p>
    </div>
    @endif

    <div class="footer">
        Este documento es válido para usos médicos. Generado automáticamente por SGE-Minerva.
    </div>

</body>
</html>