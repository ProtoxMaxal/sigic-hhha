<?php

session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

/* =====================================================
   VALIDAR ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id_equipo = (int) $_GET['id'];
$error = '';


/* =====================================================
   OBTENER EQUIPO
===================================================== */

$stmt = $pdo->prepare("
    SELECT
        id_equipo,
        nombre_equipo,
        numero_inventario,
        numero_serie,
        uuid,
        modelo,
        tipo,
        observaciones,
        id_marca,
        id_estado,
        id_ubicacion,
        id_so,
        id_responsable
    FROM equipos
    WHERE id_equipo = ?
");

$stmt->execute([$id_equipo]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header('Location: index.php');
    exit;
}


/* =====================================================
   OBTENER MARCAS
===================================================== */

$marcas = $pdo->query("
    SELECT
        id_marca,
        nombre
    FROM marcas
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   OBTENER ESTADOS
===================================================== */

$estados = $pdo->query("
    SELECT
        id_estado,
        nombre
    FROM estados_equipo
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   OBTENER UBICACIONES + SERVICIO
===================================================== */

$ubicaciones = $pdo->query("
    SELECT
        u.id_ubicacion,
        u.nombre AS ubicacion,
        s.nombre AS servicio
    FROM ubicaciones u
    INNER JOIN servicios s
        ON u.id_servicio = s.id_servicio
    ORDER BY s.nombre, u.nombre
")->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   OBTENER SISTEMAS OPERATIVOS

   arquitectura pertenece a sistemas_operativos,
   NO a equipos.
===================================================== */

$sistemas_operativos = $pdo->query("
    SELECT
        id_so,
        nombre,
        version,
        arquitectura
    FROM sistemas_operativos
    ORDER BY nombre, version
")->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   OBTENER RESPONSABLES
===================================================== */

$responsables = $pdo->query("
    SELECT
        id_responsable,
        nombre,
        cargo
    FROM responsables
    ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   PROCESAR MODIFICACIÓN
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_equipo = trim($_POST['nombre_equipo'] ?? '');
    $numero_inventario = trim($_POST['numero_inventario'] ?? '');
    $numero_serie = trim($_POST['numero_serie'] ?? '');
    $uuid = trim($_POST['uuid'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    $id_marca = !empty($_POST['id_marca'])
        ? (int) $_POST['id_marca']
        : null;

    $id_estado = !empty($_POST['id_estado'])
        ? (int) $_POST['id_estado']
        : null;

    $id_ubicacion = !empty($_POST['id_ubicacion'])
        ? (int) $_POST['id_ubicacion']
        : null;

    $id_so = !empty($_POST['id_so'])
        ? (int) $_POST['id_so']
        : null;

    $id_responsable = !empty($_POST['id_responsable'])
        ? (int) $_POST['id_responsable']
        : null;


    /* VALIDACIONES */

    if ($nombre_equipo === '') {

        $error = 'El nombre del equipo es obligatorio.';

    } elseif ($id_estado === null) {

        $error = 'Debe seleccionar un estado.';

    } elseif ($id_ubicacion === null) {

        $error = 'Debe seleccionar una ubicación.';

    } else {

        try {

            /* ==========================================
               ACTUALIZAR EQUIPO
            ========================================== */

            $sql = "
                UPDATE equipos
                SET
                    nombre_equipo = :nombre_equipo,
                    numero_inventario = :numero_inventario,
                    numero_serie = :numero_serie,
                    uuid = :uuid,
                    modelo = :modelo,
                    tipo = :tipo,
                    observaciones = :observaciones,
                    id_marca = :id_marca,
                    id_estado = :id_estado,
                    id_ubicacion = :id_ubicacion,
                    id_so = :id_so,
                    id_responsable = :id_responsable
                WHERE id_equipo = :id_equipo
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([

                ':nombre_equipo' =>
                    $nombre_equipo,

                ':numero_inventario' =>
                    $numero_inventario !== ''
                        ? $numero_inventario
                        : null,

                ':numero_serie' =>
                    $numero_serie !== ''
                        ? $numero_serie
                        : null,

                ':uuid' =>
                    $uuid !== ''
                        ? $uuid
                        : null,

                ':modelo' =>
                    $modelo !== ''
                        ? $modelo
                        : null,

                ':tipo' =>
                    $tipo !== ''
                        ? $tipo
                        : null,

                ':observaciones' =>
                    $observaciones !== ''
                        ? $observaciones
                        : null,

                ':id_marca' =>
                    $id_marca,

                ':id_estado' =>
                    $id_estado,

                ':id_ubicacion' =>
                    $id_ubicacion,

                ':id_so' =>
                    $id_so,

                ':id_responsable' =>
                    $id_responsable,

                ':id_equipo' =>
                    $id_equipo

            ]);


            /* ==========================================
               REGISTRAR AUDITORÍA

               Si la estructura de auditoría no coincide,
               la modificación igualmente se conserva.
            ========================================== */

            try {

                $stmtAuditoria = $pdo->prepare("
                    INSERT INTO auditoria
                    (
                        id_usuario,
                        accion,
                        modulo,
                        detalle,
                        fecha
                    )
                    VALUES
                    (
                        :id_usuario,
                        :accion,
                        :modulo,
                        :detalle,
                        NOW()
                    )
                ");

                $stmtAuditoria->execute([

                    ':id_usuario' =>
                        $_SESSION['id_usuario'],

                    ':accion' =>
                        'MODIFICAR',

                    ':modulo' =>
                        'EQUIPOS',

                    ':detalle' =>
                        'Se modificó el equipo ID ' . $id_equipo

                ]);

            } catch (PDOException $e) {

                // No interrumpe la modificación del equipo.

            }


            /* ==========================================
               REDIRECCIONAR A LA FICHA
            ========================================== */

            header(
                'Location: ver.php?id=' .
                $id_equipo .
                '&editado=1'
            );

            exit;


        } catch (PDOException $e) {

            $error =
                'No se pudo modificar el equipo: ' .
                $e->getMessage();

        }

    }


    /* ================================================
       MANTENER DATOS SI OCURRE UN ERROR
    ================================================ */

    $equipo['nombre_equipo'] = $nombre_equipo;
    $equipo['numero_inventario'] = $numero_inventario;
    $equipo['numero_serie'] = $numero_serie;
    $equipo['uuid'] = $uuid;
    $equipo['modelo'] = $modelo;
    $equipo['tipo'] = $tipo;
    $equipo['observaciones'] = $observaciones;
    $equipo['id_marca'] = $id_marca;
    $equipo['id_estado'] = $id_estado;
    $equipo['id_ubicacion'] = $id_ubicacion;
    $equipo['id_so'] = $id_so;
    $equipo['id_responsable'] = $id_responsable;
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

    <title>Modificar equipo - SIGIC-HHHA</title>

    <link
        rel="stylesheet"
        href="../public/css/styles.css"
    >

    <style>

        .editar-contenedor {
            max-width: 1100px;
            margin: 30px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }

        .editar-contenedor h2 {
            margin-top: 0;
        }

        .editar-contenedor > p {
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .campo {
            display: flex;
            flex-direction: column;
        }

        .campo-completo {
            grid-column: 1 / -1;
        }

        .campo label {
            font-weight: bold;
            margin-bottom: 7px;
        }

        .campo input,
        .campo select,
        .campo textarea {
            padding: 11px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            font-size: 15px;
            box-sizing: border-box;
            width: 100%;
        }

        .campo textarea {
            min-height: 100px;
            resize: vertical;
        }

        .acciones-editar {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-guardar {
            padding: 11px 20px;
            border: none;
            border-radius: 5px;
            background: #222222;
            color: #ffffff;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-cancelar {
            padding: 11px 20px;
            border-radius: 5px;
            background: #eeeeee;
            color: #222222;
            text-decoration: none;
            font-weight: bold;
        }

        .mensaje-error {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        @media (max-width: 700px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .campo-completo {
                grid-column: auto;
            }

        }

    </style>

</head>


<body class="dashboard-page">


<header class="dashboard-header">

    <div>

        <h1>SIGIC-HHHA</h1>

        <p>
            Gestión de Equipos Computacionales
        </p>

    </div>


    <div class="usuario-info">

        <p>

            <strong>
                <?php
                echo htmlspecialchars(
                    $_SESSION['nombre']
                );
                ?>
            </strong>

        </p>

        <p>

            Perfil:

            <?php
            echo htmlspecialchars(
                $_SESSION['rol']
            );
            ?>

        </p>

        <a
            href="../equipos/index.php"
            class="logout-link"
        >
            Volver a equipos
        </a>

    </div>

</header>


<main class="equipos-content">


    <div class="editar-contenedor">


        <h2>
            Modificar equipo
        </h2>

        <p>
            Actualice la información del equipo seleccionado.
        </p>


        <?php if ($error !== ''): ?>

            <div class="mensaje-error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-grid">


                <!-- NOMBRE EQUIPO -->

                <div class="campo">

                    <label for="nombre_equipo">
                        Nombre del equipo *
                    </label>

                    <input
                        type="text"
                        id="nombre_equipo"
                        name="nombre_equipo"
                        value="<?php
                        echo htmlspecialchars(
                            $equipo['nombre_equipo'] ?? ''
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- INVENTARIO -->

                <div class="campo">

                    <label for="numero_inventario">
                        Número de inventario
                    </label>

                    <input
                        type="text"
                        id="numero_inventario"
                        name="numero_inventario"
                        value="<?php
                        echo htmlspecialchars(
                            $equipo['numero_inventario'] ?? ''
                        );
                        ?>"
                    >

                </div>


                <!-- SERIE -->

                <div class="campo">

                    <label for="numero_serie">
                        Número de serie
                    </label>

                    <input
                        type="text"
                        id="numero_serie"
                        name="numero_serie"
                        value="<?php
                        echo htmlspecialchars(
                            $equipo['numero_serie'] ?? ''
                        );
                        ?>"
                    >

                </div>


                <!-- UUID -->

                <div class="campo">

                    <label for="uuid">
                        UUID
                    </label>

                    <input
                        type="text"
                        id="uuid"
                        name="uuid"
                        value="<?php
                        echo htmlspecialchars(
                            $equipo['uuid'] ?? ''
                        );
                        ?>"
                    >

                </div>


                <!-- MARCA -->

                <div class="campo">

                    <label for="id_marca">
                        Marca
                    </label>

                    <select
                        id="id_marca"
                        name="id_marca"
                    >

                        <option value="">
                            Seleccione
                        </option>

                        <?php foreach ($marcas as $marca): ?>

                            <option
                                value="<?php
                                echo $marca['id_marca'];
                                ?>"
                                <?php

                                if (
                                    (int)$equipo['id_marca'] ===
                                    (int)$marca['id_marca']
                                ) {
                                    echo 'selected';
                                }

                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $marca['nombre']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- MODELO -->

                <div class="campo">

                    <label for="modelo">
                        Modelo
                    </label>

                    <input
                        type="text"
                        id="modelo"
                        name="modelo"
                        value="<?php
                        echo htmlspecialchars(
                            $equipo['modelo'] ?? ''
                        );
                        ?>"
                    >

                </div>


                <!-- TIPO -->

                <div class="campo">

                    <label for="tipo">
                        Tipo
                    </label>

                    <input
                        type="text"
                        id="tipo"
                        name="tipo"
                        value="<?php
                        echo htmlspecialchars(
                            $equipo['tipo'] ?? ''
                        );
                        ?>"
                    >

                </div>


                <!-- ESTADO -->

                <div class="campo">

                    <label for="id_estado">
                        Estado *
                    </label>

                    <select
                        id="id_estado"
                        name="id_estado"
                        required
                    >

                        <option value="">
                            Seleccione
                        </option>

                        <?php foreach ($estados as $estado): ?>

                            <option
                                value="<?php
                                echo $estado['id_estado'];
                                ?>"
                                <?php

                                if (
                                    (int)$equipo['id_estado'] ===
                                    (int)$estado['id_estado']
                                ) {
                                    echo 'selected';
                                }

                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $estado['nombre']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- UBICACION -->

                <div class="campo campo-completo">

                    <label for="id_ubicacion">
                        Servicio / Ubicación *
                    </label>

                    <select
                        id="id_ubicacion"
                        name="id_ubicacion"
                        required
                    >

                        <option value="">
                            Seleccione
                        </option>

                        <?php foreach ($ubicaciones as $ubicacion): ?>

                            <option
                                value="<?php
                                echo $ubicacion['id_ubicacion'];
                                ?>"
                                <?php

                                if (
                                    (int)$equipo['id_ubicacion'] ===
                                    (int)$ubicacion['id_ubicacion']
                                ) {
                                    echo 'selected';
                                }

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $ubicacion['servicio']
                                    . ' - '
                                    . $ubicacion['ubicacion']
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- SISTEMA OPERATIVO -->

                <div class="campo campo-completo">

                    <label for="id_so">
                        Sistema Operativo
                    </label>

                    <select
                        id="id_so"
                        name="id_so"
                    >

                        <option value="">
                            Sin sistema operativo
                        </option>

                        <?php
                        foreach (
                            $sistemas_operativos as $so
                        ):
                        ?>

                            <option
                                value="<?php
                                echo $so['id_so'];
                                ?>"
                                <?php

                                if (
                                    (int)$equipo['id_so'] ===
                                    (int)$so['id_so']
                                ) {
                                    echo 'selected';
                                }

                                ?>
                            >

                                <?php

                                $nombreSO =
                                    $so['nombre'];

                                if (!empty($so['version'])) {

                                    $nombreSO .=
                                        ' ' .
                                        $so['version'];

                                }

                                if (!empty($so['arquitectura'])) {

                                    $nombreSO .=
                                        ' (' .
                                        $so['arquitectura'] .
                                        ')';

                                }

                                echo htmlspecialchars(
                                    $nombreSO
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- RESPONSABLE -->

                <div class="campo campo-completo">

                    <label for="id_responsable">
                        Responsable
                    </label>

                    <select
                        id="id_responsable"
                        name="id_responsable"
                    >

                        <option value="">
                            Sin responsable
                        </option>

                        <?php
                        foreach (
                            $responsables as $responsable
                        ):
                        ?>

                            <option
                                value="<?php
                                echo $responsable['id_responsable'];
                                ?>"
                                <?php

                                if (
                                    (int)$equipo['id_responsable'] ===
                                    (int)$responsable['id_responsable']
                                ) {
                                    echo 'selected';
                                }

                                ?>
                            >

                                <?php

                                $nombreResponsable =
                                    $responsable['nombre'];

                                if (!empty($responsable['cargo'])) {

                                    $nombreResponsable .=
                                        ' - ' .
                                        $responsable['cargo'];

                                }

                                echo htmlspecialchars(
                                    $nombreResponsable
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- OBSERVACIONES -->

                <div class="campo campo-completo">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                    ><?php
                    echo htmlspecialchars(
                        $equipo['observaciones'] ?? ''
                    );
                    ?></textarea>

                </div>


            </div>


            <div class="acciones-editar">


                <button
                    type="submit"
                    class="btn-guardar"
                >
                    Guardar cambios
                </button>


                <a
                    href="ver.php?id=<?php
                    echo $id_equipo;
                    ?>"
                    class="btn-cancelar"
                >
                    Cancelar
                </a>


            </div>


        </form>


    </div>


</main>


</body>

</html>