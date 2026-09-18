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

$idUbicacion = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idUbicacion || $idUbicacion <= 0) {
    header('Location: index.php');
    exit;
}

/* Obtener ubicación */
$stmt = $pdo->prepare(
    "SELECT
        u.id_ubicacion,
        u.nombre,
        u.detalle,
        u.activo,
        s.nombre AS servicio
     FROM ubicaciones u
     INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
     WHERE u.id_ubicacion = ?
     LIMIT 1"
);

$stmt->execute([$idUbicacion]);

$ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ubicacion) {
    header('Location: index.php');
    exit;
}

/* Equipos actualmente asociados */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM equipos
     WHERE id_ubicacion = ?"
);

$stmt->execute([$idUbicacion]);

$totalEquipos = (int)$stmt->fetchColumn();

/* Movimientos donde aparece como origen */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM movimientos
     WHERE id_ubicacion_origen = ?"
);

$stmt->execute([$idUbicacion]);

$totalOrigen = (int)$stmt->fetchColumn();

/* Movimientos donde aparece como destino */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM movimientos
     WHERE id_ubicacion_destino = ?"
);

$stmt->execute([$idUbicacion]);

$totalDestino = (int)$stmt->fetchColumn();

$tieneRelaciones =
    $totalEquipos > 0 ||
    $totalOrigen > 0 ||
    $totalDestino > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneRelaciones) {

        $error =
            'La ubicación no puede eliminarse porque posee registros asociados.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalleAuditoria =
                'Ubicación eliminada: ' .
                $ubicacion['nombre'] .
                ' | Servicio: ' .
                $ubicacion['servicio'];

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
                'UBICACION',
                $idUbicacion,
                $detalleAuditoria,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM ubicaciones
                 WHERE id_ubicacion = ?"
            );

            $stmt->execute([$idUbicacion]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    'No fue posible eliminar la ubicación.'
                );
            }

            $pdo->commit();

            header('Location: index.php?eliminado=1');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'No fue posible eliminar la ubicación.';
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

    <title>Eliminar ubicación - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .ubicacion-page {
            padding: 35px 20px;
        }

        .ubicacion-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .ubicacion-card h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .ubicacion-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .ubicacion-datos {
            background: #f4f5f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .ubicacion-datos p {
            margin: 8px 0;
        }

        .ubicacion-advertencia {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
        }

        .ubicacion-error {
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 6px;
        }

        .ubicacion-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .ubicacion-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

    </style>

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Ubicaciones</p>
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
            Volver
        </a>

    </div>

</header>

<main class="ubicacion-page">

    <div class="ubicacion-card">

        <h2>Eliminar ubicación</h2>

        <p class="ubicacion-descripcion">
            Revisa la información antes de continuar.
        </p>

        <div class="ubicacion-datos">

            <p>
                <strong>Servicio:</strong>
                <?php
                echo htmlspecialchars(
                    $ubicacion['servicio']
                );
                ?>
            </p>

            <p>
                <strong>Ubicación:</strong>
                <?php
                echo htmlspecialchars(
                    $ubicacion['nombre']
                );
                ?>
            </p>

            <p>
                <strong>Detalle:</strong>
                <?php
                echo htmlspecialchars(
                    $ubicacion['detalle']
                );
                ?>
            </p>

            <p>
                <strong>Estado:</strong>
                <?php
                echo $ubicacion['activo']
                    ? 'Activo'
                    : 'Inactivo';
                ?>
            </p>

        </div>

        <?php if ($tieneRelaciones): ?>

            <div class="ubicacion-advertencia">

                <strong>
                    Esta ubicación no puede eliminarse.
                </strong>

                <p>
                    Posee información asociada que debe
                    conservarse.
                </p>

                <p>
                    Equipos asociados:
                    <strong>
                        <?php echo $totalEquipos; ?>
                    </strong>
                </p>

                <p>
                    Movimientos como origen:
                    <strong>
                        <?php echo $totalOrigen; ?>
                    </strong>
                </p>

                <p>
                    Movimientos como destino:
                    <strong>
                        <?php echo $totalDestino; ?>
                    </strong>
                </p>

                <p>
                    Puedes modificarla y dejarla como Inactiva.
                </p>

            </div>

        <?php else: ?>

            <div class="ubicacion-advertencia">

                <strong>Advertencia:</strong>

                <p>
                    Esta acción eliminará permanentemente
                    la ubicación.
                </p>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="ubicacion-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <?php if (!$tieneRelaciones): ?>

            <form method="POST">

                <div class="ubicacion-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de eliminar esta ubicación?');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="ubicacion-cancelar"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <div class="ubicacion-actions">

                <a
                    href="editar.php?id=<?php echo $idUbicacion; ?>"
                    class="btn-primary"
                >
                    Modificar ubicación
                </a>

                <a
                    href="index.php"
                    class="ubicacion-cancelar"
                >
                    Volver
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>