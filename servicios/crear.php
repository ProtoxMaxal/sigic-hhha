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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {

        $error = 'Debes ingresar el nombre del servicio.';

    } else {

        $verificar = $pdo->prepare(
            "SELECT id_servicio
             FROM servicios
             WHERE nombre = ?
             LIMIT 1"
        );

        $verificar->execute([$nombre]);

        if ($verificar->fetch()) {

            $error = 'Ya existe un servicio con ese nombre.';

        } else {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO servicios
                        (nombre, activo)
                     VALUES
                        (?, ?)"
                );

                $stmt->execute([
                    $nombre,
                    $activo
                ]);

                $idServicio = $pdo->lastInsertId();

                $detalle =
                    'Servicio creado: ' .
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
                    'CREAR',
                    'SERVICIO',
                    $idServicio,
                    $detalle,
                    $_SERVER['REMOTE_ADDR'] ?? null
                ]);

                $pdo->commit();

                header('Location: index.php?creado=1');
                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'No fue posible crear el servicio.';
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

    <title>Crear servicio - SIGIC-HHHA</title>

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

        .servicio-form-group input[type="text"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .servicio-form-group input[type="text"]:focus {
            outline: none;
            border-color: #555;
        }

        .servicio-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0 25px;
        }

        .servicio-check input {
            width: 17px;
            height: 17px;
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

        <h2>Registrar servicio</h2>

        <p class="servicio-descripcion">
            Ingresa un nuevo servicio para organizar las ubicaciones del hospital.
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
                    autocomplete="off"
                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                >

            </div>

            <label class="servicio-check">

                <input
                    type="checkbox"
                    name="activo"
                    value="1"
                    <?php echo (
                        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                        isset($_POST['activo'])
                    ) ? 'checked' : ''; ?>
                >

                Servicio activo

            </label>

            <div class="servicio-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar servicio
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