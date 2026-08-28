-- =====================================================
-- SIGIC-HHHA
-- Consultas GoogleSQL utilizadas en BigQuery Sandbox
-- =====================================================


-- 1. Cantidad de equipos por servicio

SELECT
    servicio,
    COUNT(*) AS total_equipos
FROM `sigic-hhha.sigic_hhha_analitica.inventario_equipos`
GROUP BY servicio
ORDER BY total_equipos DESC;


-- 2. Cantidad de equipos por sistema operativo

SELECT
    sistema_operativo,
    version_so,
    COUNT(*) AS total_equipos
FROM `sigic-hhha.sigic_hhha_analitica.inventario_equipos`
GROUP BY sistema_operativo, version_so
ORDER BY total_equipos DESC;


-- 3. Estado de antivirus y actualización

SELECT
    antivirus,
    activo,
    actualizado,
    COUNT(*) AS total_equipos
FROM `sigic-hhha.sigic_hhha_analitica.seguridad_equipos`
GROUP BY
    antivirus,
    activo,
    actualizado
ORDER BY total_equipos DESC;