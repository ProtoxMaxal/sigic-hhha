<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['rol'] !== 'Administrador') {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        id_responsable,
        nombre,
        cargo,
        activo
    FROM responsables
    ORDER BY id_responsable DESC"
);

$responsables = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Responsables - SIGIC-HHHA</title>

    <link
    rel="stylesheet"
    href="../public/css/styles.css"
>

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

            <h2>Responsables</h2>

            <p>
                Total de responsables:
                <strong>
                    <?php echo count($responsables); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Crear responsable
        </a>

    </div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($responsables) > 0): ?>

                    <?php foreach ($responsables as $responsable): ?>

                        <tr>

                            <td>
                                <?php echo $responsable['id_responsable']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $responsable['nombre']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $responsable['cargo']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $responsable['activo']
                                    ? 'Activo'
                                    : 'Inactivo';
                                ?>
                            </td>

                            <td>

                                <a
                                    href="editar.php?id=<?php echo $responsable['id_responsable']; ?>"
                                    class="btn-small"
                                >
                                    Modificar
                                </a>

                                <a
                                    href="eliminar.php?id=<?php echo $responsable['id_responsable']; ?>"
                                    class="btn-small"
                                >
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="5">
                            No existen responsables registrados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>