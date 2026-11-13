<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <p>Hola {{ $nombre }}:</p>
    <p>Pediste elegir una contraseña nueva para el sistema de Tecnoservicios.</p>
    <p>
        <a href="{{ $url }}" style="display: inline-block; padding: 10px 16px; background: #1d4ed8; color: #ffffff; text-decoration: none; border-radius: 4px;">Elegir contraseña nueva</a>
    </p>
    <p>El link vence en 60 minutos y sirve una sola vez.</p>
    <p>Si no lo pediste, ignorá este mail.</p>
</body>
</html>
