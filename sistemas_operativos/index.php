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
        id_so,
        nombre,
        version,
        arquitectura
     FROM sistemas_operativos
     ORDER BY nombre, version"
);

$sistemas = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sistemas Operativos - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

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

            <h2>Sistemas Operativos</h2>

            <p>
                Total:
                <strong>
                    <?php echo count($sistemas); ?>
                </strong>
            </p>

        </div>

        <a
            href="crear.php"
            class="btn-primary"
        >
            + Crear sistema operativo
        </a>

    </div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Versión</th>
                    <th>Arquitectura</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

                <?php if (count($sistemas) > 0): ?>

                    <?php foreach ($sistemas as $so): ?>

                        <tr>

                            <td>
                                <?php echo $so['id_so']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($so['nombre']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($so['version']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($so['arquitectura']); ?>
                            </td>

                            <td>

                                <a
                                    href="editar.php?id=<?php echo $so['id_so']; ?>"
                                    class="btn-small"
                                >
                                    Modificar
                                </a>

                                <a
                                    href="eliminar.php?id=<?php echo $so['id_so']; ?>"
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
                            No existen sistemas operativos registrados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>