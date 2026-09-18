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

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Catálogos - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .catalogos-page {
            padding: 35px 30px;
        }

        .catalogos-header {
            margin-bottom: 25px;
        }

        .catalogos-header h2 {
            margin-bottom: 8px;
        }

        .catalogos-header p {
            color: #555;
        }

        .catalogos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .catalogo-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .catalogo-card h3 {
            margin-bottom: 10px;
        }

        .catalogo-card p {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        @media (max-width: 900px) {

            .catalogos-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .catalogos-grid {
                grid-template-columns: 1fr;
            }

            .catalogos-page {
                padding: 20px;
            }

        }

    </style>

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>

        <h1>SIGIC-HHHA</h1>

        <p>Administración de Catálogos</p>

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

<main class="catalogos-page">

    <div class="catalogos-header">

        <h2>Catálogos del sistema</h2>

        <p>
            Administra los datos utilizados en el registro
            y clasificación de los equipos.
        </p>

    </div>

    <div class="catalogos-grid">

<div class="catalogo-card">

    <h3>Responsables</h3>

    <p>
        Administra las personas responsables de los equipos computacionales.
    </p>

    <a
        href="../responsables/index.php"
        class="btn-primary"
    >
        Administrar responsables
    </a>

</div>

        <div class="catalogo-card">

            <h3>Marcas</h3>

            <p>
                Administra las marcas de los equipos computacionales.
            </p>

            <a
                href="../marcas/index.php"
                class="btn-primary"
            >
                Administrar marcas
            </a>

        </div>

        <div class="catalogo-card">

            <h3>Estados de equipo</h3>

            <p>
                Administra los estados disponibles para los equipos.
            </p>

            <a
                href="../estados/index.php"
                class="btn-primary"
            >
                Administrar estados
            </a>

        </div>

        <div class="catalogo-card">

            <h3>Sistemas Operativos</h3>

            <p>
                Administra nombre, versión y arquitectura de los sistemas operativos.
            </p>

            <a
                href="../sistemas_operativos/index.php"
                class="btn-primary"
            >
                Administrar sistemas
            </a>

        </div>

        <div class="catalogo-card">

            <h3>Servicios</h3>

            <p>
                Administra los servicios utilizados para organizar las ubicaciones.
            </p>

            <a
                href="../servicios/index.php"
                class="btn-primary"
            >
                Administrar servicios
            </a>

        </div>

        <div class="catalogo-card">

            <h3>Ubicaciones</h3>

            <p>
                Administra las ubicaciones asociadas a cada servicio.
            </p>

            <a
                href="../ubicaciones/index.php"
                class="btn-primary"
            >
                Administrar ubicaciones
            </a>

        </div>

    </div>

</main>

</body>

</html>