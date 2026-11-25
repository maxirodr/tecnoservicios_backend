<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <p>{{ $texto }}</p>
    <table style="border-collapse: collapse; margin: 12px 0;">
        <tr><td style="padding: 2px 12px 2px 0; color: #6b7280;">Tarea</td><td>{{ $tarea }}</td></tr>
        <tr><td style="padding: 2px 12px 2px 0; color: #6b7280;">Obra</td><td>{{ $obra }}</td></tr>
        @if ($entrega)
        <tr><td style="padding: 2px 12px 2px 0; color: #6b7280;">Entrega</td><td>{{ $entrega }}</td></tr>
        @endif
        @if ($prioridad)
        <tr><td style="padding: 2px 12px 2px 0; color: #6b7280;">Prioridad</td><td>{{ $prioridad }}</td></tr>
        @endif
        @if ($solicitante)
        <tr><td style="padding: 2px 12px 2px 0; color: #6b7280;">La pidió</td><td>{{ $solicitante }}</td></tr>
        @endif
    </table>
    <p>
        <a href="{{ $link }}" style="display: inline-block; padding: 10px 18px; background: #1d4ed8; color: #ffffff; text-decoration: none; border-radius: 6px;">Ver la tarea</a>
    </p>
    <p style="font-size: 12px; color: #6b7280;">Este aviso es automático. Respondé desde el sistema, en la tarea. Podés elegir qué avisos te llegan por mail en Mi perfil.</p>
</body>
</html>
