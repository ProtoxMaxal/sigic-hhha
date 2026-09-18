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

/* Servicios activos disponibles */
$consultaServicios = $pdo->query(
    "SELECT
        id_servicio,
        nombre
     FROM servicios
     WHERE activo = 1
     ORDER BY nombre"
);

$servicios = $consultaServicios->fetchAll(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idServicio = !empty($_POST['id_servicio'])
        ? (int)$_POST['id_servicio']
        : 0;

    $nombre = trim($_POST['nombre'] ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (!$idServicio) {

        $error = 'Debes seleccionar un servicio.';

    } elseif ($nombre === '') {

        $error = 'Debes ingresar el nombre de la ubicación.';

    } elseif ($detalle === '') {

        $error = 'Debes ingresar un detalle de la ubicación.';

    } else {

        /* Verificar que el servicio exista y esté activo */
        $verificarServicio = $pdo->prepare(
            "SELECT id_servicio
             FROM servicios
             WHERE id_servicio = ?
             AND activo = 1
             LIMIT 1"
        );

        $verificarServicio->execute([$idServicio]);

        if (!$verificarServicio->fetch()) {

            $error = 'El servicio seleccionado no es válido.';

        } else {

            /* Evitar ubicación duplicada dentro del mismo servicio */
            $verificar = $pdo->prepare(
                "SELECT id_ubicacion
                 FROM ubicaciones
                 WHERE id_servicio = ?
                 AND nombre = ?
                 LIMIT 1"
            );

            $verificar->execute([
                $idServicio,
                $nombre
            ]);

            if ($verificar->fetch()) {

                $error =
                    'Ya existe una ubicación con ese nombre en el servicio seleccionado.';

            } else {

                try {

                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare(
                        "INSERT INTO ubicaciones
                            (
                                id_servicio,
                                nombre,
                                detalle,
                                activo
                            )
                         VALUES
                            (?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $idServicio,
                        $nombre,
                        $detalle,
                        $activo
                    ]);

                    $idUbicacion = $pdo->lastInsertId();

                    $stmtServicio = $pdo->prepare(
                        "SELECT nombre
                         FROM servicios
                         WHERE id_servicio = ?"
                    );

                    $stmtServicio->execute([$idServicio]);

                    $nombreServicio =
                        $stmtServicio->fetchColumn();

                    $detalleAuditoria =
                        'Ubicación creada: ' .
                        $nombre .
                        ' | Servicio: ' .
                        $nombreServicio .
                        ' | Estado: ' .
                        ($activo ? 'Activo' : 'Inactivo');

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
                        'UBICACION',
                        $idUbicacion,
                        $detalleAuditoria,
                        $_SERVER['REMOTE_ADDR'] ?? null
                    ]);

                    $pdo->commit();

                    header('Location: index.php?creado=1');
                    exit;

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $error =
                        'No fue posible crear la ubicación.';
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

    <title>Crear ubicación - SIGIC-HHHA</title>

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
            margin-bottom: 5px;
        }

        .ubicacion-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .ubicacion-form-group {
            margin-bottom: 20px;
        }

        .ubicacion-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .ubicacion-form-group input,
        .ubicacion-form-group select,
        .ubicacion-form-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            font-family: Arial, sans-serif;
            background: #ffffff;
        }

        .ubicacion-form-group input:focus,
        .ubicacion-form-group select:focus,
        .ubicacion-form-group textarea:focus {
            outline: none;
            border-color: #555;
        }

        .ubicacion-form-group textarea {
            min-height: 95px;
            resize: vertical;
        }

        .ubicacion-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0 25px;
        }

        .ubicacion-check input {
            width: 17px;
            height: 17px;
        }

        .ubicacion-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .ubicacion-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        .ubicacion-error {
            padding: 12px;
            margin-bottom: 20px;
            background: #f5dddd;
            border-radius: 6px;
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

        <h2>Registrar ubicación</h2>

        <p class="ubicacion-descripcion">
            Ingresa una nueva ubicación y asígnala a un servicio.
        </p>

        <?php if ($error !== ''): ?>

            <div class="ubicacion-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="ubicacion-form-group">

                <label for="id_servicio">
                    Servicio *
                </label>

                <select
                    id="id_servicio"
                    name="id_servicio"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <?php foreach ($servicios as $servicio): ?>

                        <option
                            value="<?php echo $servicio['id_servicio']; ?>"
                            <?php
                            echo
                            (int)($_POST['id_servicio'] ?? 0) ===
                            (int)$servicio['id_servicio']
                                ? 'selected'
                                : '';
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                $servicio['nombre']
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="ubicacion-form-group">

                <label for="nombre">
                    Nombre de la ubicación *
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="150"
                    required
                    value="<?php
                    echo htmlspecialchars(
                        $_POST['nombre'] ?? ''
                    );
                    ?>"
                >

            </div>

            <div class="ubicacion-form-group">

                <label for="detalle">
                    Detalle *
                </label>

                <textarea
                    id="detalle"
                    name="detalle"
                    maxlength="255"
                    required
                ><?php
                echo htmlspecialchars(
                    $_POST['detalle'] ?? ''
                );
                ?></textarea>

            </div>

            <label class="ubicacion-check">

                <input
                    type="checkbox"
                    name="activo"
                    value="1"
                    <?php echo (
                        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                        isset($_POST['activo'])
                    ) ? 'checked' : ''; ?>
                >

                Ubicación activa

            </label>

            <div class="ubicacion-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar ubicación
                </button>

                <a
                    href="index.php"
                    class="ubicacion-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>