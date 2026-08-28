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

/* Comprobar que el equipo exista */
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
    $direccionMac = trim($_POST['direccion_mac'] ?? '');
    $direccionIp = trim($_POST['direccion_ip'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    if ($tipo === '') {

        $mensaje = 'Debes seleccionar el tipo de conexión.';

    } elseif ($estado === '') {

        $mensaje = 'Debes seleccionar el estado de la interfaz.';

    } elseif (
        $direccionMac !== '' &&
        !filter_var($direccionMac, FILTER_VALIDATE_MAC)
    ) {

        $mensaje = 'La dirección MAC ingresada no es válida.';

    } elseif (
        $direccionIp !== '' &&
        !filter_var($direccionIp, FILTER_VALIDATE_IP)
    ) {

        $mensaje = 'La dirección IP ingresada no es válida.';

    } else {

        try {

            $pdo->beginTransaction();

            $insertar = $pdo->prepare(
                "INSERT INTO interfaces_red (
                    id_equipo,
                    tipo,
                    direccion_mac,
                    direccion_ip,
                    estado
                )
                VALUES (?, ?, ?, ?, ?)"
            );

            $insertar->execute([
                $idEquipo,
                $tipo,
                $direccionMac !== '' ? $direccionMac : null,
                $direccionIp !== '' ? $direccionIp : null,
                $estado
            ]);

            $idInterfaz = $pdo->lastInsertId();

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
                'interfaces_red',
                $idInterfaz,
                'Interfaz de red agregada al equipo ID ' . $idEquipo . '.',
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $pdo->commit();

            header('Location: ver.php?id=' . $idEquipo);
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensaje = 'No fue posible registrar la información de red.';
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

    <title>Agregar red - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Registro de Información de Red</p>
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

            <h2>Agregar información de red</h2>

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

            <label for="tipo">
                Tipo de conexión *
            </label>

            <select id="tipo" name="tipo" required>

                <option value="">Seleccione</option>
                <option value="Ethernet">Ethernet</option>
                <option value="Wi-Fi">Wi-Fi</option>
                <option value="Otro">Otro</option>

            </select>

        </div>


        <div class="form-group">

            <label for="direccion_mac">
                Dirección MAC
            </label>

            <input
                type="text"
                id="direccion_mac"
                name="direccion_mac"
                placeholder="Ej: 00:1A:2B:3C:4D:5E"
            >

        </div>


        <div class="form-group">

            <label for="direccion_ip">
                Dirección IP
            </label>

            <input
                type="text"
                id="direccion_ip"
                name="direccion_ip"
                placeholder="Ej: 192.168.1.100"
            >

        </div>


        <div class="form-group">

            <label for="estado">
                Estado *
            </label>

            <select id="estado" name="estado" required>

                <option value="">Seleccione</option>
                <option value="Activa">Activa</option>
                <option value="Inactiva">Inactiva</option>

            </select>

        </div>


        <button
            type="submit"
            class="btn-login"
        >
            Guardar información de red
        </button>

    </form>

</main>

</body>
</html>