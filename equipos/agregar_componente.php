<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

/* Obtener y validar equipo */
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

    $tipo = trim($_POST['tipo'] ?? '');
    $fabricante = trim($_POST['fabricante'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $capacidad = trim($_POST['capacidad'] ?? '');
    $numeroSerie = trim($_POST['numero_serie'] ?? '');

    if ($tipo === '') {

        $mensaje = 'Debes seleccionar el tipo de componente.';

    } else {

        try {

            $pdo->beginTransaction();

            $insertar = $pdo->prepare(
                "INSERT INTO componentes (
                    id_equipo,
                    tipo,
                    fabricante,
                    modelo,
                    capacidad,
                    numero_serie
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $insertar->execute([
                $idEquipo,
                $tipo,
                $fabricante !== '' ? $fabricante : null,
                $modelo !== '' ? $modelo : null,
                $capacidad !== '' ? $capacidad : null,
                $numeroSerie !== '' ? $numeroSerie : null
            ]);

            $idComponente = $pdo->lastInsertId();

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
                'componentes',
                $idComponente,
                'Componente agregado al equipo ID ' . $idEquipo . '.',
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $pdo->commit();

            header('Location: ver.php?id=' . $idEquipo);
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensaje = 'No fue posible registrar el componente.';
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

    <title>Agregar componente - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Registro de Hardware</p>
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
            <h2>Agregar componente</h2>

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
            <label for="tipo">Tipo de componente *</label>

            <select id="tipo" name="tipo" required>

                <option value="">Seleccione</option>
                <option value="CPU">CPU</option>
                <option value="RAM">RAM</option>
                <option value="SSD">SSD</option>
                <option value="HDD">HDD</option>
                <option value="GPU">GPU</option>
                <option value="Otro">Otro</option>

            </select>
        </div>


        <div class="form-group">
            <label for="fabricante">Fabricante</label>

            <input
                type="text"
                id="fabricante"
                name="fabricante"
            >
        </div>


        <div class="form-group">
            <label for="modelo">Modelo</label>

            <input
                type="text"
                id="modelo"
                name="modelo"
            >
        </div>


        <div class="form-group">
            <label for="capacidad">
                Capacidad / Especificación
            </label>

            <input
                type="text"
                id="capacidad"
                name="capacidad"
                placeholder="Ej: 16 GB, 512 GB"
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


        <button type="submit" class="btn-login">
            Guardar componente
        </button>

    </form>

</main>