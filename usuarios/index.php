<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

/* Solo Administrador */
if ($_SESSION['rol'] !== 'Administrador') {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        u.id_usuario,
        u.nombre,
        u.correo,
        u.activo,
        u.fecha_creacion,
        r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r
        ON u.id_rol = r.id_rol
    ORDER BY u.id_usuario DESC"
);

$usuarios = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Usuarios - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Usuarios</p>
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

            <h2>Usuarios</h2>

            <p>
                Total de usuarios:
                <strong>
                    <?php echo count($usuarios); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Crear usuario
        </a>

    </div>


    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha de creación</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($usuarios) > 0): ?>

                    <?php foreach ($usuarios as $usuario): ?>

                        <tr>

                            <td>
                                <?php echo $usuario['id_usuario']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $usuario['nombre']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $usuario['correo']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $usuario['rol']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $usuario['activo']
                                    ? 'Activo'
                                    : 'Inactivo';
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $usuario['fecha_creacion']
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            No existen usuarios registrados.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>