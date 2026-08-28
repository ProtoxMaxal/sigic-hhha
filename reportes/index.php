<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';


/* Total de equipos */
$totalEquipos = $pdo->query(
    "SELECT COUNT(*)
     FROM equipos"
)->fetchColumn();


/* Equipos por estado */
$consultaEstados = $pdo->query(
    "SELECT
        est.nombre AS estado,
        COUNT(e.id_equipo) AS total
    FROM estados_equipo est
    LEFT JOIN equipos e
        ON e.id_estado = est.id_estado
    GROUP BY est.id_estado, est.nombre
    ORDER BY total DESC, est.nombre"
);

$equiposPorEstado = $consultaEstados->fetchAll();


/* Equipos por sistema operativo */
$consultaSO = $pdo->query(
    "SELECT
        COALESCE(so.nombre, 'Sin sistema operativo') AS sistema_operativo,
        so.version,
        COUNT(e.id_equipo) AS total
    FROM equipos e
    LEFT JOIN sistemas_operativos so
        ON e.id_so = so.id_so
    GROUP BY so.id_so, so.nombre, so.version
    ORDER BY total DESC"
);

$equiposPorSO = $consultaSO->fetchAll();


/* Equipos por ubicación */
$consultaUbicaciones = $pdo->query(
    "SELECT
        s.nombre AS servicio,
        u.nombre AS ubicacion,
        COUNT(e.id_equipo) AS total
    FROM ubicaciones u
    INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
    LEFT JOIN equipos e
        ON e.id_ubicacion = u.id_ubicacion
    GROUP BY
        u.id_ubicacion,
        s.nombre,
        u.nombre
    ORDER BY total DESC, s.nombre, u.nombre"
);

$equiposPorUbicacion = $consultaUbicaciones->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reportes - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Reportes de Inventario</p>
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
            <h2>Reportes</h2>
            <p>Indicadores generales del inventario computacional.</p>
        </div>

        <a
            href="exportar_csv.php"
            class="btn-primary"
        >
            Exportar CSV
        </a>

    </div>


    <!-- TOTAL -->
    <div class="dashboard-cards">

        <article class="dashboard-card">

            <h3>Total de equipos</h3>

            <p>
                <strong>
                    <?php echo $totalEquipos; ?>
                </strong>
                equipos registrados
            </p>

        </article>

    </div>


    <!-- POR ESTADO -->
    <section class="ficha-seccion">

        <h3>Equipos por estado</h3>

        <div class="tabla-contenedor">

            <table class="tabla-equipos">

                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($equiposPorEstado as $fila): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($fila['estado']); ?>
                            </td>

                            <td>
                                <?php echo $fila['total']; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- POR SISTEMA OPERATIVO -->
    <section class="ficha-seccion">

        <h3>Equipos por sistema operativo</h3>

        <div class="tabla-contenedor">

            <table class="tabla-equipos">

                <thead>
                    <tr>
                        <th>Sistema operativo</th>
                        <th>Versión</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($equiposPorSO as $fila): ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $fila['sistema_operativo']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $fila['version'] ?? '-'
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo $fila['total']; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- POR UBICACIÓN -->
    <section class="ficha-seccion">

        <h3>Equipos por ubicación</h3>

        <div class="tabla-contenedor">

            <table class="tabla-equipos">

                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Ubicación</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($equiposPorUbicacion as $fila): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($fila['servicio']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($fila['ubicacion']); ?>
                            </td>

                            <td>
                                <?php echo $fila['total']; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>

</html>