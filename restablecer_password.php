<?php

session_start();

require_once __DIR__ . '/config/database.php';

$mensaje = '';
$tipoMensaje = '';
$tokenValido = false;


/* =========================
   OBTENER TOKEN
========================= */

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if ($token === '') {

    $mensaje = 'El enlace de recuperación no es válido.';

} else {

    $tokenHash = hash('sha256', $token);

    /*
     * Buscar token válido:
     * - existe
     * - no ha sido utilizado
     * - no está vencido
     */
    $consulta = $pdo->prepare(
        "SELECT
            tr.id_token,
            tr.id_usuario,
            tr.fecha_expiracion,
            u.nombre,
            u.correo
         FROM tokens_recuperacion tr
         INNER JOIN usuarios u
            ON tr.id_usuario = u.id_usuario
         WHERE tr.token_hash = ?
         AND tr.utilizado = 0
         AND tr.fecha_expiracion >= NOW()
         AND u.activo = 1
         LIMIT 1"
    );

    $consulta->execute([$tokenHash]);

    $registroToken = $consulta->fetch();

    if (!$registroToken) {

        $mensaje =
            'El enlace de recuperación es inválido, ya fue utilizado o ha expirado.';

    } else {

        $tokenValido = true;
    }
}


/* =========================
   CAMBIAR CONTRASEÑA
========================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $tokenValido
) {

    $password = $_POST['password'] ?? '';
    $confirmarPassword =
        $_POST['confirmar_password'] ?? '';

    if (strlen($password) < 8) {

        $mensaje =
            'La contraseña debe tener al menos 8 caracteres.';

    } elseif ($password !== $confirmarPassword) {

        $mensaje =
            'Las contraseñas no coinciden.';

    } else {

        try {

            $pdo->beginTransaction();

            /*
             * Crear hash seguro
             */
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
             * Actualizar contraseña
             */
            $actualizar = $pdo->prepare(
                "UPDATE usuarios
                 SET password_hash = ?
                 WHERE id_usuario = ?"
            );

            $actualizar->execute([
                $passwordHash,
                $registroToken['id_usuario']
            ]);


            /*
             * Marcar token como utilizado
             */
            $usarToken = $pdo->prepare(
                "UPDATE tokens_recuperacion
                 SET utilizado = 1
                 WHERE id_token = ?"
            );

            $usarToken->execute([
                $registroToken['id_token']
            ]);


            /*
             * Invalidar cualquier otro token
             * pendiente del mismo usuario
             */
            $invalidarTokens = $pdo->prepare(
                "UPDATE tokens_recuperacion
                 SET utilizado = 1
                 WHERE id_usuario = ?
                 AND utilizado = 0"
            );

            $invalidarTokens->execute([
                $registroToken['id_usuario']
            ]);


            $pdo->commit();

            unset(
                $_SESSION[
                    'link_recuperacion_prueba'
                ]
            );

            $tokenValido = false;

            $mensaje =
                'Contraseña restablecida correctamente. Ya puedes iniciar sesión.';

            $tipoMensaje = 'ok';

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensaje =
                'No fue posible actualizar la contraseña.';

            $tipoMensaje = 'error';
        }
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
        Restablecer contraseña - SIGIC-HHHA
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
            Restablecer contraseña
        </h2>


        <?php if ($mensaje !== ''): ?>

            <div
                style="
                    margin: 20px 0;
                    padding: 12px;
                    background: #f1f1f1;
                    border-radius: 5px;
                "
            >
                <?php
                echo htmlspecialchars($mensaje);
                ?>
            </div>

        <?php endif; ?>


        <?php if ($tokenValido): ?>

            <p style="margin-bottom: 20px;">
                Usuario:
                <strong>
                    <?php
                    echo htmlspecialchars(
                        $registroToken['nombre']
                    );
                    ?>
                </strong>
            </p>


            <form method="POST">

                <input
                    type="hidden"
                    name="token"
                    value="<?php
                        echo htmlspecialchars($token);
                    ?>"
                >


                <div class="form-group">

                    <label for="password">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirmar_password">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        id="confirmar_password"
                        name="confirmar_password"
                        minlength="8"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn-login"
                >
                    Cambiar contraseña
                </button>

            </form>

        <?php endif; ?>


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