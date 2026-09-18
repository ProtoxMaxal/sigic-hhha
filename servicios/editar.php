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

$idServicio = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idServicio || $idServicio <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_servicio,
        nombre,
        activo
     FROM servicios
     WHERE id_servicio = ?
     LIMIT 1"
);

$stmt->execute([$idServicio]);

$servicio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servicio) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');

    $activo = isset($_POST['activo'])
        ? (int)$_POST['activo']
        : 0;

    if ($nombre === '') {

        $error = 'Debes ingresar el nombre del servicio.';

    } elseif (!in_array($activo, [0, 1], true)) {

        $error = 'El estado seleccionado no es válido.';

    } else {

        $verificar = $pdo->prepare(
            "SELECT id_servicio
             FROM servicios
             WHERE nombre = ?
             AND id_servicio <> ?
             LIMIT 1"
        );

        $verificar->execute([
            $nombre,
            $idServicio
        ]);

        if ($verificar->fetch()) {

            $error = 'Ya existe otro servicio con ese nombre.';

        } else {

            try {

                $pdo->beginTransaction();

                $nombreAnterior = $servicio['nombre'];

                $actualizar = $pdo->prepare(
                    "UPDATE servicios
                     SET
                        nombre = ?,
                        activo = ?
                     WHERE id_servicio = ?"
                );

                $actualizar->execute([
                    $nombre,
                    $activo,
                    $idServicio
                ]);

                $detalle =
                    'Servicio modificado: ' .
                    $nombreAnterior .
                    ' -> ' .
                    $nombre .
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
                    'MODIFICAR',
                    'SERVICIO',
                    $idServicio,
                    $detalle,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                $pdo->commit();

                header('Location: index.php?modificado=1');
                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'No fue posible modificar el servicio.';
            }
        }
    }

    $servicio['nombre'] = $nombre;
    $servicio['activo'] = $activo;
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

    <title>Modificar servicio - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .servicio-page {
            padding: 35px 20px;
        }

        .servicio-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .servicio-card h2 {
            margin-top: 0;
            margin-bottom: 5px;
        }

        .servicio-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .servicio-form-group {
            margin-bottom: 20px;
        }

        .servicio-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .servicio-form-group input[type="text"],
        .servicio-form-group select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            background: #ffffff;
        }

        .servicio-form-group input:focus,
        .servicio-form-group select:focus {
            outline: none;
            border-color: #555;
        }

        .servicio-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .servicio-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        .servicio-error {
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
        <p>Gestión de Servicios</p>
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

<main class="servicio-page">

    <div class="servicio-card">

        <h2>Modificar servicio</h2>

        <p class="servicio-descripcion">
            Actualiza los datos del servicio seleccionado.
        </p>

        <?php if ($error !== ''): ?>

            <div class="servicio-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="servicio-form-group">

                <label for="nombre">
                    Nombre *
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="150"
                    required
                    value="<?php echo htmlspecialchars($servicio['nombre']); ?>"
                >

            </div>

            <div class="servicio-form-group">

                <label for="activo">
                    Estado *
                </label>

                <select
                    id="activo"
                    name="activo"
                    required
                >

                    <option
                        value="1"
                        <?php echo (int)$servicio['activo'] === 1
                            ? 'selected'
                            : ''; ?>
                    >
                        Activo
                    </option>

                    <option
                        value="0"
                        <?php echo (int)$servicio['activo'] === 0
                            ? 'selected'
                            : ''; ?>
                    >
                        Inactivo
                    </option>

                </select>

            </div>

            <div class="servicio-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar cambios
                </button>

                <a
                    href="index.php"
                    class="servicio-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>