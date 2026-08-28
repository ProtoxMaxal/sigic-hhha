<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

/* Solo el Administrador puede acceder */
if ($_SESSION['rol'] !== 'Administrador') {
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$consulta = $pdo->query(
    "SELECT
        a.id_auditoria,
        u.nombre AS usuario,
        a.accion,
        a.entidad,
        a.id_registro,
        a.detalle,
        a.fecha,
        a.direccion_ip
    FROM auditoria a
    LEFT JOIN usuarios u
        ON a.id_usuario = u.id_usuario
    ORDER BY a.fecha DESC, a.id_auditoria DESC"
);

$registros = $consulta->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Auditoría - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

    <header class="dashboard-header">

        <div>
            <h1>SIGIC-HHHA</h1>
            <p>Registro de Auditoría del Sistema</p>
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

                <h2>Auditoría</h2>

                <p>
                    Total de registros:
                    <strong>
                        <?php echo count($registros); ?>
                    </strong>
                </p>

            </div>

        </div>


        <div class="tabla-contenedor">

            <table class="tabla-equipos">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th>ID Registro</th>
                        <th>Detalle</th>
                        <th>Fecha</th>
                        <th>IP</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (count($registros) > 0): ?>

                        <?php foreach ($registros as $registro): ?>

                            <tr>

                                <td>
                                    <?php echo $registro['id_auditoria']; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registro['usuario'] ?? 'Usuario no disponible'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($registro['accion']); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($registro['entidad']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registro['id_registro'] ?? '-'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registro['detalle'] ?? '-'
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($registro['fecha']); ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registro['direccion_ip'] ?? '-'
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8">
                                No existen registros de auditoría.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>

</html>