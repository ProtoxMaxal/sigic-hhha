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

$idMarca = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idMarca || $idMarca <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_marca,
        nombre
     FROM marcas
     WHERE id_marca = ?
     LIMIT 1"
);

$stmt->execute([$idMarca]);

$marca = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$marca) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') {

        $error = 'Debes ingresar el nombre de la marca.';

    } else {

        $verificar = $pdo->prepare(
            "SELECT id_marca
             FROM marcas
             WHERE nombre = ?
             AND id_marca <> ?
             LIMIT 1"
        );

        $verificar->execute([
            $nombre,
            $idMarca
        ]);

        if ($verificar->fetch()) {

            $error = 'Ya existe otra marca con ese nombre.';

        } else {

            try {

                $pdo->beginTransaction();

                $nombreAnterior = $marca['nombre'];

                $actualizar = $pdo->prepare(
                    "UPDATE marcas
                     SET nombre = ?
                     WHERE id_marca = ?"
                );

                $actualizar->execute([
                    $nombre,
                    $idMarca
                ]);

                $detalle =
                    'Marca modificada: ' .
                    $nombreAnterior .
                    ' -> ' .
                    $nombre;

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
                    'MARCA',
                    $idMarca,
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

                $error = 'No fue posible modificar la marca.';
            }
        }
    }

    $marca['nombre'] = $nombre;
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

    <title>Modificar marca - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .marca-page {
            padding: 35px 20px;
        }

        .marca-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .marca-card h2 {
            margin-top: 0;
            margin-bottom: 5px;
        }

        .marca-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .marca-form-group {
            margin-bottom: 20px;
        }

        .marca-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .marca-form-group input[type="text"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .marca-form-group input[type="text"]:focus {
            outline: none;
            border-color: #555;
        }

        .marca-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .marca-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        .marca-error {
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
        <p>Gestión de Marcas</p>
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

<main class="marca-page">

    <div class="marca-card">

        <h2>Modificar marca</h2>

        <p class="marca-descripcion">
            Actualiza el nombre de la marca seleccionada.
        </p>

        <?php if ($error !== ''): ?>

            <div class="marca-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="marca-form-group">

                <label for="nombre">
                    Nombre *
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    required
                    value="<?php echo htmlspecialchars($marca['nombre']); ?>"
                >

            </div>

            <div class="marca-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar cambios
                </button>

                <a
                    href="index.php"
                    class="marca-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>