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

$idResponsable = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idResponsable || $idResponsable <= 0) {
    header('Location: index.php');
    exit;
}

$consulta = $pdo->prepare(
    "SELECT
        id_responsable,
        nombre,
        cargo,
        activo
     FROM responsables
     WHERE id_responsable = ?
     LIMIT 1"
);

$consulta->execute([$idResponsable]);

$responsable = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$responsable) {
    header('Location: index.php');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');

    $activo = isset($_POST['activo'])
        ? (int)$_POST['activo']
        : 0;

    if ($nombre === '') {

        $mensaje = 'Debes ingresar el nombre del responsable.';

    } elseif ($cargo === '') {

        $mensaje = 'Debes ingresar el cargo del responsable.';

    } elseif (!in_array($activo, [0, 1], true)) {

        $mensaje = 'El estado seleccionado no es válido.';

    } else {

        try {

            $pdo->beginTransaction();

            $actualizar = $pdo->prepare(
                "UPDATE responsables
                 SET
                    nombre = ?,
                    cargo = ?,
                    activo = ?
                 WHERE id_responsable = ?"
            );

            $actualizar->execute([
                $nombre,
                $cargo,
                $activo,
                $idResponsable
            ]);

            $detalle =
                'Responsable modificado: ' .
                $responsable['nombre'] .
                ' -> ' .
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
                'MODIFICAR',
                'RESPONSABLE',
                $idResponsable,
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

            $mensaje = 'No fue posible modificar el responsable.';
        }
    }

    $responsable['nombre'] = $nombre;
    $responsable['cargo'] = $cargo;
    $responsable['activo'] = $activo;
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

    <title>Modificar responsable - SIGIC-HHHA</title>

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

        .responsable-form-group input[type="text"],
        .responsable-form-group select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            background: #ffffff;
        }

        .responsable-form-group input[type="text"]:focus,
        .responsable-form-group select:focus {
            outline: none;
            border-color: #555;
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

        <h2>Modificar responsable</h2>

        <p class="responsable-descripcion">
            Actualiza los datos del responsable seleccionado.
        </p>

        <?php if ($mensaje !== ''): ?>

            <div class="responsable-error">
                <?php echo htmlspecialchars($mensaje); ?>
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
                    value="<?php echo htmlspecialchars($responsable['nombre']); ?>"
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
                    value="<?php echo htmlspecialchars($responsable['cargo']); ?>"
                >

            </div>

            <div class="responsable-form-group">

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
                        <?php echo (int)$responsable['activo'] === 1 ? 'selected' : ''; ?>
                    >
                        Activo
                    </option>

                    <option
                        value="0"
                        <?php echo (int)$responsable['activo'] === 0 ? 'selected' : ''; ?>
                    >
                        Inactivo
                    </option>

                </select>

            </div>

            <div class="responsable-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar cambios
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