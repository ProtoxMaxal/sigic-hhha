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

$idEstado = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idEstado || $idEstado <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_estado,
        nombre,
        descripcion
     FROM estados_equipo
     WHERE id_estado = ?
     LIMIT 1"
);

$stmt->execute([$idEstado]);

$estado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estado) {
    header('Location: index.php');
    exit;
}

/* Verificar si existen equipos usando este estado */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM equipos
     WHERE id_estado = ?"
);

$stmt->execute([$idEstado]);

$totalEquipos = (int)$stmt->fetchColumn();

$tieneEquipos = $totalEquipos > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneEquipos) {

        $error =
            'El estado no puede eliminarse porque posee equipos asociados.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalle =
                'Estado de equipo eliminado: ' .
                $estado['nombre'];

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
                'ESTADO_EQUIPO',
                $idEstado,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM estados_equipo
                 WHERE id_estado = ?"
            );

            $stmt->execute([$idEstado]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    'No fue posible eliminar el estado.'
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
                'No fue posible eliminar el estado.';
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

    <title>Eliminar estado - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .estado-page {
            padding: 35px 20px;
        }

        .estado-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .estado-card h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .estado-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .estado-datos {
            background: #f4f5f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .estado-datos p {
            margin: 8px 0;
        }

        .estado-advertencia {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
        }

        .estado-error {
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 6px;
        }

        .estado-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .estado-cancelar {
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
        <p>Gestión de Estados de Equipo</p>
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

<main class="estado-page">

    <div class="estado-card">

        <h2>Eliminar estado</h2>

        <p class="estado-descripcion">
            Revisa la información antes de continuar.
        </p>

        <div class="estado-datos">

            <p>
                <strong>Estado:</strong>
                <?php echo htmlspecialchars($estado['nombre']); ?>
            </p>

            <p>
                <strong>Descripción:</strong>
                <?php echo htmlspecialchars($estado['descripcion']); ?>
            </p>

        </div>

        <?php if ($tieneEquipos): ?>

            <div class="estado-advertencia">

                <strong>
                    Este estado no puede eliminarse.
                </strong>

                <p>
                    Tiene equipos asociados actualmente.
                </p>

                <p>
                    Equipos asociados:
                    <strong>
                        <?php echo $totalEquipos; ?>
                    </strong>
                </p>

            </div>

        <?php else: ?>

            <div class="estado-advertencia">

                <strong>Advertencia:</strong>

                <p>
                    Esta acción eliminará permanentemente
                    el estado.
                </p>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="estado-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <?php if (!$tieneEquipos): ?>

            <form method="POST">

                <div class="estado-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de eliminar este estado?');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="estado-cancelar"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <div class="estado-actions">

                <a
                    href="index.php"
                    class="estado-cancelar"
                >
                    Volver
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>