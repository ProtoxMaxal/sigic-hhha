<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

if (
    !isset($_SESSION['rol']) ||
    strtolower(trim($_SESSION['rol'])) !== 'administrador'
) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$idEquipo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idEquipo || $idEquipo <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT
        id_equipo,
        nombre_equipo,
        numero_inventario,
        numero_serie
     FROM equipos
     WHERE id_equipo = ?"
);

$stmt->execute([$idEquipo]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $confirmar = $_POST['confirmar'] ?? '';

    if ($confirmar !== 'SI') {
        $error = 'Debe confirmar la eliminación del equipo.';
    } else {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "DELETE FROM componentes WHERE id_equipo = ?"
            );
            $stmt->execute([$idEquipo]);

            $stmt = $pdo->prepare(
                "DELETE FROM interfaces_red WHERE id_equipo = ?"
            );
            $stmt->execute([$idEquipo]);

            $stmt = $pdo->prepare(
                "DELETE FROM seguridad_equipo WHERE id_equipo = ?"
            );
            $stmt->execute([$idEquipo]);

            $stmt = $pdo->prepare(
                "DELETE FROM movimientos WHERE id_equipo = ?"
            );
            $stmt->execute([$idEquipo]);

            $stmt = $pdo->prepare(
                "DELETE FROM mantenciones WHERE id_equipo = ?"
            );
            $stmt->execute([$idEquipo]);

            $detalleAuditoria =
                'Equipo eliminado: ' .
                $equipo['nombre_equipo'] .
                ' | Inventario: ' .
                ($equipo['numero_inventario'] ?: 'Sin inventario') .
                ' | Serie: ' .
                ($equipo['numero_serie'] ?: 'Sin serie');

            $direccionIp = $_SERVER['REMOTE_ADDR'] ?? null;

            $stmt = $pdo->prepare(
                "INSERT INTO auditoria
                    (
                        id_usuario,
                        accion,
                        entidad,
                        id_registro,
                        detalle,
                        fecha,
                        direccion_ip
                    )
                 VALUES
                    (?, ?, ?, ?, ?, NOW(), ?)"
            );

            $stmt->execute([
                $_SESSION['id_usuario'],
                'ELIMINAR',
                'EQUIPO',
                $idEquipo,
                $detalleAuditoria,
                $direccionIp
            ]);

            $stmt = $pdo->prepare(
                "DELETE FROM equipos WHERE id_equipo = ?"
            );

            $stmt->execute([$idEquipo]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('No fue posible eliminar el equipo.');
            }

            $pdo->commit();

            header('Location: index.php?eliminado=1');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'No fue posible eliminar el equipo. Verifique que no existan otros registros relacionados.';
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

    <title>Eliminar equipo - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>

        <h1>SIGIC-HHHA</h1>

        <p>Eliminación de Equipo Computacional</p>

    </div>

    <div class="usuario-info">

        <p>
            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION['nombre'] ?? ''
                );
                ?>
            </strong>
        </p>

        <p>
            Perfil:
            <?php
            echo htmlspecialchars(
                $_SESSION['rol'] ?? ''
            );
            ?>
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

    <div
        style="
            max-width: 750px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
        "
    >

        <h2>Eliminar equipo</h2>

        <p>
            Está a punto de eliminar el siguiente equipo:
        </p>

        <div
            style="
                background: #f4f5f6;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
            "
        >

            <p>
                <strong>Nombre:</strong>
                <?php echo htmlspecialchars($equipo['nombre_equipo']); ?>
            </p>

            <p>
                <strong>Número de inventario:</strong>
                <?php echo htmlspecialchars($equipo['numero_inventario'] ?? '-'); ?>
            </p>

            <p>
                <strong>Número de serie:</strong>
                <?php echo htmlspecialchars($equipo['numero_serie'] ?? '-'); ?>
            </p>

        </div>

        <p>
            <strong>Advertencia:</strong>
            esta acción eliminará también los componentes,
            interfaces de red, revisiones de seguridad,
            movimientos y mantenciones asociados al equipo.
        </p>

        <?php if ($error !== ''): ?>

            <div
                style="
                    margin: 20px 0;
                    padding: 15px;
                    background: #f8d7da;
                    border-radius: 6px;
                "
            >
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="confirmar"
                value="SI"
            >

            <div
                style="
                    display: flex;
                    gap: 10px;
                    margin-top: 25px;
                "
            >

                <button
                    type="submit"
                    class="btn-primary"
                    onclick="return confirm('¿Está seguro de que desea eliminar este equipo? Esta acción no se puede deshacer.');"
                >
                    Eliminar definitivamente
                </button>

                <a
                    href="ver.php?id=<?php echo $idEquipo; ?>"
                    class="btn-primary"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>