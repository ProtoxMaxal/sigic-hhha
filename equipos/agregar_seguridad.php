<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$idEquipo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idEquipo) {
    header('Location: index.php');
    exit;
}

/* Comprobar que el equipo existe */
$consultaEquipo = $pdo->prepare(
    "SELECT id_equipo, nombre_equipo
     FROM equipos
     WHERE id_equipo = ?
     LIMIT 1"
);

$consultaEquipo->execute([$idEquipo]);
$equipo = $consultaEquipo->fetch();

if (!$equipo) {
    header('Location: index.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $antivirus = trim($_POST['antivirus'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $activo = $_POST['activo'] ?? '';
    $actualizado = $_POST['actualizado'] ?? '';
    $fechaRevision = $_POST['fecha_revision'] ?? '';

    if ($antivirus === '') {

        $mensaje = 'Debes ingresar el nombre del antivirus.';

    } elseif ($activo !== '0' && $activo !== '1') {

        $mensaje = 'Debes indicar si el antivirus está activo.';

    } elseif ($actualizado !== '0' && $actualizado !== '1') {

        $mensaje = 'Debes indicar si el antivirus está actualizado.';

    } elseif ($fechaRevision === '') {

        $mensaje = 'Debes ingresar la fecha de revisión.';

    } else {

        try {

            $pdo->beginTransaction();

            $insertar = $pdo->prepare(
                "INSERT INTO seguridad_equipo (
                    id_equipo,
                    antivirus,
                    version,
                    activo,
                    actualizado,
                    fecha_revision
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insertar->execute([
                $idEquipo,
                $antivirus,
                $version !== '' ? $version : null,
                (int) $activo,
                (int) $actualizado,
                $fechaRevision
            ]);

            $idSeguridad = $pdo->lastInsertId();

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
                'CREAR',
                'seguridad_equipo',
                $idSeguridad,
                'Revisión de seguridad agregada al equipo ID ' . $idEquipo . '.',
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $pdo->commit();

            header('Location: ver.php?id=' . $idEquipo);
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensaje = 'No fue posible registrar la información de seguridad.';
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

    <title>Agregar seguridad - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Registro de Seguridad del Equipo</p>
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
            href="ver.php?id=<?php echo $idEquipo; ?>"
            class="logout-link"
        >
            Volver a la ficha
        </a>

    </div>

</header>


<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Agregar revisión de seguridad</h2>

            <p>
                Equipo:
                <strong>
                    <?php echo htmlspecialchars($equipo['nombre_equipo']); ?>
                </strong>
            </p>

        </div>

    </div>


    <?php if ($mensaje !== ''): ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>

    <?php endif; ?>


    <form method="POST" class="formulario-equipo">

        <div class="form-group">

            <label for="antivirus">
                Antivirus *
            </label>

            <input
                type="text"
                id="antivirus"
                name="antivirus"
                required
            >

        </div>


        <div class="form-group">

            <label for="version">
                Versión
            </label>

            <input
                type="text"
                id="version"
                name="version"
            >

        </div>


        <div class="form-group">

            <label for="activo">
                ¿Antivirus activo? *
            </label>

            <select id="activo" name="activo" required>

                <option value="">Seleccione</option>
                <option value="1">Sí</option>
                <option value="0">No</option>

            </select>

        </div>


        <div class="form-group">

            <label for="actualizado">
                ¿Antivirus actualizado? *
            </label>

            <select id="actualizado" name="actualizado" required>

                <option value="">Seleccione</option>
                <option value="1">Sí</option>
                <option value="0">No</option>

            </select>

        </div>


        <div class="form-group">

            <label for="fecha_revision">
                Fecha de revisión *
            </label>

            <input
                type="date"
                id="fecha_revision"
                name="fecha_revision"
                required
            >

        </div>


        <button
            type="submit"
            class="btn-login"
        >
            Guardar revisión
        </button>

    </form>

</main>

</body>
</html>