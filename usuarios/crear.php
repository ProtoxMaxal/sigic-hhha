<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

/* Solo Administrador */
if ($_SESSION['rol'] !== 'Administrador') {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';

/* Obtener roles */
$roles = $pdo->query(
    "SELECT id_rol, nombre
     FROM roles
     ORDER BY nombre"
)->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    $idRol = !empty($_POST['id_rol'])
        ? (int) $_POST['id_rol']
        : 0;

    if ($nombre === '') {

        $mensaje = 'Debes ingresar el nombre del usuario.';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = 'Debes ingresar un correo válido.';

    } elseif (strlen($password) < 8) {

        $mensaje = 'La contraseña debe tener al menos 8 caracteres.';

    } elseif (!$idRol) {

        $mensaje = 'Debes seleccionar un rol.';

    } else {

        /* Comprobar que el rol exista */
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

            /* Comprobar correo duplicado */
            $consultaCorreo = $pdo->prepare(
                "SELECT id_usuario
                 FROM usuarios
                 WHERE correo = ?
                 LIMIT 1"
            );

            $consultaCorreo->execute([$correo]);

            if ($consultaCorreo->fetch()) {

                $mensaje = 'Ya existe un usuario con ese correo.';

            } else {

                try {

                    $pdo->beginTransaction();

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
                        $idRol,
                        $nombre,
                        $correo,
                        $passwordHash
                    ]);

                    $idUsuarioNuevo = $pdo->lastInsertId();


                    /* Auditoría */
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
                        'CREAR',
                        'usuarios',
                        $idUsuarioNuevo,
                        'Creación de nuevo usuario del sistema.',
                        $_SERVER['REMOTE_ADDR'] ?? null
                    ]);

                    $pdo->commit();

                    header('Location: index.php?creado=1');
                    exit;

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $mensaje = 'No fue posible crear el usuario.';
                }
            }
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

    <title>Crear usuario - SIGIC-HHHA</title>

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
                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </strong>
        </p>

        <p>
            Perfil:
            <?php echo htmlspecialchars($_SESSION['rol']); ?>
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

            <h2>Crear usuario</h2>

            <p>
                Registrar una nueva cuenta para SIGIC-HHHA.
            </p>

        </div>

    </div>


    <?php if ($mensaje !== ''): ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($mensaje); ?>
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

                <option value="">
                    Seleccione
                </option>

                <?php foreach ($roles as $rol): ?>

                    <option value="<?php echo $rol['id_rol']; ?>">
                        <?php echo htmlspecialchars($rol['nombre']); ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label for="password">
                Contraseña *
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

        </div>


        <button
            type="submit"
            class="btn-login"
        >
            Crear usuario
        </button>

    </form>

</main>

</body>

</html>