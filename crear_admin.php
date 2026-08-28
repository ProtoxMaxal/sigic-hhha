<?php

session_start();

require_once __DIR__ . '/config/database.php';

$mensaje = '';
$tipoMensaje = '';

/*
 * Este archivo solo puede utilizarse
 * cuando todavía no existe ningún usuario.
 */
$totalUsuarios = $pdo->query(
    "SELECT COUNT(*) FROM usuarios"
)->fetchColumn();

if ($totalUsuarios > 0) {

    header('Location: login.php');
    exit;
}


/* Buscar rol Administrador */
$consultaRol = $pdo->prepare(
    "SELECT id_rol
     FROM roles
     WHERE nombre = ?
     LIMIT 1"
);

$consultaRol->execute(['Administrador']);

$idRolAdministrador = $consultaRol->fetchColumn();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirmar_password'] ?? '';

    if (!$idRolAdministrador) {

        $mensaje =
            'No existe el rol Administrador. Importe primero los datos iniciales.';

        $tipoMensaje = 'error';

    } elseif ($nombre === '') {

        $mensaje = 'Debes ingresar un nombre.';
        $tipoMensaje = 'error';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'Debes ingresar un correo válido.';
        $tipoMensaje = 'error';

    } elseif (strlen($password) < 8) {

        $mensaje =
            'La contraseña debe contener al menos 8 caracteres.';

        $tipoMensaje = 'error';

    } elseif ($password !== $confirmarPassword) {

        $mensaje = 'Las contraseñas no coinciden.';
        $tipoMensaje = 'error';

    } else {

        try {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insertar = $pdo->prepare(
                "INSERT INTO usuarios (
                    id_rol,
                    nombre,
                    correo,
                    password_hash,
                    activo
                )
                VALUES (?, ?, ?, ?, 1)"
            );

            $insertar->execute([
                $idRolAdministrador,
                $nombre,
                $correo,
                $passwordHash
            ]);

            header('Location: login.php?admin_creado=1');
            exit;

        } catch (PDOException $e) {

            $mensaje =
                'No fue posible crear el Administrador.';

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

    <title>Configuración inicial - SIGIC-HHHA</title>

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
            Configuración inicial
        </h2>

        <p style="margin-bottom: 20px; text-align: center;">
            Crear el primer usuario Administrador.
        </p>


        <?php if ($mensaje !== ''): ?>

            <div class="mensaje-error">

                <?php echo htmlspecialchars($mensaje); ?>

            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label for="nombre">
                    Nombre
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    required
                >

            </div>


            <div class="form-group">

                <label for="correo">
                    Correo
                </label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Contraseña
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
                Crear Administrador
            </button>

        </form>

    </section>

</main>

</body>

</html>