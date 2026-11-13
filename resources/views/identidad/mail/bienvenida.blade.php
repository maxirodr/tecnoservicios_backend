<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <p>Hola {{ $nombre }}:</p>
    <p>
        @if ($altaPor)
            {{ $altaPor }} te dio de alta en el sistema de Tecnoservicios.
        @else
            Te dieron de alta en el sistema de Tecnoservicios.
        @endif
    </p>
    <p>Tu mail de ingreso es <strong>{{ $email }}</strong>.</p>
    <p>
        <a href="{{ $url }}" style="display: inline-block; padding: 10px 16px; background: #1d4ed8; color: #ffffff; text-decoration: none; border-radius: 4px;">Elegir mi contraseña</a>
    </p>
    <p>El link vence en 7 días. Si venció, usá "Olvidé mi contraseña" en la pantalla de ingreso.</p>
</body>
</html>
