<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

/* Solo Administrador puede eliminar mantenciones */
if (
    !isset($_SESSION['rol']) ||
    strtolower(trim($_SESSION['rol'])) !== 'administrador'
) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$idMantencion = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($idMantencion <= 0) {
    header('Location: index.php');
    exit;
}

$consulta = $pdo->prepare(
    "SELECT
        m.id_mantencion,
        m.id_equipo,
        m.tipo,
        m.estado,
        m.detalle,
        m.fecha,
        e.nombre_equipo,
        e.numero_inventario
    FROM mantenciones m
    INNER JOIN equipos e
        ON m.id_equipo = e.id_equipo
    WHERE m.id_mantencion = ?
    LIMIT 1"
);

$consulta->execute([$idMantencion]);

$mantencion = $consulta->fetch();

if (!$mantencion) {
    header('Location: index.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();

        $detalleAuditoria =
            'Mantención eliminada. Equipo: ' .
            $mantencion['nombre_equipo'] .
            ' | Inventario: ' .
            ($mantencion['numero_inventario'] ?? 'Sin inventario') .
            ' | Tipo: ' .
            $mantencion['tipo'] .
            ' | Estado: ' .
            $mantencion['estado'];

        $eliminar = $pdo->prepare(
            "DELETE FROM mantenciones
             WHERE id_mantencion = ?"
        );

        $eliminar->execute([$idMantencion]);

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
            'ELIMINAR',
            'MANTENCION',
            $idMantencion,
            $detalleAuditoria,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $pdo->commit();

        header('Location: index.php?eliminado=1');
        exit;

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensaje = 'No fue posible eliminar la mantención.';
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

    <title>Eliminar mantención - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Mantenciones</p>
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
            Volver a mantenciones
        </a>

    </div>

</header>

<main class="equipos-content">

    <div
        class="formulario-equipo"
        style="max-width: 750px; margin: 40px auto;"
    >

        <h2>Eliminar mantención</h2>

        <p>
            Está a punto de eliminar la siguiente mantención:
        </p>

        <div
            style="
                margin-top: 20px;
                padding: 20px;
                background: #f1f3f5;
                border-radius: 8px;
            "
        >

            <p>
                <strong>Equipo:</strong>
                <?php echo htmlspecialchars($mantencion['nombre_equipo']); ?>
            </p>

            <p>
                <strong>Número de inventario:</strong>
                <?php echo htmlspecialchars(
                    $mantencion['numero_inventario'] ?? '-'
                ); ?>
            </p>

            <p>
                <strong>Tipo:</strong>
                <?php echo htmlspecialchars($mantencion['tipo']); ?>
            </p>

            <p>
                <strong>Estado:</strong>
                <?php echo htmlspecialchars($mantencion['estado']); ?>
            </p>

            <p>
                <strong>Detalle:</strong>
                <?php echo htmlspecialchars($mantencion['detalle']); ?>
            </p>

            <p>
                <strong>Fecha:</strong>
                <?php echo htmlspecialchars($mantencion['fecha']); ?>
            </p>

        </div>

        <?php if ($mensaje !== ''): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>

        <?php endif; ?>

        <p style="margin-top: 20px;">

            <strong>Advertencia:</strong>

            esta acción eliminará permanentemente el registro de la mantención.

        </p>

        <div
            style="
                display: flex;
                gap: 10px;
                margin-top: 20px;
            "
        >

            <form method="POST">

                <button
                    type="submit"
                    class="btn-login"
                    style="width: auto; margin: 0;"
                >
                    Eliminar definitivamente
                </button>

            </form>

            <a
                href="index.php"
                class="btn-primary"
                style="
                    width: auto;
                    display: inline-flex;
                    align-items: center;
                    text-decoration: none;
                "
            >
                Cancelar
            </a>

        </div>

    </div>

</main>

</body>

</html>