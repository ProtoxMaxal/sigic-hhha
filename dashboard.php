<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

/* Contadores del sistema */
$totalEquipos = $pdo->query(
    "SELECT COUNT(*) FROM equipos"
)->fetchColumn();

$totalMovimientos = $pdo->query(
    "SELECT COUNT(*) FROM movimientos"
)->fetchColumn();

$totalMantenciones = $pdo->query(
    "SELECT COUNT(*) FROM mantenciones"
)->fetchColumn();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Panel principal - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="public/css/styles.css"
    >
</head>

<body class="dashboard-page">

    <!-- ENCABEZADO -->
    <header class="dashboard-header">

        <div>
            <h1>SIGIC-HHHA</h1>
            <p>Gestión e Inventario de Equipos Computacionales</p>
        </div>

        <div class="usuario-info">

            <p>
                <strong>
                    <?php
                    echo htmlspecialchars($_SESSION['nombre']);
                    ?>
                </strong>
            </p>

            <p>
                Perfil:
                <?php
                echo htmlspecialchars($_SESSION['rol']);
                ?>
            </p>

            <a
                href="logout.php"
                class="logout-link"
            >
                Cerrar sesión
            </a>

        </div>

    </header>


    <div class="dashboard-container">

        <!-- MENÚ LATERAL -->
        <nav class="sidebar">

            <ul>

                <li>
                    <a href="dashboard.php">
                        Inicio
                    </a>
                </li>

                <li>
                    <a href="equipos/index.php">
                             Equipos
</a>
                </li>

                <li>
                    <a href="movimientos/index.php">
                             Movimientos
</a>
                </li>

                <li>
                    <a href="mantenciones/index.php">
                             Mantenciones
</a>
                </li>

                <li>
                    <a href="reportes/index.php">
                             Reportes
</a>
                </li>

                <?php if ($_SESSION['rol'] === 'Administrador'): ?>

                    <li>
                       <a href="usuarios/index.php">
                                Usuarios
</a>
                    </li>

                    <li>
                        <a href="auditoria/index.php">
    Auditoría
</a>
                    </li>

                <?php endif; ?>

            </ul>

        </nav>


        <!-- CONTENIDO -->
        <main class="dashboard-content">

            <h2>Panel principal</h2>

            <p>
                Bienvenido al Sistema Web de Gestión e Inventario
                Seguro de Equipos Computacionales.
            </p>


            <div class="dashboard-cards">

                <article class="dashboard-card">

                    <h3>Equipos registrados</h3>

                    <p>
                        Total:
                        <strong>
                            <?php echo $totalEquipos; ?>
                        </strong>
                    </p>

                </article>


                <article class="dashboard-card">

                    <h3>Movimientos</h3>

                    <p>
                        Total:
                        <strong>
                            <?php echo $totalMovimientos; ?>
                        </strong>
                    </p>

                </article>


                <article class="dashboard-card">

                    <h3>Mantenciones</h3>

                    <p>
                        Total:
                        <strong>
                            <?php echo $totalMantenciones; ?>
                        </strong>
                    </p>

                </article>

            </div>

        </main>

    </div>

</body>

</html>