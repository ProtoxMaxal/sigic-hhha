<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        e.id_equipo,
        e.nombre_equipo,
        e.numero_inventario,
        e.numero_serie,
        e.uuid,
        e.modelo,
        e.tipo,
        m.nombre AS marca,
        est.nombre AS estado,
        u.nombre AS ubicacion,
        s.nombre AS servicio,
        so.nombre AS sistema_operativo,
        so.version AS version_so,
        r.nombre AS responsable
    FROM equipos e
    LEFT JOIN marcas m
        ON e.id_marca = m.id_marca
    INNER JOIN estados_equipo est
        ON e.id_estado = est.id_estado
    INNER JOIN ubicaciones u
        ON e.id_ubicacion = u.id_ubicacion
    INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
    LEFT JOIN sistemas_operativos so
        ON e.id_so = so.id_so
    LEFT JOIN responsables r
        ON e.id_responsable = r.id_responsable
    ORDER BY e.id_equipo DESC"
);

$equipos = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Equipos - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >
</head>

<body class="dashboard-page">

    <header class="dashboard-header">

        <div>
            <h1>SIGIC-HHHA</h1>
            <p>Gestión de Equipos Computacionales</p>
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

            <a href="../dashboard.php" class="logout-link">
                Volver al inicio
            </a>

        </div>

    </header>


    <main class="equipos-content">

        <div class="equipos-header">

    <div>
        <h2>Equipos registrados</h2>

        <p>
            Total de equipos:
            <strong><?php echo count($equipos); ?></strong>
        </p>
    </div>

    <a href="crear.php" class="btn-primary">
        + Registrar equipo
    </a>

</div>


        <div class="tabla-contenedor">

            <table class="tabla-equipos">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Inventario</th>
                        <th>Serie</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Servicio</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Sistema Operativo</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($equipos) > 0): ?>

                        <?php foreach ($equipos as $equipo): ?>

                            <tr>

                                <td>
                                    <?php echo $equipo['id_equipo']; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['nombre_equipo']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['numero_inventario'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['numero_serie'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['marca'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['modelo'] ?? '-'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['servicio']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['ubicacion']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($equipo['estado']); ?>
                                </td>

                                <td>
                                    <?php

                                    $so = $equipo['sistema_operativo'] ?? '-';

                                    if (!empty($equipo['version_so'])) {
                                        $so .= ' ' . $equipo['version_so'];
                                    }

                                    echo htmlspecialchars($so);

                                    ?>
                                </td>
                                <td>

    <a
        href="ver.php?id=<?php echo $equipo['id_equipo']; ?>"
        class="btn-small"
    >
        Ver ficha
    </a>

</td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="11">
                                No existen equipos registrados.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>

</html>