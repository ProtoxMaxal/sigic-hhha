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

$idSo = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idSo || $idSo <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_so,
        nombre,
        version,
        arquitectura
     FROM sistemas_operativos
     WHERE id_so = ?
     LIMIT 1"
);

$stmt->execute([$idSo]);

$sistema = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sistema) {
    header('Location: index.php');
    exit;
}

/* Verificar si existen equipos utilizando este sistema operativo */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM equipos
     WHERE id_so = ?"
);

$stmt->execute([$idSo]);

$totalEquipos = (int)$stmt->fetchColumn();

$tieneEquipos = $totalEquipos > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneEquipos) {

        $error =
            'El sistema operativo no puede eliminarse porque posee equipos asociados.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalle =
                'Sistema operativo eliminado: ' .
                $sistema['nombre'] .
                ' | Versión: ' .
                $sistema['version'] .
                ' | Arquitectura: ' .
                $sistema['arquitectura'];

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
                'SISTEMA_OPERATIVO',
                $idSo,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM sistemas_operativos
                 WHERE id_so = ?"
            );

            $stmt->execute([$idSo]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    'No fue posible eliminar el sistema operativo.'
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
                'No fue posible eliminar el sistema operativo.';
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

    <title>Eliminar sistema operativo - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .so-page {
            padding: 35px 20px;
        }

        .so-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .so-card h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .so-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .so-datos {
            background: #f4f5f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .so-datos p {
            margin: 8px 0;
        }

        .so-advertencia {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
        }

        .so-error {
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 6px;
        }

        .so-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .so-cancelar {
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
        <p>Gestión de Sistemas Operativos</p>
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

<main class="so-page">

    <div class="so-card">

        <h2>Eliminar sistema operativo</h2>

        <p class="so-descripcion">
            Revisa la información antes de continuar.
        </p>

        <div class="so-datos">

            <p>
                <strong>Nombre:</strong>
                <?php echo htmlspecialchars($sistema['nombre']); ?>
            </p>

            <p>
                <strong>Versión:</strong>
                <?php echo htmlspecialchars($sistema['version']); ?>
            </p>

            <p>
                <strong>Arquitectura:</strong>
                <?php echo htmlspecialchars($sistema['arquitectura']); ?>
            </p>

        </div>

        <?php if ($tieneEquipos): ?>

            <div class="so-advertencia">

                <strong>
                    Este sistema operativo no puede eliminarse.
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

            <div class="so-advertencia">

                <strong>Advertencia:</strong>

                <p>
                    Esta acción eliminará permanentemente
                    el sistema operativo.
                </p>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="so-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <?php if (!$tieneEquipos): ?>

            <form method="POST">

                <div class="so-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de eliminar este sistema operativo?');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="so-cancelar"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <div class="so-actions">

                <a
                    href="index.php"
                    class="so-cancelar"
                >
                    Volver
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>