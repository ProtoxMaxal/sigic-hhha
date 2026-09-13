<?php

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$configMail = require __DIR__ . '/config/mail.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['correo'] ?? '');

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'Debes ingresar un correo electrónico válido.';

    } else {

        $consulta = $pdo->prepare(
            "SELECT id_usuario, nombre, correo
             FROM usuarios
             WHERE correo = ?
             AND activo = 1
             LIMIT 1"
        );

        $consulta->execute([$correo]);

        $usuario = $consulta->fetch();


        if ($usuario) {

            try {

                /*
                 * Generar token seguro.
                 */
                $token = bin2hex(random_bytes(32));

                /*
                 * En la BD se guarda solamente
                 * el hash del token.
                 */
                $tokenHash = hash('sha256', $token);

                $fechaExpiracion = date(
                    'Y-m-d H:i:s',
                    time() + 3600
                );


                /*
                 * Invalidar tokens anteriores.
                 */
                $invalidar = $pdo->prepare(
                    "UPDATE tokens_recuperacion
                     SET utilizado = 1
                     WHERE id_usuario = ?
                     AND utilizado = 0"
                );

                $invalidar->execute([
                    $usuario['id_usuario']
                ]);


                /*
                 * Guardar nuevo token.
                 */
                $insertar = $pdo->prepare(
                    "INSERT INTO tokens_recuperacion (
                        id_usuario,
                        token_hash,
                        fecha_expiracion,
                        utilizado
                    )
                    VALUES (?, ?, ?, 0)"
                );

                $insertar->execute([
                    $usuario['id_usuario'],
                    $tokenHash,
                    $fechaExpiracion
                ]);


                /*
                 * Enlace de recuperación.                         
                 */
                $hostActual = $_SERVER['HTTP_HOST'] ?? 'localhost';

$esLocal =
    str_starts_with($hostActual, 'localhost') ||
    str_starts_with($hostActual, '127.0.0.1');

if ($esLocal) {

    $baseUrl = 'http://localhost/sigic-hhha';

} else {

    $baseUrl = 'https://' . $hostActual;
}

$linkRecuperacion =
    $baseUrl
    . '/restablecer_password.php?token='
    . urlencode($token);


                /*
                 * Configurar PHPMailer.
                 */
                $mail = new PHPMailer(true);

                $mail->isSMTP();

                $mail->Host =
                    $configMail['host'];

                $mail->SMTPAuth = true;

                $mail->Username =
                    $configMail['username'];

                $mail->Password =
                    $configMail['password'];

                $mail->SMTPSecure =
                    PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port =
                    $configMail['port'];

                $mail->CharSet = 'UTF-8';


                /*
                 * Remitente.
                 */
                $mail->setFrom(
                    $configMail['from_email'],
                    $configMail['from_name']
                );


                /*
                 * Destinatario.
                 */
                $mail->addAddress(
                    $usuario['correo'],
                    $usuario['nombre']
                );


                /*
                 * Contenido HTML.
                 */
                $mail->isHTML(true);

                $mail->Subject =
                    'Recuperación de contraseña - SIGIC-HHHA';

                $nombreSeguro = htmlspecialchars(
                    $usuario['nombre'],
                    ENT_QUOTES,
                    'UTF-8'
                );

                $linkSeguro = htmlspecialchars(
                    $linkRecuperacion,
                    ENT_QUOTES,
                    'UTF-8'
                );


                $mail->Body = "
                    <h2>SIGIC-HHHA</h2>

                    <p>
                        Hola {$nombreSeguro},
                    </p>

                    <p>
                        Se solicitó el restablecimiento
                        de la contraseña de tu cuenta.
                    </p>

                    <p>
                        Presiona el siguiente enlace:
                    </p>

                    <p>
                        <a href=\"{$linkSeguro}\">
                            Restablecer contraseña
                        </a>
                    </p>

                    <p>
                        El enlace estará disponible
                        durante 1 hora y podrá utilizarse
                        una sola vez.
                    </p>

                    <p>
                        Si no solicitaste este cambio,
                        puedes ignorar este mensaje.
                    </p>

                    <hr>

                    <p>
                        SIGIC-HHHA<br>
                        Sistema de Gestión e Inventario
                        de Equipos Computacionales
                    </p>
                ";


                /*
                 * Alternativa texto plano.
                 */
                $mail->AltBody =
                    "SIGIC-HHHA\n\n"
                    . "Hola {$usuario['nombre']}.\n\n"
                    . "Se solicitó restablecer la contraseña "
                    . "de tu cuenta.\n\n"
                    . "Enlace:\n"
                    . $linkRecuperacion
                    . "\n\nEste enlace estará disponible "
                    . "durante 1 hora y puede utilizarse "
                    . "una sola vez.";


                /*
                 * Enviar.
                 */
                $mail->send();

            } catch (Exception $e) {

                /*
                 * No mostramos detalles SMTP al usuario.
                 */
                error_log(
                    'Error PHPMailer: '
                    . $e->getMessage()
                );
            }
        }


        /*
         * Mismo mensaje exista o no el correo.
         * Evita revelar qué cuentas están registradas.
         */
        $mensaje =
            'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Recuperar contraseña - SIGIC-HHHA
    </title>

    <link
        rel="stylesheet"
        href="public/css/styles.css"
    >

</head>

<body>

<main class="login-page">

    <section class="login-container">

        <h1>SIGIC-HHHA</h1>

        <h2>
            Recuperar contraseña
        </h2>

        <p style="margin-bottom: 20px;">
            Ingresa el correo asociado a tu cuenta.
        </p>


        <?php if ($mensaje !== ''): ?>

            <div
                style="
                    margin-bottom: 15px;
                    padding: 12px;
                    border-radius: 5px;
                    background: #f1f1f1;
                "
            >
                <?php
                echo htmlspecialchars($mensaje);
                ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="correo">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    required
                    autocomplete="email"
                >

            </div>


            <button
                type="submit"
                class="btn-login"
            >
                Enviar instrucciones
            </button>

        </form>


        <p
            style="
                margin-top: 20px;
                text-align: center;
            "
        >

            <a href="login.php">
                Volver al inicio de sesión
            </a>

        </p>

    </section>

</main>

</body>

</html>