<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
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
    "SELECT *
     FROM movimientos
     WHERE id_movimiento = ?
     LIMIT 1"
);

$consulta->execute([$idMovimiento]);

$movimiento = $consulta->fetch();

if (!$movimiento) {
    header('Location: index.php');
    exit;
}

$equipos = $pdo->query(
    "SELECT
        id_equipo,
        nombre_equipo,
        numero_inventario
     FROM equipos
     ORDER BY nombre_equipo"
)->fetchAll();

$ubicaciones = $pdo->query(
    "SELECT
        id_ubicacion,
        nombre
     FROM ubicaciones
     WHERE activo = 1
     ORDER BY nombre"
)->fetchAll();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idEquipo = isset($_POST['id_equipo'])
        ? (int) $_POST['id_equipo']
        : 0;

    $idOrigen = !empty($_POST['id_ubicacion_origen'])
        ? (int) $_POST['id_ubicacion_origen']
        : null;

    $idDestino = isset($_POST['id_ubicacion_destino'])
        ? (int) $_POST['id_ubicacion_destino']
        : 0;

    $observacion = trim($_POST['observacion'] ?? '');

    if ($idEquipo <= 0) {

        $mensaje = 'Debes seleccionar un equipo.';

    } elseif ($idDestino <= 0) {

        $mensaje = 'Debes seleccionar una ubicación de destino.';

    } elseif ($idOrigen !== null && $idOrigen === $idDestino) {

        $mensaje = 'La ubicación de origen y destino no pueden ser iguales.';

    } else {

        try {

            $pdo->beginTransaction();

            $actualizar = $pdo->prepare(
                "UPDATE movimientos
                 SET
                    id_equipo = ?,
                    id_ubicacion_origen = ?,
                    id_ubicacion_destino = ?,
                    observacion = ?
                 WHERE id_movimiento = ?"
            );

            $actualizar->execute([
                $idEquipo,
                $idOrigen,
                $idDestino,
                $observacion !== '' ? $observacion : null,
                $idMovimiento
            ]);

            $actualizarEquipo = $pdo->prepare(
                "UPDATE equipos
                 SET id_ubicacion = ?
                 WHERE id_equipo = ?"
            );

            $actualizarEquipo->execute([
                $idDestino,
                $idEquipo
            ]);

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
                'MODIFICAR',
                'MOVIMIENTO',
                $idMovimiento,
                'Movimiento modificado. Destino actualizado.',
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $pdo->commit();

            header('Location: index.php?modificado=1');
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensaje = 'No fue posible modificar el movimiento.';
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

    <title>Modificar movimiento - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

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

<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Modificar movimiento</h2>

            <p>
                Actualice la información del movimiento seleccionado.
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

                <?php foreach ($equipos as $equipo): ?>

                    <option
                        value="<?php echo $equipo['id_equipo']; ?>"
                        <?php
                        echo $equipo['id_equipo'] == $movimiento['id_equipo']
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php
                        echo htmlspecialchars(
                            $equipo['nombre_equipo']
                            . ' - '
                            . ($equipo['numero_inventario'] ?? 'Sin inventario')
                        );
                        ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="form-group">

            <label for="id_ubicacion_origen">
                Ubicación de origen
            </label>

            <select
                id="id_ubicacion_origen"
                name="id_ubicacion_origen"
            >

                <option value="">
                    Sin ubicación anterior
                </option>

                <?php foreach ($ubicaciones as $ubicacion): ?>

                    <option
                        value="<?php echo $ubicacion['id_ubicacion']; ?>"
                        <?php
                        echo $ubicacion['id_ubicacion'] == $movimiento['id_ubicacion_origen']
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php echo htmlspecialchars($ubicacion['nombre']); ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="form-group">

            <label for="id_ubicacion_destino">
                Ubicación de destino *
            </label>

            <select
                id="id_ubicacion_destino"
                name="id_ubicacion_destino"
                required
            >

                <?php foreach ($ubicaciones as $ubicacion): ?>

                    <option
                        value="<?php echo $ubicacion['id_ubicacion']; ?>"
                        <?php
                        echo $ubicacion['id_ubicacion'] == $movimiento['id_ubicacion_destino']
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php echo htmlspecialchars($ubicacion['nombre']); ?>
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
                rows="5"
            ><?php echo htmlspecialchars($movimiento['observacion'] ?? ''); ?></textarea>

        </div>

        <div class="acciones-formulario">

            <button
                type="submit"
                class="btn-login"
            >
                Guardar cambios
            </button>

            <a
                href="index.php"
                class="btn-small"
            >
                Cancelar
            </a>

        </div>

    </form>

</main>

</body>

</html>