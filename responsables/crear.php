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
    $cargo = trim($_POST['cargo'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {

        $error = 'Debes ingresar el nombre del responsable.';

    } elseif ($cargo === '') {

        $error = 'Debes ingresar el cargo del responsable.';

    } else {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO responsables
                    (nombre, cargo, activo)
                 VALUES
                    (:nombre, :cargo, :activo)"
            );

            $stmt->execute([
                ':nombre' => $nombre,
                ':cargo' => $cargo,
                ':activo' => $activo
            ]);

            $idResponsable = $pdo->lastInsertId();

            $detalle =
                'Responsable creado: ' .
                $nombre .
                ' | Cargo: ' .
                $cargo;

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
                'RESPONSABLE',
                $idResponsable,
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

            $error = 'No fue posible crear el responsable.';
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

    <title>Crear responsable - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .responsable-page {
            padding: 35px 20px;
        }

        .responsable-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .responsable-card h2 {
            margin-top: 0;
            margin-bottom: 5px;
        }

        .responsable-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .responsable-form-group {
            margin-bottom: 20px;
        }

        .responsable-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .responsable-form-group input[type="text"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .responsable-form-group input[type="text"]:focus {
            outline: none;
            border-color: #555;
        }

        .responsable-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0 25px;
        }

        .responsable-check input {
            width: 17px;
            height: 17px;
        }

        .responsable-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .responsable-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        .responsable-error {
            padding: 12px;
            margin-bottom: 20px;
            background: #f5dddd;
            border-radius: 6px;
        }

        @media (max-width: 600px) {

            .responsable-card {
                padding: 20px;
            }

            .responsable-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .responsable-actions button,
            .responsable-actions a {
                text-align: center;
            }
        }

    </style>

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Responsables</p>
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

<main class="responsable-page">

    <div class="responsable-card">

        <h2>Registrar responsable</h2>

        <p class="responsable-descripcion">
            Ingresa los datos de la persona responsable del equipo.
        </p>

        <?php if ($error !== ''): ?>

            <div class="responsable-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="responsable-form-group">

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

            <div class="responsable-form-group">

                <label for="cargo">
                    Cargo *
                </label>

                <input
                    type="text"
                    id="cargo"
                    name="cargo"
                    maxlength="100"
                    required
                    autocomplete="off"
                    value="<?php echo htmlspecialchars($_POST['cargo'] ?? ''); ?>"
                >

            </div>

            <label class="responsable-check">

                <input
                    type="checkbox"
                    name="activo"
                    value="1"
                    <?php echo (
                        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
                        isset($_POST['activo'])
                    ) ? 'checked' : ''; ?>
                >

                Responsable activo

            </label>

            <div class="responsable-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar responsable
                </button>

                <a
                    href="index.php"
                    class="responsable-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>