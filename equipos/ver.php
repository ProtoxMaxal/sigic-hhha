<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

/* Validar ID recibido */
$idEquipo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idEquipo) {
    header('Location: index.php');
    exit;
}

/* Obtener información del equipo */
$consulta = $pdo->prepare(
    "SELECT
        e.*,
        m.nombre AS marca,
        est.nombre AS estado,
        u.nombre AS ubicacion,
        s.nombre AS servicio,
        so.nombre AS sistema_operativo,
        so.version AS version_so,
        so.arquitectura,
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

    WHERE e.id_equipo = ?

    LIMIT 1"
);

$consulta->execute([$idEquipo]);

$equipo = $consulta->fetch();

if (!$equipo) {
    header('Location: index.php');
    exit;
}
/* Componentes del equipo */
$consultaComponentes = $pdo->prepare(
    "SELECT
        id_componente,
        tipo,
        fabricante,
        modelo,
        capacidad,
        numero_serie
    FROM componentes
    WHERE id_equipo = ?
    ORDER BY tipo"
);

$consultaComponentes->execute([$idEquipo]);
$componentes = $consultaComponentes->fetchAll();


/* Interfaces de red */
$consultaRed = $pdo->prepare(
    "SELECT
        id_interfaz,
        tipo,
        direccion_mac,
        direccion_ip,
        estado
    FROM interfaces_red
    WHERE id_equipo = ?
    ORDER BY id_interfaz"
);

$consultaRed->execute([$idEquipo]);
$interfacesRed = $consultaRed->fetchAll();


/* Información de seguridad */
$consultaSeguridad = $pdo->prepare(
    "SELECT
        id_seguridad,
        antivirus,
        version,
        activo,
        actualizado,
        fecha_revision
    FROM seguridad_equipo
    WHERE id_equipo = ?
    ORDER BY fecha_revision DESC, id_seguridad DESC"
);

$consultaSeguridad->execute([$idEquipo]);
$registrosSeguridad = $consultaSeguridad->fetchAll();


/* Historial de mantenciones */
$consultaMantenciones = $pdo->prepare(
    "SELECT
        m.id_mantencion,
        m.fecha,
        m.tipo,
        m.detalle,
        m.estado,
        u.nombre AS usuario
    FROM mantenciones m
    INNER JOIN usuarios u
        ON m.id_usuario = u.id_usuario
    WHERE m.id_equipo = ?
    ORDER BY m.fecha DESC, m.id_mantencion DESC"
);

$consultaMantenciones->execute([$idEquipo]);

$mantencionesEquipo = $consultaMantenciones->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Ficha de equipo - SIGIC-HHHA
    </title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

    <header class="dashboard-header">

        <div>

            <h1>SIGIC-HHHA</h1>

            <p>
                Ficha Técnica del Equipo
            </p>

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
                href="index.php"
                class="logout-link"
            >
                Volver a equipos
            </a>

        </div>

    </header>


    <main class="equipos-content">

        <div class="equipos-header">

            <div>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $equipo['nombre_equipo']
                    );
                    ?>
                </h2>

                <p>
                    Ficha técnica del computador
                </p>

            </div>

        </div>


        <section class="ficha-equipo">

            <h3>
                Información general
            </h3>


            <div class="ficha-grid">

                <div>

                    <strong>ID</strong>

                    <p>
                        <?php echo $equipo['id_equipo']; ?>
                    </p>

                </div>


                <div>

                    <strong>
                        Nombre del equipo
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['nombre_equipo']
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>
                        Número de inventario
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['numero_inventario'] ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>
                        Número de serie
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['numero_serie'] ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>UUID</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['uuid'] ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Marca</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['marca'] ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Modelo</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['modelo'] ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Tipo</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['tipo'] ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Estado</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['estado']
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Servicio</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['servicio']
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Ubicación</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['ubicacion']
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>Responsable</strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['responsable']
                            ?? 'Sin responsable'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>
                        Sistema operativo
                    </strong>

                    <p>

                        <?php

                        $sistemaOperativo =
                            $equipo['sistema_operativo']
                            ?? '-';

                        if (!empty($equipo['version_so'])) {

                            $sistemaOperativo .=
                                ' ' .
                                $equipo['version_so'];
                        }

                        echo htmlspecialchars(
                            $sistemaOperativo
                        );

                        ?>

                    </p>

                </div>


                <div>

                    <strong>
                        Arquitectura
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['arquitectura']
                            ?? '-'
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>
                        Fecha de registro
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $equipo['fecha_registro']
                        );
                        ?>
                    </p>

                </div>

            </div>


            <div class="ficha-observaciones">

                <strong>
                    Observaciones
                </strong>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $equipo['observaciones']
                        ?? 'Sin observaciones'
                    );
                    ?>
                </p>

            </div>

        </section>
<!-- HARDWARE -->
<section class="ficha-seccion">

    <div class="equipos-header">

    <h3>Hardware y componentes</h3>

    <a
        href="agregar_componente.php?id=<?php echo $idEquipo; ?>"
        class="btn-primary"
    >
        + Agregar componente
    </a>

</div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Fabricante</th>
                    <th>Modelo</th>
                    <th>Capacidad</th>
                    <th>Número de serie</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($componentes) > 0): ?>

                    <?php foreach ($componentes as $componente): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($componente['tipo']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($componente['fabricante'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($componente['modelo'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($componente['capacidad'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($componente['numero_serie'] ?? '-'); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No hay componentes registrados para este equipo.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- RED -->
<section class="ficha-seccion">

    <div class="equipos-header">

    <h3>Información de red</h3>

    <a
        href="agregar_red.php?id=<?php echo $idEquipo; ?>"
        class="btn-primary"
    >
        + Agregar red
    </a>

</div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Dirección MAC</th>
                    <th>Dirección IP</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($interfacesRed) > 0): ?>

                    <?php foreach ($interfacesRed as $interfaz): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($interfaz['tipo']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($interfaz['direccion_mac'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($interfaz['direccion_ip'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($interfaz['estado'] ?? '-'); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4">
                            No hay interfaces de red registradas.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- SEGURIDAD -->
<section class="ficha-seccion">

    <div class="equipos-header">

    <h3>Seguridad del equipo</h3>

    <a
        href="agregar_seguridad.php?id=<?php echo $idEquipo; ?>"
        class="btn-primary"
    >
        + Agregar revisión
    </a>

</div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>
                <tr>
                    <th>Antivirus</th>
                    <th>Versión</th>
                    <th>Activo</th>
                    <th>Actualizado</th>
                    <th>Fecha de revisión</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($registrosSeguridad) > 0): ?>

                    <?php foreach ($registrosSeguridad as $seguridad): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($seguridad['antivirus'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($seguridad['version'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo $seguridad['activo'] ? 'Sí' : 'No'; ?>
                            </td>

                            <td>
                                <?php echo $seguridad['actualizado'] ? 'Sí' : 'No'; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($seguridad['fecha_revision'] ?? '-'); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No hay información de seguridad registrada.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<!-- MANTENCIONES -->
<section class="ficha-seccion">

    <div class="equipos-header">

        <h3>Historial de mantenciones</h3>

        <a
            href="../mantenciones/crear.php"
            class="btn-primary"
        >
            + Registrar mantención
        </a>

    </div>

    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Detalle</th>
                    <th>Usuario</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($mantencionesEquipo) > 0): ?>

                    <?php foreach ($mantencionesEquipo as $mantencion): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($mantencion['fecha']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($mantencion['tipo']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($mantencion['estado']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($mantencion['detalle']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($mantencion['usuario']); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No existen mantenciones registradas para este equipo.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


    </main>

</body>

</html>