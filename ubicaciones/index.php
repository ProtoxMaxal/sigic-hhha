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

$consulta = $pdo->query(
    "SELECT
        u.id_ubicacion,
        u.nombre,
        u.detalle,
        u.activo,
        s.nombre AS servicio
     FROM ubicaciones u
     INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
     ORDER BY s.nombre, u.nombre"
);

$ubicaciones = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ubicaciones - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Ubicaciones</p>
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
    href="../catalogos/index.php"
    class="logout-link"
>
    Volver a catálogo
</a>

    </div>

</header>

<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Ubicaciones</h2>

            <p>
                Total de ubicaciones:
                <strong>
                    <?php echo count($ubicaciones); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Crear ubicación
        </a>

    </div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Servicio</th>
                    <th>Ubicación</th>
                    <th>Detalle</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($ubicaciones) > 0): ?>

                    <?php foreach ($ubicaciones as $ubicacion): ?>

                        <tr>

                            <td>
                                <?php echo $ubicacion['id_ubicacion']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $ubicacion['servicio']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $ubicacion['nombre']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $ubicacion['detalle']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $ubicacion['activo']
                                    ? 'Activo'
                                    : 'Inactivo';
                                ?>
                            </td>

                            <td>

                                <a
                                    href="editar.php?id=<?php echo $ubicacion['id_ubicacion']; ?>"
                                    class="btn-small"
                                >
                                    Modificar
                                </a>

                                <a
                                    href="eliminar.php?id=<?php echo $ubicacion['id_ubicacion']; ?>"
                                    class="btn-small"
                                >
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6">
                            No existen ubicaciones registradas.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>