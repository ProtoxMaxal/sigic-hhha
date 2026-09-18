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

$idMarca = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idMarca || $idMarca <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_marca,
        nombre
     FROM marcas
     WHERE id_marca = ?
     LIMIT 1"
);

$stmt->execute([$idMarca]);

$marca = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$marca) {
    header('Location: index.php');
    exit;
}

/* Verificar equipos asociados */
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM equipos
     WHERE id_marca = ?"
);

$stmt->execute([$idMarca]);

$totalEquipos = (int)$stmt->fetchColumn();

$tieneEquipos = $totalEquipos > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneEquipos) {

        $error =
            'La marca no puede eliminarse porque posee equipos asociados.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalle =
                'Marca eliminada: ' .
                $marca['nombre'];

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
                'MARCA',
                $idMarca,
                $detalle,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM marcas
                 WHERE id_marca = ?"
            );

            $stmt->execute([$idMarca]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    'No fue posible eliminar la marca.'
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
                'No fue posible eliminar la marca.';
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

    <title>Eliminar marca - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .marca-page {
            padding: 35px 20px;
        }

        .marca-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .marca-card h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .marca-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .marca-datos {
            background: #f4f5f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .marca-datos p {
            margin: 8px 0;
        }

        .marca-advertencia {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
        }

        .marca-error {
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 6px;
        }

        .marca-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .marca-cancelar {
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
        <p>Gestión de Marcas</p>
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

<main class="marca-page">

    <div class="marca-card">

        <h2>Eliminar marca</h2>

        <p class="marca-descripcion">
            Revisa la información antes de continuar.
        </p>

        <div class="marca-datos">

            <p>
                <strong>Marca:</strong>
                <?php
                echo htmlspecialchars(
                    $marca['nombre']
                );
                ?>
            </p>

        </div>

        <?php if ($tieneEquipos): ?>

            <div class="marca-advertencia">

                <strong>
                    Esta marca no puede eliminarse.
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

            <div class="marca-advertencia">

                <strong>Advertencia:</strong>

                <p>
                    Esta acción eliminará permanentemente
                    la marca.
                </p>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="marca-error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>

        <?php if (!$tieneEquipos): ?>

            <form method="POST">

                <div class="marca-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de eliminar esta marca?');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="marca-cancelar"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <div class="marca-actions">

                <a
                    href="index.php"
                    class="marca-cancelar"
                >
                    Volver
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>