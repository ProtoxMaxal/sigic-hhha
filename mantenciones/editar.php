<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$idMantencion = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($idMantencion <= 0) {
    header('Location: index.php');
    exit;
}

$consulta = $pdo->prepare(
    "SELECT
        m.id_mantencion,
        m.id_equipo,
        m.fecha,
        m.tipo,
        m.detalle,
        m.estado,
        e.nombre_equipo,
        e.numero_inventario
    FROM mantenciones m
    INNER JOIN equipos e
        ON m.id_equipo = e.id_equipo
    WHERE m.id_mantencion = ?
    LIMIT 1"
);

$consulta->execute([$idMantencion]);

$mantencion = $consulta->fetch();

if (!$mantencion) {
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

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idEquipo = isset($_POST['id_equipo'])
        ? (int) $_POST['id_equipo']
        : 0;

    $tipo = trim($_POST['tipo'] ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    if ($idEquipo <= 0) {

        $mensaje = 'Debes seleccionar un equipo.';

    } elseif ($tipo === '') {

        $mensaje = 'Debes ingresar el tipo de mantención.';

    } elseif ($detalle === '') {

        $mensaje = 'Debes ingresar el detalle de la mantención.';

    } elseif ($estado === '') {

        $mensaje = 'Debes seleccionar un estado.';

    } else {

        $consultaEquipo = $pdo->prepare(
            "SELECT id_equipo
             FROM equipos
             WHERE id_equipo = ?
             LIMIT 1"
        );

        $consultaEquipo->execute([$idEquipo]);

        if (!$consultaEquipo->fetch()) {

            $mensaje = 'El equipo seleccionado no existe.';

        } else {

            try {

                $pdo->beginTransaction();

                $actualizar = $pdo->prepare(
                    "UPDATE mantenciones
                     SET
                        id_equipo = ?,
                        tipo = ?,
                        detalle = ?,
                        estado = ?
                     WHERE id_mantencion = ?"
                );

                $actualizar->execute([
                    $idEquipo,
                    $tipo,
                    $detalle,
                    $estado,
                    $idMantencion
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
                    'MANTENCION',
                    $idMantencion,
                    'Mantención modificada. Tipo: ' .
                    $tipo .
                    ' | Estado: ' .
                    $estado,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                $pdo->commit();

                header('Location: index.php?modificado=1');
                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $mensaje = 'No fue posible modificar la mantención.';
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

    <title>Modificar mantención - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Mantenciones</p>
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
            Volver a mantenciones
        </a>

    </div>

</header>

<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Modificar mantención</h2>

            <p>
                Actualice la información de la mantención seleccionada.
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
                    Seleccione un equipo
                </option>

                <?php foreach ($equipos as $equipo): ?>

                    <option
                        value="<?php echo $equipo['id_equipo']; ?>"
                        <?php
                        echo (
                            (int) $mantencion['id_equipo']
                            ===
                            (int) $equipo['id_equipo']
                        )
                            ? 'selected'
                            : '';
                        ?>
                    >
                        <?php
                        echo htmlspecialchars(
                            $equipo['nombre_equipo'] .
                            ' - ' .
                            ($equipo['numero_inventario'] ?? 'Sin inventario')
                        );
                        ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="form-group">

            <label for="tipo">
                Tipo *
            </label>

            <input
                type="text"
                id="tipo"
                name="tipo"
                value="<?php echo htmlspecialchars($mantencion['tipo']); ?>"
                required
            >

        </div>

        <div class="form-group">

            <label for="estado">
                Estado *
            </label>

            <select
                id="estado"
                name="estado"
                required
            >

                <option
                    value="Pendiente"
                    <?php echo $mantencion['estado'] === 'Pendiente' ? 'selected' : ''; ?>
                >
                    Pendiente
                </option>

                <option
                    value="En proceso"
                    <?php echo $mantencion['estado'] === 'En proceso' ? 'selected' : ''; ?>
                >
                    En proceso
                </option>

                <option
                    value="Finalizada"
                    <?php echo $mantencion['estado'] === 'Finalizada' ? 'selected' : ''; ?>
                >
                    Finalizada
                </option>

                <option
                    value="Cancelada"
                    <?php echo $mantencion['estado'] === 'Cancelada' ? 'selected' : ''; ?>
                >
                    Cancelada
                </option>

            </select>

        </div>

        <div class="form-group">

            <label for="detalle">
                Detalle *
            </label>

            <textarea
                id="detalle"
                name="detalle"
                rows="5"
                required
            ><?php echo htmlspecialchars($mantencion['detalle']); ?></textarea>

        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">

    <button
        type="submit"
        class="btn-login"
        style="width: auto; margin: 0;"
    >
        Guardar cambios
    </button>

    <a
        href="index.php"
        class="btn-primary"
        style="width: auto; display: inline-flex; align-items: center; text-decoration: none;"
    >
        Cancelar
    </a>

</div>
 
    </form>

</main>

</body>

</html>