<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';

/* Obtener equipos */
$equipos = $pdo->query(
    "SELECT
        e.id_equipo,
        e.nombre_equipo,
        u.nombre AS ubicacion_actual,
        s.nombre AS servicio
    FROM equipos e
    INNER JOIN ubicaciones u
        ON e.id_ubicacion = u.id_ubicacion
    INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
    ORDER BY e.nombre_equipo"
)->fetchAll();


/* Obtener ubicaciones activas */
$ubicaciones = $pdo->query(
    "SELECT
        u.id_ubicacion,
        u.nombre AS ubicacion,
        s.nombre AS servicio
    FROM ubicaciones u
    INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
    WHERE u.activo = 1
    ORDER BY s.nombre, u.nombre"
)->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idEquipo = !empty($_POST['id_equipo'])
        ? (int) $_POST['id_equipo']
        : 0;

    $idDestino = !empty($_POST['id_ubicacion_destino'])
        ? (int) $_POST['id_ubicacion_destino']
        : 0;

    $observacion = trim($_POST['observacion'] ?? '');

    if (!$idEquipo) {

        $mensaje = 'Debes seleccionar un equipo.';

    } elseif (!$idDestino) {

        $mensaje = 'Debes seleccionar una ubicación de destino.';

    } else {

        /* Obtener ubicación actual del equipo */
        $consultaEquipo = $pdo->prepare(
            "SELECT
                id_equipo,
                nombre_equipo,
                id_ubicacion
             FROM equipos
             WHERE id_equipo = ?
             LIMIT 1"
        );

        $consultaEquipo->execute([$idEquipo]);
        $equipoSeleccionado = $consultaEquipo->fetch();

        if (!$equipoSeleccionado) {

            $mensaje = 'El equipo seleccionado no existe.';

        } elseif (
            (int) $equipoSeleccionado['id_ubicacion']
            === $idDestino
        ) {

            $mensaje =
                'La ubicación de destino debe ser diferente de la ubicación actual.';

        } else {

            /* Verificar destino */
            $consultaDestino = $pdo->prepare(
                "SELECT id_ubicacion
                 FROM ubicaciones
                 WHERE id_ubicacion = ?
                   AND activo = 1
                 LIMIT 1"
            );

            $consultaDestino->execute([$idDestino]);

            if (!$consultaDestino->fetch()) {

                $mensaje = 'La ubicación de destino no es válida.';

            } else {

                try {

                    $pdo->beginTransaction();

                    $idOrigen =
                        (int) $equipoSeleccionado['id_ubicacion'];

                    /* Registrar movimiento */
                    $insertarMovimiento = $pdo->prepare(
                        "INSERT INTO movimientos (
                            id_equipo,
                            id_ubicacion_origen,
                            id_ubicacion_destino,
                            id_usuario,
                            fecha_movimiento,
                            observacion
                        )
                        VALUES (?, ?, ?, ?, NOW(), ?)"
                    );

                    $insertarMovimiento->execute([
                        $idEquipo,
                        $idOrigen,
                        $idDestino,
                        $_SESSION['id_usuario'],
                        $observacion !== ''
                            ? $observacion
                            : null
                    ]);

                    $idMovimiento = $pdo->lastInsertId();


                    /* Actualizar ubicación actual */
                    $actualizarEquipo = $pdo->prepare(
                        "UPDATE equipos
                         SET id_ubicacion = ?
                         WHERE id_equipo = ?"
                    );

                    $actualizarEquipo->execute([
                        $idDestino,
                        $idEquipo
                    ]);


                    /* Auditoría */
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
                        'MOVER',
                        'equipos',
                        $idEquipo,
                        'Movimiento registrado. Origen ID '
                            . $idOrigen
                            . ', destino ID '
                            . $idDestino
                            . '.',
                        $_SERVER['REMOTE_ADDR'] ?? null
                    ]);

                    $pdo->commit();

                    header(
                        'Location: index.php?creado=1'
                    );
                    exit;

                } catch (PDOException $e) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $mensaje =
                        'No fue posible registrar el movimiento.';
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

    <title>Registrar movimiento - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Movimiento de Equipos</p>
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
            href="../dashboard.php"
            class="logout-link"
        >
            Volver al inicio
        </a>

    </div>

</header>


<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Registrar movimiento</h2>

            <p>
                Trasladar un computador a otra ubicación.
            </p>

        </div>

    </div>


    <?php if ($mensaje !== ''): ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        class="formulario-equipo"
    >

        <div class="form-group">

            <label for="id_equipo">
                Equipo *
            </label>

            <select
                id="id_equipo"
                name="id_equipo"
                required
            >

                <option value="">
                    Seleccione
                </option>

                <?php foreach ($equipos as $equipo): ?>

                    <option
                        value="<?php echo $equipo['id_equipo']; ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $equipo['nombre_equipo']
                            . ' - '
                            . $equipo['servicio']
                            . ' / '
                            . $equipo['ubicacion_actual']
                        );
                        ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label for="id_ubicacion_destino">
                Nueva ubicación *
            </label>

            <select
                id="id_ubicacion_destino"
                name="id_ubicacion_destino"
                required
            >

                <option value="">
                    Seleccione
                </option>

                <?php foreach ($ubicaciones as $ubicacion): ?>

                    <option
                        value="<?php echo $ubicacion['id_ubicacion']; ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $ubicacion['servicio']
                            . ' - '
                            . $ubicacion['ubicacion']
                        );
                        ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label for="observacion">
                Observación
            </label>

            <textarea
                id="observacion"
                name="observacion"
                rows="4"
                placeholder="Ej: Traslado temporal a bodega"
            ></textarea>

        </div>


        <button
            type="submit"
            class="btn-login"
        >
            Registrar movimiento
        </button>

    </form>

</main>

</body>

</html>