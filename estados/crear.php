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
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '') {

        $error = 'Debes ingresar el nombre del estado.';

    } elseif ($descripcion === '') {

        $error = 'Debes ingresar una descripción.';

    } else {

        $verificar = $pdo->prepare(
            "SELECT id_estado
             FROM estados_equipo
             WHERE nombre = ?
             LIMIT 1"
        );

        $verificar->execute([$nombre]);

        if ($verificar->fetch()) {

            $error = 'Ya existe un estado con ese nombre.';

        } else {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO estados_equipo
                        (nombre, descripcion)
                     VALUES
                        (?, ?)"
                );

                $stmt->execute([
                    $nombre,
                    $descripcion
                ]);

                $idEstado = $pdo->lastInsertId();

                $detalle =
                    'Estado de equipo creado: ' .
                    $nombre .
                    ' | Descripción: ' .
                    $descripcion;

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
                    'ESTADO_EQUIPO',
                    $idEstado,
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

                $error = 'No fue posible crear el estado.';
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

    <title>Crear estado - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .estado-page {
            padding: 35px 20px;
        }

        .estado-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .estado-card h2 {
            margin-top: 0;
            margin-bottom: 5px;
        }

        .estado-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .estado-form-group {
            margin-bottom: 20px;
        }

        .estado-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .estado-form-group input[type="text"],
        .estado-form-group textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            font-family: Arial, sans-serif;
        }

        .estado-form-group input[type="text"]:focus,
        .estado-form-group textarea:focus {
            outline: none;
            border-color: #555;
        }

        .estado-form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .estado-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .estado-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        .estado-error {
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
        <p>Gestión de Estados de Equipo</p>
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

<main class="estado-page">

    <div class="estado-card">

        <h2>Registrar estado</h2>

        <p class="estado-descripcion">
            Ingresa un nuevo estado para los equipos computacionales.
        </p>

        <?php if ($error !== ''): ?>

            <div class="estado-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="estado-form-group">

                <label for="nombre">
                    Nombre *
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    required
                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                >

            </div>

            <div class="estado-form-group">

                <label for="descripcion">
                    Descripción *
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    maxlength="255"
                    required
                ><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>

            </div>

            <div class="estado-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar estado
                </button>

                <a
                    href="index.php"
                    class="estado-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>