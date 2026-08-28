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
        id_equipo,
        nombre_equipo,
        numero_inventario
    FROM equipos
    ORDER BY nombre_equipo"
)->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idEquipo = !empty($_POST['id_equipo'])
        ? (int) $_POST['id_equipo']
        : 0;

    $tipo = trim($_POST['tipo'] ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    if (!$idEquipo) {

        $mensaje = 'Debes seleccionar un equipo.';

    } elseif ($tipo === '') {

        $mensaje = 'Debes seleccionar el tipo de mantención.';

    } elseif ($detalle === '') {

        $mensaje = 'Debes ingresar el detalle de la mantención.';

    } elseif ($estado === '') {

        $mensaje = 'Debes seleccionar el estado de la mantención.';

    } else {

        /* Comprobar que el equipo exista */
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

                /* Registrar mantención */
                $insertar = $pdo->prepare(
                    "INSERT INTO mantenciones (
                        id_equipo,
                        id_usuario,
                        fecha,
                        tipo,
                        detalle,
                        estado
                    )
                    VALUES (?, ?, NOW(), ?, ?, ?)"
                );

                $insertar->execute([
                    $idEquipo,
                    $_SESSION['id_usuario'],
                    $tipo,
                    $detalle,
                    $estado
                ]);

                $idMantencion = $pdo->lastInsertId();


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
                    'mantenciones',
                    $idMantencion,
                    'Mantención registrada para el equipo ID ' . $idEquipo . '.',
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                $pdo->commit();

                header('Location: index.php?creado=1');
                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $mensaje = 'No fue posible registrar la mantención.';
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

    <title>Registrar mantención - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Registro de Mantenciones</p>
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

            <h2>Registrar mantención</h2>

            <p>
                Ingrese la información de la intervención realizada.
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

                        $textoEquipo = $equipo['nombre_equipo'];

                        if (!empty($equipo['numero_inventario'])) {
                            $textoEquipo .= ' - ' . $equipo['numero_inventario'];
                        }

                        echo htmlspecialchars($textoEquipo);

                        ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="form-group">

            <label for="tipo">
                Tipo de mantención *
            </label>

            <select
                id="tipo"
                name="tipo"
                required
            >

                <option value="">
                    Seleccione
                </option>

                <option value="Preventiva">
                    Preventiva
                </option>

                <option value="Correctiva">
                    Correctiva
                </option>

                <option value="Diagnóstico">
                    Diagnóstico
                </option>

                <option value="Actualización">
                    Actualización
                </option>

                <option value="Otro">
                    Otro
                </option>

            </select>

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

                <option value="">
                    Seleccione
                </option>

                <option value="Pendiente">
                    Pendiente
                </option>

                <option value="En proceso">
                    En proceso
                </option>

                <option value="Finalizada">
                    Finalizada
                </option>

            </select>

        </div>


        <div class="form-group">

            <label for="detalle">
                Detalle de la mantención *
            </label>

            <textarea
                id="detalle"
                name="detalle"
                rows="5"
                required
                placeholder="Ej: Limpieza interna, revisión de memoria RAM y disco..."
            ></textarea>

        </div>


        <button
            type="submit"
            class="btn-login"
        >
            Registrar mantención
        </button>

    </form>

</main>

</body>

</html>