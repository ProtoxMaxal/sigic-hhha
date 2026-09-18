<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

/* Solo Administrador puede eliminar movimientos */
if (
    !isset($_SESSION['rol']) ||
    strtolower(trim($_SESSION['rol'])) !== 'administrador'
) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$idMovimiento = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($idMovimiento <= 0) {
    header('Location: index.php');
    exit;
}

$consulta = $pdo->prepare(
    "SELECT
        m.id_movimiento,
        m.id_equipo,
        m.id_ubicacion_origen,
        m.id_ubicacion_destino,
        m.fecha_movimiento,
        m.observacion,
        e.nombre_equipo,
        e.numero_inventario,
        origen.nombre AS ubicacion_origen,
        destino.nombre AS ubicacion_destino
    FROM movimientos m
    INNER JOIN equipos e
        ON m.id_equipo = e.id_equipo
    LEFT JOIN ubicaciones origen
        ON m.id_ubicacion_origen = origen.id_ubicacion
    INNER JOIN ubicaciones destino
        ON m.id_ubicacion_destino = destino.id_ubicacion
    WHERE m.id_movimiento = ?
    LIMIT 1"
);

$consulta->execute([$idMovimiento]);

$movimiento = $consulta->fetch();

if (!$movimiento) {
    header('Location: index.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();

        $detalleAuditoria =
            'Movimiento eliminado. Equipo: '
            . $movimiento['nombre_equipo']
            . ' | Inventario: '
            . ($movimiento['numero_inventario'] ?? 'Sin inventario')
            . ' | Origen: '
            . ($movimiento['ubicacion_origen'] ?? 'Sin ubicación anterior')
            . ' | Destino: '
            . $movimiento['ubicacion_destino'];

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
            'MOVIMIENTO',
            $idMovimiento,
            $detalleAuditoria,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $eliminar = $pdo->prepare(
            "DELETE FROM movimientos
             WHERE id_movimiento = ?"
        );

        $eliminar->execute([$idMovimiento]);

        $pdo->commit();

        header('Location: index.php?eliminado=1');
        exit;

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensaje = 'No fue posible eliminar el movimiento.';
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

    <title>Eliminar movimiento - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .eliminar-container {
            max-width: 750px;
            margin: 40px auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        .eliminar-container h2 {
            margin-top: 0;
            margin-bottom: 4px;
        }

        .eliminar-container > p {
            margin-top: 0;
        }

        .eliminar-detalle {
            background: #f1f3f5;
            padding: 20px;
            border-radius: 8px;
            margin: 22px 0;
        }

        .eliminar-detalle p {
            margin: 3px 0;
        }

        .eliminar-advertencia {
            margin: 20px 0;
        }

        .eliminar-acciones {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
        }

        .eliminar-acciones button,
        .eliminar-acciones a {
            display: inline-block;
            width: auto;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .eliminar-acciones button {
            border: 0;
        }

    </style>

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Movimientos</p>
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
            Volver a movimientos
        </a>

    </div>

</header>

<main>

    <div class="eliminar-container">

        <h2>Eliminar movimiento</h2>

        <p>
            Está a punto de eliminar el siguiente movimiento:
        </p>

        <?php if ($mensaje !== ''): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>

        <?php endif; ?>

        <div class="eliminar-detalle">

            <p>
                <strong>Equipo:</strong>
                <?php echo htmlspecialchars($movimiento['nombre_equipo']); ?>
            </p>

            <p>
                <strong>Número de inventario:</strong>
                <?php echo htmlspecialchars(
                    $movimiento['numero_inventario'] ?? 'Sin inventario'
                ); ?>
            </p>

            <p>
                <strong>Origen:</strong>
                <?php echo htmlspecialchars(
                    $movimiento['ubicacion_origen']
                    ?? 'Sin ubicación anterior'
                ); ?>
            </p>

            <p>
                <strong>Destino:</strong>
                <?php echo htmlspecialchars(
                    $movimiento['ubicacion_destino']
                ); ?>
            </p>

            <p>
                <strong>Fecha:</strong>
                <?php echo htmlspecialchars(
                    $movimiento['fecha_movimiento']
                ); ?>
            </p>

            <p>
                <strong>Observación:</strong>
                <?php echo htmlspecialchars(
                    $movimiento['observacion'] ?? '-'
                ); ?>
            </p>

        </div>

        <p class="eliminar-advertencia">
            <strong>Advertencia:</strong>
            esta acción eliminará permanentemente el registro del movimiento.
        </p>

        <form method="POST">

            <div class="eliminar-acciones">

                <button
                    type="submit"
                    class="btn-login"
                >
                    Eliminar definitivamente
                </button>

                <a
                    href="index.php"
                    class="btn-small"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>