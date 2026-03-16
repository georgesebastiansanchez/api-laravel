<!DOCTYPE html>

<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restablecer contraseña</title>
<style>
body {
font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif;
line-height: 1.6;
color: #4A5568;
background-color: #F7FAFC;
margin: 0;
padding: 0;
-webkit-text-size-adjust: 100%;
-ms-text-size-adjust: 100%;
}
.wrapper {
max-width: 600px;
margin: 40px auto;
padding: 0 20px;
}
.container {
background: #FFFFFF;
padding: 30px 40px;
border-radius: 12px;
box-shadow: 0 4px 10px rgba(0,0,0,0.05);
border: 1px solid #E2E8F0;
}
.header {
text-align: center;
padding-bottom: 20px;
margin-bottom: 30px;
}
.header h1 {
color: #3182CE; /* Azul más profesional /
margin: 0;
font-size: 24px;
font-weight: 700;
}
.content {
margin-bottom: 30px;
font-size: 16px;
}
.button-wrapper {
text-align: center;
margin: 30px 0;
}
.button {
display: inline-block;
padding: 12px 25px;
background-color: #3182CE; / Azul principal /
color: white !important; / Asegura que el texto sea blanco /
text-decoration: none;
border-radius: 8px;
font-weight: 600;
text-align: center;
box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
transition: background-color 0.3s ease;
}
.button:hover {
background-color: #2C5282; / Tono más oscuro al pasar el ratón /
}
.footer {
border-top: 1px solid #E2E8F0;
padding-top: 20px;
text-align: center;
font-size: 12px;
color: #A0AEC0;
}
.warning {
background-color: #FEF3C7; / Amarillo suave /
border-left: 5px solid #FBBF24; / Barra lateral amarilla /
color: #92400E;
padding: 15px;
border-radius: 6px;
margin: 25px 0;
}
.monospace-link {
word-break: break-all;
background-color: #F7FAFC;
padding: 10px;
border-radius: 6px;
font-family: monospace;
font-size: 14px;
color: #4A5568;
display: block; / Asegura que ocupe su propia línea */
}
</style>
</head>
<body>
<div class="wrapper">
<div class="container">
<div class="header">
<h1>Restablecer Contraseña</h1>
</div>

        <div class="content">
            <p><strong>Hola {{ $nombre }},</strong></p>
            
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta asociada al correo: <strong>{{ $email }}</strong>. Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
            
            <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
            
            <div class="button-wrapper">
                <a href="http://localhost:5173/reset-password?token={{ $token }}&email={{ $email }}" class="button">
                    Restablecer Contraseña
                </a>
            </div>
            
            <div class="warning">
                <strong>⚠️ Atención:</strong>
                <ul>
                    <li>Por motivos de seguridad, este enlace expirará en <strong>24 horas</strong>.</li>
                    <li>El restablecimiento solo puede realizarse una vez.</li>
                </ul>
            </div>
            
            <p>Si tienes problemas para hacer clic en el botón, copia y pega este enlace en tu navegador:</p>
            <span class="monospace-link">
                http://localhost:5173/reset-password?token={{ $token }}&email={{ $email }}
            </span>
        </div>
        
        <div class="footer">
            <p>Este correo es automatizado, por favor no lo respondas.</p>
            <p>Tu Sistema &copy; {{ date('Y') }}</p>
        </div>
    </div>
</div>

</body>
</html>