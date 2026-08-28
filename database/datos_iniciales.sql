-- =====================================================
-- SIGIC-HHHA
-- Datos iniciales del sistema
-- No contiene usuarios, contraseñas ni equipos reales.
-- =====================================================


-- ROLES
INSERT IGNORE INTO roles (nombre, descripcion)
VALUES
(
    'Administrador',
    'Acceso completo al sistema'
),
(
    'Técnico',
    'Acceso a funciones operativas del inventario'
);


-- ESTADOS DE EQUIPO
INSERT IGNORE INTO estados_equipo (nombre, descripcion)
VALUES
(
    'Operativo',
    'Equipo en funcionamiento normal'
),
(
    'En mantención',
    'Equipo en proceso de revisión o reparación'
),
(
    'Inactivo',
    'Equipo fuera de uso temporal'
),
(
    'En bodega',
    'Equipo almacenado'
),
(
    'Dado de baja',
    'Equipo retirado definitivamente del inventario'
);


-- SERVICIO INICIAL DE DEMOSTRACIÓN
INSERT IGNORE INTO servicios (nombre, activo)
VALUES
(
    'Informática',
    1
);


-- UBICACIONES INICIALES DE DEMOSTRACIÓN
INSERT IGNORE INTO ubicaciones (
    id_servicio,
    nombre,
    detalle,
    activo
)
SELECT
    id_servicio,
    'Oficina Informática',
    'Ubicación inicial de demostración',
    1
FROM servicios
WHERE nombre = 'Informática';


INSERT IGNORE INTO ubicaciones (
    id_servicio,
    nombre,
    detalle,
    activo
)
SELECT
    id_servicio,
    'Bodega Informática',
    'Ubicación inicial de demostración',
    1
FROM servicios
WHERE nombre = 'Informática';


-- MARCAS DE EJEMPLO
INSERT IGNORE INTO marcas (nombre)
VALUES
('HP'),
('Lenovo'),
('Dell'),
('Acer');