<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'Administrador'
) {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$idUsuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idUsuario || $idUsuario <= 0) {
    header('Location: index.php');
    exit;
}

$roles = $pdo->query(
    "SELECT id_rol, nombre
     FROM roles
     ORDER BY nombre"
)->fetchAll();

$consulta = $pdo->prepare(
    "SELECT
        id_usuario,
        id_rol,
        nombre,
        correo,
        activo
     FROM usuarios
     WHERE id_usuario = ?
     LIMIT 1"
);

$consulta->execute([$idUsuario]);

$usuario = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: index.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    $idRol = !empty($_POST['id_rol'])
        ? (int)$_POST['id_rol']
        : 0;

    $activo = isset($_POST['activo'])
        ? (int)$_POST['activo']
        : 0;

    $password = $_POST['password'] ?? '';

    if ($nombre === '') {

        $mensaje = 'Debes ingresar el nombre del usuario.';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'Debes ingresar un correo válido.';

    } elseif (!$idRol) {

        $mensaje = 'Debes seleccionar un rol.';

    } elseif (!in_array($activo, [0, 1], true)) {

        $mensaje = 'El estado seleccionado no es válido.';

    } elseif (
        $password !== '' &&
        strlen($password) < 8
    ) {

        $mensaje = 'La nueva contraseña debe tener al menos 8 caracteres.';

    } elseif (
        (int)$idUsuario === (int)$_SESSION['id_usuario'] &&
        $activo === 0
    ) {

        $mensaje = 'No puedes desactivar tu propia cuenta mientras tienes la sesión iniciada.';

    } else {

        $consultaRol = $pdo->prepare(
            "SELECT id_rol
             FROM roles
             WHERE id_rol = ?
             LIMIT 1"
        );

        $consultaRol->execute([$idRol]);

        if (!$consultaRol->fetch()) {

            $mensaje = 'El rol seleccionado no es válido.';

        } else {

            $consultaCorreo = $pdo->prepare(
                "SELECT id_usuario
                 FROM usuarios
                 WHERE correo = ?
                 AND id_usuario <> ?
                 LIMIT 1"
            );

            $consultaCorreo->execute([
                $correo,
                $idUsuario
            ]);

            if ($consultaCorreo->fetch()) {

                $mensaje = 'Ya existe otro usuario con ese correo.';

            } else {

                try {

                    $pdo->beginTransaction();

                    if ($password !== '') {

                        $passwordHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $actualizar = $pdo->prepare(
                            "UPDATE usuarios
                             SET
                                id_rol = ?,
                                nombre = ?,
                                correo = ?,
                                activo = ?,
                                password_hash = ?
                             WHERE id_usuario = ?"
                        );

                        $actualizar->execute([
                            $idRol,
                            $nombre,
                            $correo,
                            $activo,
                            $passwordHash,
                            $idUsuario
                        ]);

                    } else {

                        $actualizar = $pdo->prepare(
                            "UPDATE usuarios
                             SET
                                id_rol = ?,
                                nombre = ?,
                                correo = ?,
                                activo = ?
                             WHERE id_usuario = ?"
                        );

                        $actualizar->execute([
                            $idRol,
                            $nombre,
                            $correo,
                            $activo,
                            $idUsuario
                        ]);
                    }

                    $detalle =
                        'Usuario modificado: ' .
                        $usuario['nombre'] .
                        ' -> ' .
                        $nombre .
                        ' | Correo: ' .
                        $correo;

                    $auditoria = $pdo->prepare(
                        "INSERT INTO auditoria (
                            id_usuario,
                            accion,
                            entidad,
                            id_registro,
                            detalle,
                            direccion_ip
                        )
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $auditoria->execute([
                        $_SESSION['id_usuario'],
                        'MODIFICAR',
                        'USUARIO',
                        $idUsuario,
                        $detalle,
                        $_SERVER['REMOTE_ADDR'] ?? null
                    ]);

                    $pdo->commit();

                    if (
                        (int)$idUsuario ===
                        (int)$_SESSION['id_usuario']
                    ) {
                        $_SESSION['nombre'] = $nombre;
                    }

                    header(
                        'Location: index.php?modificado=1'
                    );
                    exit;

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $mensaje = 'No fue posible modificar el usuario.';
                }
            }
        }
    }

    $usuario['nombre'] = $nombre;
    $usuario['correo'] = $correo;
    $usuario['id_rol'] = $idRol;
    $usuario['activo'] = $activo;
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

    <title>Modificar usuario - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>

        <h1>SIGIC-HHHA</h1>

        <p>Gestión de Usuarios</p>

    </div>

    <div class="usuario-info">

        <p>
            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION['nombre']
                );
                ?>
            </strong>
        </p>

        <p>
            Perfil:
            <?php
            echo htmlspecialchars(
                $_SESSION['rol']
            );
            ?>
        </p>

        <a
            href="index.php"
            class="logout-link"
        >
            Volver a usuarios
        </a>

    </div>

</header>

<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Modificar usuario</h2>

            <p>
                Actualice la información de la cuenta seleccionada.
            </p>

        </div>

    </div>

    <?php if ($mensaje !== ''): ?>

        <div class="mensaje-error">

            <?php
            echo htmlspecialchars($mensaje);
            ?>

        </div>

    <?php endif; ?>

    <form
        method="POST"
        class="formulario-equipo"
    >

        <div class="form-group">

            <label for="nombre">
                Nombre *
            </label>

            <input
                type="text"
                id="nombre"
                name="nombre"
                value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="correo">
                Correo *
            </label>

            <input
                type="email"
                id="correo"
                name="correo"
                value="<?php echo htmlspecialchars($usuario['correo']); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="id_rol">
                Rol *
            </label>

            <select
                id="id_rol"
                name="id_rol"
                required
            >

                <?php foreach ($roles as $rol): ?>

                    <option
                        value="<?php echo $rol['id_rol']; ?>"
                        <?php
                        echo
                        (int)$usuario['id_rol'] ===
                        (int)$rol['id_rol']
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php
                        echo htmlspecialchars(
                            $rol['nombre']
                        );
                        ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="form-group">

            <label for="activo">
                Estado *
            </label>

            <select
                id="activo"
                name="activo"
                required
            >

                <option
                    value="1"
                    <?php
                    echo (int)$usuario['activo'] === 1
                        ? 'selected'
                        : '';
                    ?>
                >
                    Activo
                </option>

                <option
                    value="0"
                    <?php
                    echo (int)$usuario['activo'] === 0
                        ? 'selected'
                        : '';
                    ?>
                >
                    Inactivo
                </option>

            </select>

        </div>

        <div class="form-group">

            <label for="password">
                Nueva contraseña
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
            >

            <small>
                Déjala vacía si no deseas cambiar la contraseña.
            </small>

        </div>

        <button
            type="submit"
            class="btn-login"
        >
            Guardar cambios
        </button>

    </form>

</main>

</body>

</html>