<?php

session_start();

require_once __DIR__ . '/config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($correo === '' || $password === '') {

        $mensaje = 'Debes completar todos los campos.';

    } else {

        $consulta = $pdo->prepare(
            "SELECT
                u.id_usuario,
                u.nombre,
                u.correo,
                u.password_hash,
                u.activo,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.correo = ?
            LIMIT 1"
        );

        $consulta->execute([$correo]);

        $usuario = $consulta->fetch();

        if (
            $usuario &&
            $usuario['activo'] == 1 &&
            password_verify($password, $usuario['password_hash'])
        ) {

            session_regenerate_id(true);

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['correo'] = $usuario['correo'];
            $_SESSION['rol'] = $usuario['rol'];

            header('Location: dashboard.php');
            exit;

        } else {

            $mensaje = 'Correo o contraseña incorrectos.';
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

    <title>Iniciar sesión - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="public/css/styles.css"
    >
</head>

<body>

    <main class="login-page">

        <section class="login-container">

            <h1>SIGIC-HHHA</h1>

            <h2>Gestión e Inventario de Equipos Computacionales</h2>

            <?php if ($mensaje !== ''): ?>

                <div class="mensaje-error">
                    <?php echo htmlspecialchars($mensaje); ?>
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
                        placeholder="Ingrese su correo"
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
                        placeholder="Ingrese su contraseña"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn-login"
                >
                    Iniciar sesión
                </button>

            </form>

        </section>

    </main>

</body>

</html>