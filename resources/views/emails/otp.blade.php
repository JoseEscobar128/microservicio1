<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Verificación de correo</title>
</head>
<body style="font-family: Poppins, Arial, sans-serif; background-color: #f7fafc; margin: 0; padding: 0; color: #333;">
  <div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);">

    <!-- Encabezado sin imagen -->
    <div style="background-color: #D78D16; padding: 30px 20px; text-align: center; color: white;">
      <h1 style="margin: 0; font-size: 22px;">Mesa Fácil</h1>
      <p style="margin: 5px 0 0;">Verificación de correo electrónico</p>
    </div>

    <!-- Contenido principal -->
    <div style="padding: 30px;">
      <p>Hola <strong>{{ $nombre }}</strong>,</p>
      <p>Gracias por usar nuestro servicio. Por favor utiliza el siguiente código de verificación:</p>

      <div style="background: #fcefdc; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center; border: 1px dashed #e2e8f0;">
        <p style="margin: 0 0 10px;">Tu código de verificación es:</p>
        <div style="font-size: 32px; font-weight: 700; letter-spacing: 5px; color: #D78D16; margin: 15px 0; padding: 10px 20px; background: white; border-radius: 6px; display: inline-block; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
          {{ $otp }}
        </div>
        <p style="margin-top: 10px;">Este código es válido por <strong>10 minutos</strong>.</p>
      </div>

      <p>Si no solicitaste este código, por favor ignora este mensaje o contacta con nuestro equipo de soporte.</p>

      <hr style="margin: 30px 0; border: none; height: 1px; background: #e2e8f0;" />

      <p style="text-align: center;">¿Tienes problemas? <a href="mailto:soporte@tudominio.com" style="color: #D78D16;">Contáctanos</a></p>

      <div style="text-align: center;">
        <a href="{{ url('/') }}" style="display: inline-block; padding: 12px 24px; background: #D78D16; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; margin-top: 20px;">Ir a nuestra página</a>
      </div>
    </div>

    <!-- Footer -->
    <div style="text-align: center; padding: 20px; font-size: 12px; color: #64748b; background: #f1f5f9;">
      <p>© {{ date('Y') }} Mesa Fácil. Todos los derechos reservados.</p>
      <p>
        <a href="{{ url('/politica-privacidad') }}" style="color: #64748b; text-decoration: none;">Política de Privacidad</a> |
        <a href="{{ url('/terminos') }}" style="color: #64748b; text-decoration: none;">Términos de Servicio</a>
      </p>
      <p>Calle Falsa 123, Torreón Coahuila, México</p>
    </div>
  </div>
</body>
</html>
