<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$mensaje = '';
$tipoMensaje = '';

/* Cargar datos para los select */
$marcas = $pdo->query(
    "SELECT id_marca, nombre
     FROM marcas
     ORDER BY nombre"
)->fetchAll();

$estados = $pdo->query(
    "SELECT id_estado, nombre
     FROM estados_equipo
     ORDER BY nombre"
)->fetchAll();

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

$sistemas = $pdo->query(
    "SELECT
        id_so,
        nombre,
        version,
        arquitectura
     FROM sistemas_operativos
     ORDER BY nombre, version"
)->fetchAll();

$responsables = $pdo->query(
    "SELECT id_responsable, nombre
     FROM responsables
     WHERE activo = 1
     ORDER BY nombre"
)->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombreEquipo = trim($_POST['nombre_equipo'] ?? '');
    $numeroInventario = trim($_POST['numero_inventario'] ?? '');
    $numeroSerie = trim($_POST['numero_serie'] ?? '');
    $uuid = trim($_POST['uuid'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    $idMarca = !empty($_POST['id_marca'])
        ? (int) $_POST['id_marca']
        : null;

    $idEstado = !empty($_POST['id_estado'])
        ? (int) $_POST['id_estado']
        : null;

    $idUbicacion = !empty($_POST['id_ubicacion'])
        ? (int) $_POST['id_ubicacion']
        : null;

    $idSo = !empty($_POST['id_so'])
        ? (int) $_POST['id_so']
        : null;

    $idResponsable = !empty($_POST['id_responsable'])
        ? (int) $_POST['id_responsable']
        : null;


    if ($nombreEquipo === '') {

        $mensaje = 'Debes ingresar el nombre del equipo.';
        $tipoMensaje = 'error';

    } elseif (!$idEstado) {

        $mensaje = 'Debes seleccionar un estado.';
        $tipoMensaje = 'error';

    } elseif (!$idUbicacion) {

        $mensaje = 'Debes seleccionar una ubicación.';
        $tipoMensaje = 'error';

    } else {

        /* Verificar datos únicos */
        $duplicado = false;

        if ($numeroInventario !== '') {

            $consulta = $pdo->prepare(
                "SELECT id_equipo
                 FROM equipos
                 WHERE numero_inventario = ?"
            );

            $consulta->execute([$numeroInventario]);

            if ($consulta->fetch()) {
                $mensaje = 'El número de inventario ya está registrado.';
                $tipoMensaje = 'error';
                $duplicado = true;
            }
        }


        if (!$duplicado && $numeroSerie !== '') {

            $consulta = $pdo->prepare(
                "SELECT id_equipo
                 FROM equipos
                 WHERE numero_serie = ?"
            );

            $consulta->execute([$numeroSerie]);

            if ($consulta->fetch()) {
                $mensaje = 'El número de serie ya está registrado.';
                $tipoMensaje = 'error';
                $duplicado = true;
            }
        }


        if (!$duplicado && $uuid !== '') {

            $consulta = $pdo->prepare(
                "SELECT id_equipo
                 FROM equipos
                 WHERE uuid = ?"
            );

            $consulta->execute([$uuid]);

            if ($consulta->fetch()) {
                $mensaje = 'El UUID ya está registrado.';
                $tipoMensaje = 'error';
                $duplicado = true;
            }
        }


        if (!$duplicado) {

            try {

                $pdo->beginTransaction();

                $insertar = $pdo->prepare(
                    "INSERT INTO equipos (
                        id_marca,
                        id_estado,
                        id_ubicacion,
                        id_so,
                        id_responsable,
                        nombre_equipo,
                        numero_inventario,
                        numero_serie,
                        uuid,
                        modelo,
                        tipo,
                        observaciones
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $insertar->execute([
                    $idMarca,
                    $idEstado,
                    $idUbicacion,
                    $idSo,
                    $idResponsable,
                    $nombreEquipo,
                    $numeroInventario !== '' ? $numeroInventario : null,
                    $numeroSerie !== '' ? $numeroSerie : null,
                    $uuid !== '' ? $uuid : null,
                    $modelo !== '' ? $modelo : null,
                    $tipo !== '' ? $tipo : null,
                    $observaciones !== '' ? $observaciones : null
                ]);

                $idEquipo = $pdo->lastInsertId();

                /* Registrar la acción en auditoría */
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
                    'equipos',
                    $idEquipo,
                    'Registro de nuevo equipo computacional.',
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                $pdo->commit();

                header('Location: index.php?creado=1');
                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $mensaje = 'No fue posible registrar el equipo.';
                $tipoMensaje = 'error';
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

    <title>Registrar equipo - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

    <header class="dashboard-header">

        <div>
            <h1>SIGIC-HHHA</h1>
            <p>Registro de Equipos Computacionales</p>
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
                Volver a equipos
            </a>

        </div>

    </header>


    <main class="equipos-content">

        <div class="equipos-header">

            <div>
                <h2>Registrar nuevo equipo</h2>
                <p>Complete la información del computador.</p>
            </div>

        </div>


        <?php if ($mensaje !== ''): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>

        <?php endif; ?>


        <form method="POST" class="formulario-equipo">

            <div class="form-group">

                <label for="nombre_equipo">
                    Nombre del equipo *
                </label>

                <input
                    type="text"
                    id="nombre_equipo"
                    name="nombre_equipo"
                    required
                >

            </div>


            <div class="form-group">

                <label for="numero_inventario">
                    Número de inventario
                </label>

                <input
                    type="text"
                    id="numero_inventario"
                    name="numero_inventario"
                >

            </div>


            <div class="form-group">

                <label for="numero_serie">
                    Número de serie
                </label>

                <input
                    type="text"
                    id="numero_serie"
                    name="numero_serie"
                >

            </div>


            <div class="form-group">

                <label for="uuid">
                    UUID
                </label>

                <input
                    type="text"
                    id="uuid"
                    name="uuid"
                >

            </div>


            <div class="form-group">

                <label for="id_marca">
                    Marca
                </label>

                <select
                    id="id_marca"
                    name="id_marca"
                >

                    <option value="">
                        Seleccione
                    </option>

                    <?php foreach ($marcas as $marca): ?>

                        <option value="<?php echo $marca['id_marca']; ?>">
                            <?php echo htmlspecialchars($marca['nombre']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="modelo">
                    Modelo
                </label>

                <input
                    type="text"
                    id="modelo"
                    name="modelo"
                >

            </div>


            <div class="form-group">

                <label for="tipo">
                    Tipo
                </label>

                <select
                    id="tipo"
                    name="tipo"
                >

                    <option value="">
                        Seleccione
                    </option>

                    <option value="Desktop">
                        Desktop
                    </option>

                    <option value="Notebook">
                        Notebook
                    </option>

                    <option value="All in One">
                        All in One
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="id_estado">
                    Estado *
                </label>

                <select
                    id="id_estado"
                    name="id_estado"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <?php foreach ($estados as $estado): ?>

                        <option value="<?php echo $estado['id_estado']; ?>">
                            <?php echo htmlspecialchars($estado['nombre']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="id_ubicacion">
                    Ubicación *
                </label>

                <select
                    id="id_ubicacion"
                    name="id_ubicacion"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <?php foreach ($ubicaciones as $ubicacion): ?>

                        <option value="<?php echo $ubicacion['id_ubicacion']; ?>">

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

                <label for="id_so">
                    Sistema operativo
                </label>

                <select
                    id="id_so"
                    name="id_so"
                >

                    <option value="">
                        Seleccione
                    </option>

                    <?php foreach ($sistemas as $sistema): ?>

                        <option value="<?php echo $sistema['id_so']; ?>">

                            <?php

                            $textoSo = $sistema['nombre'];

                            if (!empty($sistema['version'])) {
                                $textoSo .= ' ' . $sistema['version'];
                            }

                            if (!empty($sistema['arquitectura'])) {
                                $textoSo .= ' - ' . $sistema['arquitectura'];
                            }

                            echo htmlspecialchars($textoSo);

                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="id_responsable">
                    Responsable
                </label>

                <select
                    id="id_responsable"
                    name="id_responsable"
                >

                    <option value="">
                        Sin responsable
                    </option>

                    <?php foreach ($responsables as $responsable): ?>

                        <option value="<?php echo $responsable['id_responsable']; ?>">
                            <?php echo htmlspecialchars($responsable['nombre']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="observaciones">
                    Observaciones
                </label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    rows="4"
                ></textarea>

            </div>


            <button
                type="submit"
                class="btn-login"
            >
                Registrar equipo
            </button>

        </form>

    </main>

</body>

</html>