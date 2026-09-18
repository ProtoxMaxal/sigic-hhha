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
        id_estado,
        nombre,
        descripcion
     FROM estados_equipo
     ORDER BY id_estado"
);

$estados = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Estados de equipo - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Estados de Equipo</p>
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

</header>

<main class="equipos-content">

    <div class="equipos-header">

        <div>

            <h2>Estados de equipo</h2>

            <p>
                Total de estados:
                <strong>
                    <?php echo count($estados); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Crear estado
        </a>

    </div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($estados) > 0): ?>

                    <?php foreach ($estados as $estado): ?>

                        <tr>

                            <td>
                                <?php echo $estado['id_estado']; ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $estado['nombre']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $estado['descripcion']
                                );
                                ?>
                            </td>

                            <td>

                                <a
                                    href="editar.php?id=<?php echo $estado['id_estado']; ?>"
                                    class="btn-small"
                                >
                                    Modificar
                                </a>

                                <a
                                    href="eliminar.php?id=<?php echo $estado['id_estado']; ?>"
                                    class="btn-small"
                                >
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="4">
                            No existen estados registrados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>