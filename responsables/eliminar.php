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

$idResponsable = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idResponsable || $idResponsable <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_responsable,
        nombre,
        cargo,
        activo
     FROM responsables
     WHERE id_responsable = ?
     LIMIT 1"
);

$stmt->execute([$idResponsable]);

$responsable = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$responsable) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Revisar si el responsable está asociado a equipos
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM equipos
     WHERE id_responsable = ?"
);

$stmt->execute([$idResponsable]);

$totalEquipos = (int)$stmt->fetchColumn();

$tieneEquipos = $totalEquipos > 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($tieneEquipos) {

        $error =
            'El responsable no puede eliminarse porque posee equipos asociados.';

    } else {

        try {

            $pdo->beginTransaction();

            $detalleAuditoria =
                'Responsable eliminado: ' .
                $responsable['nombre'] .
                ' | Cargo: ' .
                $responsable['cargo'];

            $direccionIp =
                $_SERVER['REMOTE_ADDR'] ?? null;

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
                'RESPONSABLE',
                $idResponsable,
                $detalleAuditoria,
                $direccionIp
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM responsables
                 WHERE id_responsable = ?"
            );

            $stmt->execute([
                $idResponsable
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    'No fue posible eliminar el responsable.'
                );
            }

            $pdo->commit();

            header(
                'Location: index.php?eliminado=1'
            );
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'No fue posible eliminar el responsable.';
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

    <title>Eliminar responsable - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .responsable-page {
            padding: 35px 20px;
        }

        .responsable-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .responsable-card h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .responsable-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .responsable-datos {
            background: #f4f5f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .responsable-datos p {
            margin: 8px 0;
        }

        .responsable-advertencia {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 6px;
        }

        .responsable-error {
            margin: 20px 0;
            padding: 15px;
            background: #f8d7da;
            border-radius: 6px;
        }

        .responsable-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .responsable-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        @media (max-width: 600px) {

            .responsable-card {
                padding: 20px;
            }

            .responsable-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .responsable-actions button,
            .responsable-actions a {
                text-align: center;
            }
        }

    </style>

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>

        <h1>SIGIC-HHHA</h1>

        <p>Gestión de Responsables</p>

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
            Volver
        </a>

    </div>

</header>

<main class="responsable-page">

    <div class="responsable-card">

        <h2>Eliminar responsable</h2>

        <p class="responsable-descripcion">
            Revisa la información antes de continuar.
        </p>

        <div class="responsable-datos">

            <p>
                <strong>Nombre:</strong>
                <?php
                echo htmlspecialchars(
                    $responsable['nombre']
                );
                ?>
            </p>

            <p>
                <strong>Cargo:</strong>
                <?php
                echo htmlspecialchars(
                    $responsable['cargo']
                );
                ?>
            </p>

            <p>
                <strong>Estado:</strong>
                <?php
                echo $responsable['activo']
                    ? 'Activo'
                    : 'Inactivo';
                ?>
            </p>

        </div>

        <?php if ($tieneEquipos): ?>

            <div class="responsable-advertencia">

                <strong>
                    Este responsable no puede eliminarse.
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

                <p>
                    Puedes modificarlo y dejarlo
                    como Inactivo.
                </p>

            </div>

        <?php else: ?>

            <div class="responsable-advertencia">

                <strong>Advertencia:</strong>

                <p>
                    Esta acción eliminará permanentemente
                    al responsable.
                </p>

            </div>

        <?php endif; ?>

        <?php if ($error !== ''): ?>

            <div class="responsable-error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>

        <?php if (!$tieneEquipos): ?>

            <form method="POST">

                <div class="responsable-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                        onclick="return confirm('¿Está seguro de eliminar este responsable? Esta acción no se puede deshacer.');"
                    >
                        Eliminar definitivamente
                    </button>

                    <a
                        href="index.php"
                        class="responsable-cancelar"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        <?php else: ?>

            <div class="responsable-actions">

                <a
                    href="editar.php?id=<?php echo $idResponsable; ?>"
                    class="btn-primary"
                >
                    Modificar responsable
                </a>

                <a
                    href="index.php"
                    class="responsable-cancelar"
                >
                    Volver
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>