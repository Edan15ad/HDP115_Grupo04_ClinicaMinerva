<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; color: #334155; line-height: 1.5; padding: 20px;">

    <h2 style="color: #0ea5e9;">Tus resultados están listos</h2>
    
    <p>Estimado(a) <strong>{{ $paciente->nombres }} {{ $paciente->apellidos }}</strong>,</p>
    
    <p>El laboratorio de <strong>Clínica Minerva</strong> ha procesado con éxito tu examen clínico correspondiente a:</p>
    <h3 style="background-color: #f1f5f9; padding: 10px; border-radius: 5px; color: #0f172a;">{{ $examen->nombre }}</h3>
    
    <p>Hemos adjuntado en este correo el documento PDF con el reporte completo de tus resultados.</p>
    
    <p>Puedes presentar este documento a tu médico de confianza o revisarlo ingresando a tu portal de paciente.</p>

    <br>
    <p>Atentamente,<br><strong>Equipo de Laboratorio, Clínica Minerva</strong></p>

</body>
</html>