<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        m.id_mantencion,
        e.nombre_equipo,
        e.numero_inventario,
        u.nombre AS usuario,
        m.fecha,
        m.tipo,
        m.detalle,
        m.estado
    FROM mantenciones m

    INNER JOIN equipos e
        ON m.id_equipo = e.id_equipo

    INNER JOIN usuarios u
        ON m.id_usuario = u.id_usuario

    ORDER BY m.fecha DESC,
             m.id_mantencion DESC"
);

$mantenciones = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mantenciones - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Historial de Mantenciones</p>
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

            <h2>Mantenciones</h2>

            <p>
                Total de mantenciones:
                <strong>
                    <?php echo count($mantenciones); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Registrar mantención
        </a>

    </div>


    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Equipo</th>
                    <th>Inventario</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($mantenciones) > 0): ?>

                    <?php foreach ($mantenciones as $mantencion): ?>

                        <tr>

                            <td>
                                <?php echo $mantencion['id_mantencion']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['nombre_equipo']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['numero_inventario']
                                    ?? '-'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['tipo']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['estado']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['detalle']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['usuario']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $mantencion['fecha']
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">
                            No existen mantenciones registradas.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>