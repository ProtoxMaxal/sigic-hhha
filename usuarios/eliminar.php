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

if ((int)$idUsuario === (int)$_SESSION['id_usuario']) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        u.id_usuario,
        u.nombre,
        u.correo,
        u.activo,
        r.nombre AS rol
     FROM usuarios u
     INNER JOIN roles r
        ON u.id_rol = r.id_rol
     WHERE u.id_usuario = ?"
);

$stmt->execute([$idUsuario]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM movimientos
     WHERE id_usuario = ?"
);

$stmt->execute([$idUsuario]);

$totalMovimientos = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM mantenciones
     WHERE id_usuario = ?"
);

$stmt->execute([$idUsuario]);

$totalMantenciones = (int)$stmt->fetchColumn();

$tieneHistorial =
    $totalMovimientos > 0 ||
    $totalMantenciones > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneHistorial) {

        $error = 'El usuario no puede eliminarse porque posee movimientos o mantenciones asociados.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalleAuditoria =
                'Usuario eliminado: ' .
                $usuario['nombre'] .
                ' | Correo: ' .
                $usuario['correo'] .
                ' | Rol: ' .
                $usuario['rol'];

            $direccionIp = $_SERVER['REMOTE_ADDR'] ?? null;

            $stmt = $pdo->prepare(
                "INSERT INTO auditoria
                    (
                        id_usuario,
                        accion,
                        entidad,
                        id_registro,
                        detalle,
                        fecha,
                        direccion_ip
                    )
                 VALUES
                    (?, ?, ?, ?, ?, NOW(), ?)"
            );

            $stmt->execute([
                $_SESSION['id_usuario'],
                'ELIMINAR',
                'USUARIO',
                $idUsuario,
                $detalleAuditoria,
                $direccionIp
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM usuarios
                 WHERE id_usuario = ?"
            );

            $stmt->execute([$idUsuario]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('No fue posible eliminar el usuario.');
            }

            $pdo->commit();

            header('Location: index.php?eliminado=1');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'No fue posible eliminar el usuario.';
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

    <title>Eliminar usuario - SIGIC-HHHA</title>

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
                    $_SESSION['nombre'] ?? ''
                );
                ?>
            </strong>
        </p>

        <p>
            Perfil:
            <?php
            echo htmlspecialchars(
                $_SESSION['rol'] ?? ''
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

    <div
        style="
            max-width: 750px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        "
    >

        <h2>Eliminar usuario</h2>

        <p>
            Está a punto de eliminar el siguiente usuario:
        </p>

        <div
            style="
                background: #f4f5f6;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
            "
        >

            <p>
                <strong>Nombre:</strong>
                <?php
                echo htmlspecialchars(
                    $usuario['nombre']
                );
                ?>
            </p>

            <p>
                <strong>Correo:</strong>
                <?php
                echo htmlspecialchars(
                    $usuario['correo']
                );
                ?>
            </p>

            <p>
                <strong>Rol:</strong>
                <?php
                echo htmlspecialchars(
                    $usuario['rol']
                );
                ?>
            </p>

        </div>

        <?php if ($tieneHistorial): ?>

            <div
                style="
                    margin: 20px 0;
                    padding: 15px;
                    background: #fff3cd;
                    border-radius: 6px;
                "
            >

                <strong>
                    Este usuario no puede eliminarse.
                </strong>

                <p>
                    Posee registros históricos asociados.
                </p>

                <p>
                    Movimientos:
                    <strong>
                        <?php echo $totalMovimientos; ?>
                    </strong>
                </p>

                <p>
                    Mantenciones:
                    <strong>
                        <?php echo $totalMantenciones; ?>
                    </strong>
                </p>

            </div>

        <?php else: ?>

            <p>
                <strong>Advertencia:</strong>
                esta acción eliminará permanentemente
                la cuenta del usuario.
            </p>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div
                style="
                    margin: 20px 0;
                    padding: 15px;
                    background: #f8d7da;
                    border-radius: 6px;
                "
            >
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <?php if (!$tieneHistorial): ?>

            <form method="POST">

                <div
                    style="
                        display: flex;
                        gap: 10px;
                        margin-top: 25px;
                    "
                >

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="btn-primary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <a
                href="index.php"
                class="btn-primary"
            >
                Volver a usuarios
            </a>

        <?php endif; ?>

    </div>

</main>

</body>

</html>