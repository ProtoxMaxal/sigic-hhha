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
    $version = trim($_POST['version'] ?? '');
    $arquitectura = trim($_POST['arquitectura'] ?? '');

    if ($nombre === '') {

        $error = 'Debes ingresar el nombre del sistema operativo.';

    } elseif ($version === '') {

        $error = 'Debes ingresar la versión.';

    } elseif ($arquitectura === '') {

        $error = 'Debes seleccionar la arquitectura.';

    } else {

        $verificar = $pdo->prepare(
            "SELECT id_so
             FROM sistemas_operativos
             WHERE nombre = ?
             AND version = ?
             AND arquitectura = ?
             LIMIT 1"
        );

        $verificar->execute([
            $nombre,
            $version,
            $arquitectura
        ]);

        if ($verificar->fetch()) {

            $error =
                'Ese sistema operativo ya se encuentra registrado.';

        } else {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO sistemas_operativos
                        (nombre, version, arquitectura)
                     VALUES
                        (?, ?, ?)"
                );

                $stmt->execute([
                    $nombre,
                    $version,
                    $arquitectura
                ]);

                $idSo = $pdo->lastInsertId();

                $detalle =
                    'Sistema operativo creado: ' .
                    $nombre .
                    ' | Versión: ' .
                    $version .
                    ' | Arquitectura: ' .
                    $arquitectura;

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
                    'SISTEMA_OPERATIVO',
                    $idSo,
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

                $error =
                    'No fue posible crear el sistema operativo.';
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

    <title>Crear sistema operativo - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .so-page {
            padding: 35px 20px;
        }

        .so-card {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .so-card h2 {
            margin-top: 0;
            margin-bottom: 5px;
        }

        .so-descripcion {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .so-form-group {
            margin-bottom: 20px;
        }

        .so-form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .so-form-group input,
        .so-form-group select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            background: #ffffff;
        }

        .so-form-group input:focus,
        .so-form-group select:focus {
            outline: none;
            border-color: #555;
        }

        .so-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .so-cancelar {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            background: #e9e9e9;
            color: #222;
            border-radius: 5px;
        }

        .so-error {
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
        <p>Gestión de Sistemas Operativos</p>
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

<main class="so-page">

    <div class="so-card">

        <h2>Registrar sistema operativo</h2>

        <p class="so-descripcion">
            Ingresa un nuevo sistema operativo para los equipos.
        </p>

        <?php if ($error !== ''): ?>

            <div class="so-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="so-form-group">

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

            <div class="so-form-group">

                <label for="version">
                    Versión *
                </label>

                <input
                    type="text"
                    id="version"
                    name="version"
                    maxlength="50"
                    required
                    value="<?php echo htmlspecialchars($_POST['version'] ?? ''); ?>"
                >

            </div>

            <div class="so-form-group">

                <label for="arquitectura">
                    Arquitectura *
                </label>

                <select
                    id="arquitectura"
                    name="arquitectura"
                    required
                >

                    <option value="">
                        Seleccione
                    </option>

                    <option
                        value="64 bits"
                        <?php
                        echo ($_POST['arquitectura'] ?? '') === '64 bits'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        64 bits
                    </option>

                    <option
                        value="32 bits"
                        <?php
                        echo ($_POST['arquitectura'] ?? '') === '32 bits'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        32 bits
                    </option>

                </select>

            </div>

            <div class="so-actions">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar sistema operativo
                </button>

                <a
                    href="index.php"
                    class="so-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>