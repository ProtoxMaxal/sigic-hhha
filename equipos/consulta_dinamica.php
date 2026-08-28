<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
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

    <title>Consulta dinámica - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

</head>

<body class="dashboard-page">

<header class="dashboard-header">

    <div>
        <h1>SIGIC-HHHA</h1>
        <p>Consulta Dinámica de Equipos</p>
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

            <h2>Consulta dinámica</h2>

            <p>
                Información obtenida mediante API REST y JavaScript.
            </p>

        </div>

        <button
            type="button"
            id="btnCargar"
            class="btn-primary"
        >
            Cargar equipos
        </button>

    </div>


    <div id="mensajeApi"></div>


    <div class="tabla-contenedor">

        <table class="tabla-equipos">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Inventario</th>
                    <th>Marca</th>
                    <th>Estado</th>
                    <th>Servicio</th>
                    <th>Ubicación</th>
                </tr>

            </thead>

            <tbody id="tablaEquipos">

                <tr>
                    <td colspan="7">
                        Presiona "Cargar equipos".
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</main>


<script>

const boton = document.getElementById('btnCargar');
const tabla = document.getElementById('tablaEquipos');
const mensaje = document.getElementById('mensajeApi');


boton.addEventListener('click', async function () {

    boton.disabled = true;
    boton.textContent = 'Cargando...';

    mensaje.textContent = '';

    try {

        const respuesta = await fetch('../api/equipos.php');

        if (!respuesta.ok) {
            throw new Error(
                'Error HTTP: ' + respuesta.status
            );
        }

        const resultado = await respuesta.json();

        tabla.innerHTML = '';

        if (
            !resultado.ok ||
            resultado.data.length === 0
        ) {

            tabla.innerHTML = `
                <tr>
                    <td colspan="7">
                        No existen equipos disponibles.
                    </td>
                </tr>
            `;

            return;
        }


        resultado.data.forEach(function (equipo) {

            const fila = document.createElement('tr');

            fila.innerHTML = `
                <td>${equipo.id_equipo}</td>
                <td>${escapeHtml(equipo.nombre_equipo)}</td>
                <td>${escapeHtml(equipo.numero_inventario ?? '-')}</td>
                <td>${escapeHtml(equipo.marca ?? '-')}</td>
                <td>${escapeHtml(equipo.estado)}</td>
                <td>${escapeHtml(equipo.servicio)}</td>
                <td>${escapeHtml(equipo.ubicacion)}</td>
            `;

            tabla.appendChild(fila);
        });


        mensaje.textContent =
            'Equipos cargados correctamente desde la API.';

    } catch (error) {

        mensaje.textContent =
            'No fue posible obtener la información.';

        console.error(error);

    } finally {

        boton.disabled = false;
        boton.textContent = 'Cargar equipos';
    }

});


function escapeHtml(valor) {

    const elemento = document.createElement('div');

    elemento.textContent = valor;

    return elemento.innerHTML;
}

</script>

</body>

</html>