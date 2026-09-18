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

$idServicio = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idServicio || $idServicio <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_servicio,
        nombre,
        activo
     FROM servicios
     WHERE id_servicio = ?
     LIMIT 1"
);

$stmt->execute([$idServicio]);

$servicio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servicio) {
    header('Location: index.php');
    exit;
}

/* Verificar ubicaciones asociadas */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM ubicaciones
     WHERE id_servicio = ?"
);

$stmt->execute([$idServicio]);

$totalUbicaciones = (int)$stmt->fetchColumn();

$tieneUbicaciones = $totalUbicaciones > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneUbicaciones) {

        $error =
            'El servicio no puede eliminarse porque posee ubicaciones asociadas.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalle =
                'Servicio eliminado: ' .
                $servicio['nombre'];

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
                'SERVICIO',
                $idServicio,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM servicios
                 WHERE id_servicio = ?"
            );

            $stmt->execute([$idServicio]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    'No fue posible eliminar el servicio.'
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
                'No fue posible eliminar el servicio.';
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

    <title>Eliminar servicio - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .servicio-page {
            padding: 35px 20px;
        }

        .servicio-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .servicio-card h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .servicio-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .servicio-datos {
            background: #f4f5f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .servicio-datos p {
            margin: 8px 0;
        }

        .servicio-advertencia {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
        }

        .servicio-error {
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 6px;
        }

        .servicio-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .servicio-cancelar {
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
        <p>Gestión de Servicios</p>
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

<main class="servicio-page">

    <div class="servicio-card">

        <h2>Eliminar servicio</h2>

        <p class="servicio-descripcion">
            Revisa la información antes de continuar.
        </p>

        <div class="servicio-datos">

            <p>
                <strong>Servicio:</strong>
                <?php echo htmlspecialchars($servicio['nombre']); ?>
            </p>

            <p>
                <strong>Estado:</strong>
                <?php echo $servicio['activo']
                    ? 'Activo'
                    : 'Inactivo'; ?>
            </p>

        </div>

        <?php if ($tieneUbicaciones): ?>

            <div class="servicio-advertencia">

                <strong>
                    Este servicio no puede eliminarse.
                </strong>

                <p>
                    Tiene ubicaciones asociadas actualmente.
                </p>

                <p>
                    Ubicaciones asociadas:
                    <strong>
                        <?php echo $totalUbicaciones; ?>
                    </strong>
                </p>

                <p>
                    Puedes modificarlo y dejarlo como Inactivo.
                </p>

            </div>

        <?php else: ?>

            <div class="servicio-advertencia">

                <strong>Advertencia:</strong>

                <p>
                    Esta acción eliminará permanentemente
                    el servicio.
                </p>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="servicio-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <?php if (!$tieneUbicaciones): ?>

            <form method="POST">

                <div class="servicio-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de eliminar este servicio?');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="servicio-cancelar"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <div class="servicio-actions">

                <a
                    href="editar.php?id=<?php echo $idServicio; ?>"
                    class="btn-primary"
                >
                    Modificar servicio
                </a>

                <a
                    href="index.php"
                    class="servicio-cancelar"
                >
                    Volver
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>