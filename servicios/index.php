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
        id_servicio,
        nombre,
        activo
     FROM servicios
     ORDER BY nombre"
);

$servicios = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Servicios - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Gestión de Servicios</p>
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

            <h2>Servicios</h2>

            <p>
                Total de servicios:
                <strong>
                    <?php echo count($servicios); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Crear servicio
        </a>

    </div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($servicios) > 0): ?>

                    <?php foreach ($servicios as $servicio): ?>

                        <tr>

                            <td>
                                <?php echo $servicio['id_servicio']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($servicio['nombre']); ?>
                            </td>

                            <td>
                                <?php echo $servicio['activo']
                                    ? 'Activo'
                                    : 'Inactivo'; ?>
                            </td>

                            <td>

                                <a
                                    href="editar.php?id=<?php echo $servicio['id_servicio']; ?>"
                                    class="btn-small"
                                >
                                    Modificar
                                </a>

                                <a
                                    href="eliminar.php?id=<?php echo $servicio['id_servicio']; ?>"
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
                            No existen servicios registrados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>