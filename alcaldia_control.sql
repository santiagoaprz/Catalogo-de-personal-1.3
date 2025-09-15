-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-08-2025 a las 19:47:11
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `alcaldia_control`
--
CREATE DATABASE IF NOT EXISTS `alcaldia_control` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `alcaldia_control`;

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `reset_auto_increment`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `reset_auto_increment` ()   BEGIN
    DECLARE next_id INT;
    SELECT MAX(id) + 1 INTO next_id FROM documentos;
    SET @sql = CONCAT('ALTER TABLE documentos AUTO_INCREMENT = ', next_id);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_oficios`
--

DROP TABLE IF EXISTS `auditoria_oficios`;
CREATE TABLE `auditoria_oficios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `numero_oficio` varchar(20) DEFAULT NULL,
  `numero_oficio_usuario` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `auditoria_oficios`:
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogo_personal`
--

DROP TABLE IF EXISTS `catalogo_personal`;
CREATE TABLE `catalogo_personal` (
  `id` int(11) NOT NULL,
  `numero_empleado` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `puesto` varchar(100) NOT NULL,
  `departamento_jud` varchar(100) NOT NULL DEFAULT 'SIN DEPARTAMENTO',
  `dire_fisica` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_institucional` varchar(100) DEFAULT NULL,
  `nuevo_numero_empleado` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `catalogo_personal`:
--

--
-- Volcado de datos para la tabla `catalogo_personal`
--

INSERT INTO `catalogo_personal` (`id`, `numero_empleado`, `nombre`, `puesto`, `departamento_jud`, `dire_fisica`, `telefono`, `extension`, `fecha_registro`, `ultima_actualizacion`, `email_institucional`, `nuevo_numero_empleado`) VALUES
(3, 'EMP-TEST01', 'Nombre Prueba', 'Puesto Prueba', 'Departamento Prueba', NULL, '5512345678', '123', '2025-08-04 18:34:42', '2025-08-04 18:34:42', 'test@tlalpan.cdmx.gob.mx', NULL),
(8, '', 'ASP', 'rh', 'Subdirección de Relaciones Laborales y Capacitación', NULL, '5548857355', '7564', '2025-08-04 19:48:46', '2025-08-05 20:45:08', 'prueba1@tlalpan.cdmx.gob.mx', NULL),
(20, 'EMP-00001', 'julia martinez', 'SECRET', 'Subdirección de Tecnologías de la Información y Comunicaciones', NULL, '987456321', '856', '2025-08-05 21:18:16', '2025-08-07 19:07:52', 'prueba10@tlalpan.cdmx.gob.mx', NULL),
(25, 'EMP-00002', 'paaau', 'Desarrollo de sistemas', 'J.U.D. de Servicios Generales y Apoyo Logístico', NULL, '5548857356', '5454', '2025-08-05 21:24:41', '2025-08-05 21:24:41', 'prueba11@tlalpan.cdmx.gob.mx', NULL),
(26, 'EMP-00003', 'poiujhytre', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', NULL, '5548857307', '2156', '2025-08-15 16:33:00', '2025-08-15 16:33:00', 'prueba111@tlalpan.cdmx.gob.mx', NULL),
(27, 'EMP-00004', 'Alexis Sant', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', NULL, '55489656', '262', '2025-08-28 17:35:37', '2025-08-28 17:35:37', 'asantiago@tlalpan.cdmx.gob.mx', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `editable` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `configuracion`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

DROP TABLE IF EXISTS `documentos`;
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_entrega` date NOT NULL,
  `fecha_recepcion` date DEFAULT NULL,
  `numero_oficio` varchar(50) NOT NULL,
  `remitente` varchar(100) NOT NULL,
  `cargo_remitente` varchar(100) NOT NULL,
  `depto_remitente` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL,
  `asunto` text NOT NULL,
  `tipo` enum('OFICIO','TURNO','CIRCULAR','NOTA_INFORMATIVA','CONOCIMIENTO') NOT NULL,
  `estatus` enum('SEGUIMIENTO','ATENDIDO','TURNADO') NOT NULL,
  `pdf_url` varchar(255) NOT NULL,
  `etapa` enum('RECIBIDO','RESPUESTA','ACUSE') DEFAULT 'RECIBIDO',
  `fecha_respuesta` date DEFAULT NULL,
  `destinatario` varchar(255) DEFAULT NULL,
  `usuario_responde` int(11) DEFAULT NULL,
  `usuario_acusa` int(11) DEFAULT NULL,
  `fecha_acuse` date DEFAULT NULL,
  `dire_fisica` varchar(255) DEFAULT NULL,
  `usuario_registra` int(11) DEFAULT NULL,
  `personal_id` int(11) DEFAULT NULL,
  `jud_destino` varchar(100) DEFAULT NULL,
  `numero_empleado` varchar(20) NOT NULL,
  `numero_oficio_usuario` varchar(50) DEFAULT NULL,
  `email_institucional` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `documentos`:
--   `personal_id`
--       `catalogo_personal` -> `id`
--

--
-- Volcado de datos para la tabla `documentos`
--

INSERT INTO `documentos` (`id`, `fecha_creacion`, `fecha_entrega`, `fecha_recepcion`, `numero_oficio`, `remitente`, `cargo_remitente`, `depto_remitente`, `telefono`, `extension`, `asunto`, `tipo`, `estatus`, `pdf_url`, `etapa`, `fecha_respuesta`, `destinatario`, `usuario_responde`, `usuario_acusa`, `fecha_acuse`, `dire_fisica`, `usuario_registra`, `personal_id`, `jud_destino`, `numero_empleado`, `numero_oficio_usuario`, `email_institucional`) VALUES
(1, '2025-08-04 12:34:42', '2025-08-04', NULL, 'OF-TEST01', 'Nombre Prueba', '', NULL, NULL, NULL, '', 'OFICIO', 'SEGUIMIENTO', '', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, '', NULL, 'test@tlalpan.cdmx.gob.mx'),
(4, '2025-08-04 13:48:46', '2025-08-04', NULL, 'OF-00131', 'ASP', 'Desarrollo de sistemas', 'J.U.D. de Soporte Técnico', '5548857304', '256', 'se realiza prueba para ver si se reflejan la capturas de datos', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754336926_52ac843f.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA04AGOS2025', 'prueba1@tlalpan.cdmx.gob.mx'),
(5, '2025-08-05 12:12:19', '2025-08-05', NULL, 'OF-00132', 'Alexis Santiago Pérez', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '12345678', '321', 'se realiza prueba;\r\nya se ven reflejados las capturas\r\nSe debe probar con un correo igual para ver que sucede.', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754417539_d691ecae.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA05AGOS2025', 'prueba2@tlalpan.cdmx.gob.mx'),
(6, '2025-08-05 12:41:54', '2025-08-05', NULL, 'OF-00133', 'Ricardo Santiago Bernal', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '5548857356', '895', 'se guarda todo menos en la seccion de catalogo', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754419314_10d34567.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA3-05AGOS2025', 'prueba3@tlalpan.cdmx.gob.mx'),
(7, '2025-08-05 12:43:37', '2025-08-05', NULL, 'OF-00134', 'DANIEL SANCHEZ', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '987654321', '753', 'se realiza prueba con mismo correo prueba1@\r\ncon difente nombre ahora es DANIEL SANCHEZ\r\nantes fue ASP con un registro en el catalogo', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754419417_495d033a.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA4-05AGOS2025', 'prueba1@tlalpan.cdmx.gob.mx'),
(8, '2025-08-05 12:50:07', '2025-08-05', NULL, 'OF-00135', 'ASP', 'Desarrollo de sistemas', 'J.U.D. de Soporte Técnico', '5548857356', '896', 'se realiza prueba con el mismo correo para comprobar actualizan departamental\r\nSE QUEDA LIGADO RMEITENTE CON CORREO INST', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754419806_3a3fd9fb.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'Subdirección de Tecnologías de la Información y Comunicaciones', '', 'PRUEBA5-05AGOS2025', 'prueba1@tlalpan.cdmx.gob.mx'),
(9, '2025-08-05 14:38:54', '2025-08-05', NULL, 'OF-00136', 'jesus melgoza', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '5578965412', '2568', 'se realizan pruebas exitosamente corrigiendo las intermitencias de visualizacion de pdf\r\ny se debe mostrar el nuevo registro de jesus melgoza, prueba4@tla... en catalogo.php', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754426334_2c98579a.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA6-05AGOS2025', 'prueba4@tlalpan.cdmx.gob.mx'),
(10, '2025-08-05 14:45:08', '2025-08-05', NULL, 'OF-00137', 'ASP', 'rh', 'Subdirección de Relaciones Laborales y Capacitación', '5548857355', '7564', 'se realizan pruebas exitosamente a excepcion de los nuevos registros en catalogo.php\r\nahora con esta prueba se cambia a ASP prueba1@ a la sub de relaciones laborales', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754426708_7ad59eea.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA7-05AGOS2025', 'prueba1@tlalpan.cdmx.gob.mx'),
(11, '2025-08-05 14:45:20', '2025-08-05', NULL, 'OF-00138', 'ASP', 'rh', 'Subdirección de Relaciones Laborales y Capacitación', '5548857355', '7564', 'se realizan pruebas exitosamente a excepcion de los nuevos registros en catalogo.php\r\nahora con esta prueba se cambia a ASP prueba1@ a la sub de relaciones laborales', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754426720_864ae821.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA7-05AGOS2025', 'prueba1@tlalpan.cdmx.gob.mx'),
(12, '2025-08-05 14:45:40', '2025-08-05', NULL, 'OF-00139', 'ASP', 'rh', 'Subdirección de Relaciones Laborales y Capacitación', '5548857355', '7564', 'se realizan pruebas exitosamente a excepcion de los nuevos registros en catalogo.php\r\nahora con esta prueba se cambia a ASP prueba1@ a la sub de relaciones laborales DIO ERROR SE CAMBIA POR PRUEBA2', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754426740_7399915a.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA7-05AGOS2025', 'prueba2@tlalpan.cdmx.gob.mx'),
(13, '2025-08-05 14:47:38', '2025-08-05', NULL, 'OF-00140', 'ASP', 'rh', 'Subdirección de Relaciones Laborales y Capacitación', '5548857355', '7564', 'se realizan pruebas exitosamente a excepcion de los nuevos registros en catalogo.php\r\nahora con esta prueba se cambia a ASP prueba1@ a la sub de relaciones laborales DIO ERROR SE CAMBIA POR PRUEBA', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754426858_3e40b21f.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, NULL, 'J.U.D. de Soporte Técnico', '', 'PRUEBA7-05AGOS2025', 'prueba1@tlalpan.cdmx.gob.mx'),
(16, '2025-08-05 15:18:16', '2025-08-05', NULL, 'OF-00141', 'julia martinez', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '4564521185', '4564', 'se reealizan pruebas gral', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754428696_246fd286.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'calle prueba', 1, 20, 'J.U.D. de Soporte Técnico', '', 'PRUEBA10-05AGOS2025', 'prueba10@tlalpan.cdmx.gob.mx'),
(17, '2025-08-05 15:24:41', '2025-08-05', NULL, 'OF-00142', 'paaau', 'Desarrollo de sistemas', 'J.U.D. de Servicios Generales y Apoyo Logístico', '5548857356', '5454', 'adfghjk', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754429081_1b9b68b5.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, 25, 'L.C.P. de Gestión Documental', '', 'PRUEBA13-05AGOS2025', 'prueba11@tlalpan.cdmx.gob.mx'),
(23, '2025-08-07 12:53:33', '2025-08-07', NULL, 'OF-00143', 'julia martinez', 'Desarrollo de sistemas', 'J.U.D. de Registro Contable', '987654321', '785', 'hthfdfdsfdsa', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754592813_8d72b1e5.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, 20, 'L.C.P. de Gestión Documental', '', 'PRUEBA1-07AGOS2025', 'prueba10@tlalpan.cdmx.gob.mx'),
(24, '2025-08-07 13:07:52', '2025-08-07', NULL, 'OF-00144', 'julia martinez', 'SECRET', 'Subdirección de Tecnologías de la Información y Comunicaciones', '987456321', '856', 'SE HACEN PRUEBAS EXITOSAMENTE EN TODOS LADOS\r\nQUEDA LISTO EL SISTEMA.', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1754593672_2a7a5a25.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, 20, 'J.U.D. de Soporte Técnico', '', 'PRUEBA2-07AGOS2025', 'prueba10@tlalpan.cdmx.gob.mx'),
(25, '2025-08-15 10:33:01', '2025-08-15', NULL, 'OF-00145', 'poiujhytre', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '5548857307', '2156', 'se realizan pruebas ya que dejo de guardar las capturas de datos', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1755275580_ec03af67.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, 26, 'J.U.D. de Soporte Técnico', '', 'PRUEBA01-15AGOS2025', 'prueba111@tlalpan.cdmx.gob.mx'),
(26, '2025-08-28 11:35:37', '2025-08-28', NULL, 'OF-00146', 'Alexis Sant', 'Desarrollo de sistemas', 'J.U.D. de Desarrollo de Sistemas', '55489656', '262', 'se realizan pruebas ya que no guardan nuevamente los datos en la compu de jesus', 'OFICIO', 'SEGUIMIENTO', 'C:/xampp/htdocs/SISTEMA_OFICIOS/pdfs/doc_1756402537_be42a9aa.pdf', 'RECIBIDO', NULL, NULL, NULL, NULL, NULL, 'av moneda s/numero', 1, 27, 'Dirección de Modernización Administrativa y Tecnologías de la Información y Comunicaciones', '', 'prueba28agost', 'asantiago@tlalpan.cdmx.gob.mx');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial`
--

DROP TABLE IF EXISTS `historial`;
CREATE TABLE `historial` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `detalles` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `historial`:
--   `documento_id`
--       `documentos` -> `id`
--   `usuario_id`
--       `usuarios` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_departamentos`
--

DROP TABLE IF EXISTS `historial_departamentos`;
CREATE TABLE `historial_departamentos` (
  `id` int(11) NOT NULL,
  `personal_id` int(11) NOT NULL,
  `numero_empleado` varchar(20) NOT NULL,
  `departamento_anterior` varchar(100) DEFAULT NULL,
  `departamento_nuevo` varchar(100) DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_registra` int(11) NOT NULL,
  `documento_id` int(11) DEFAULT NULL,
  `numero_oficio_usuario` varchar(50) DEFAULT NULL,
  `email_institucional` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `historial_departamentos`:
--   `numero_empleado`
--       `catalogo_personal` -> `numero_empleado`
--   `documento_id`
--       `documentos` -> `id`
--   `personal_id`
--       `catalogo_personal` -> `id`
--

--
-- Volcado de datos para la tabla `historial_departamentos`
--

INSERT INTO `historial_departamentos` (`id`, `personal_id`, `numero_empleado`, `departamento_anterior`, `departamento_nuevo`, `fecha_cambio`, `usuario_registra`, `documento_id`, `numero_oficio_usuario`, `email_institucional`) VALUES
(6, 20, 'EMP-00001', NULL, 'J.U.D. de Desarrollo de Sistemas', '2025-08-05 21:18:16', 1, 16, 'PRUEBA10-05AGOS2025', 'prueba10@tlalpan.cdmx.gob.mx'),
(7, 25, 'EMP-00002', NULL, 'J.U.D. de Servicios Generales y Apoyo Logístico', '2025-08-05 21:24:41', 1, 17, 'PRUEBA13-05AGOS2025', 'prueba11@tlalpan.cdmx.gob.mx'),
(13, 20, 'EMP-00001', 'J.U.D. de Registro Contable', 'J.U.D. de Registro Contable', '2025-08-07 18:53:33', 1, 23, 'PRUEBA1-07AGOS2025', 'prueba10@tlalpan.cdmx.gob.mx'),
(14, 20, 'EMP-00001', 'Subdirección de Tecnologías de la Información y Comunicaciones', 'Subdirección de Tecnologías de la Información y Comunicaciones', '2025-08-07 19:07:52', 1, 24, 'PRUEBA2-07AGOS2025', 'prueba10@tlalpan.cdmx.gob.mx'),
(15, 26, 'EMP-00003', 'J.U.D. de Desarrollo de Sistemas', 'J.U.D. de Desarrollo de Sistemas', '2025-08-15 16:33:01', 1, 25, 'PRUEBA01-15AGOS2025', 'prueba111@tlalpan.cdmx.gob.mx'),
(16, 27, 'EMP-00004', 'J.U.D. de Desarrollo de Sistemas', 'J.U.D. de Desarrollo de Sistemas', '2025-08-28 17:35:37', 1, 26, 'prueba28agost', 'asantiago@tlalpan.cdmx.gob.mx');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jud_departamentos`
--

DROP TABLE IF EXISTS `jud_departamentos`;
CREATE TABLE `jud_departamentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `jud_departamentos`:
--

--
-- Volcado de datos para la tabla `jud_departamentos`
--

INSERT INTO `jud_departamentos` (`id`, `nombre`, `responsable`, `activo`) VALUES
(1, 'DIRECCIÓN GENERAL DE ADMINISTRACIÓN Y FINANZAS', NULL, 1),
(2, 'Dirección de Administración de Capital Humano', NULL, 1),
(3, 'Subdirección de Nóminas y Registro de Personal', NULL, 1),
(4, 'J.U.D. de Registro y Movimientos de Personal', NULL, 1),
(5, 'Subdirección de Relaciones Laborales y Capacitación', NULL, 1),
(6, 'J.U.D. de Capacitación y Desarrollo de Personal', NULL, 1),
(7, 'J.U.D. de Relaciones Laborales y Prestaciones', NULL, 1),
(8, 'Dirección de Autogenerados', NULL, 1),
(9, 'L.C.P. de Seguimiento e Informes', NULL, 1),
(10, 'Dirección de Finanzas', NULL, 1),
(11, 'Subdirección de Contabilidad', NULL, 1),
(12, 'J.U.D. de Registro Contable', NULL, 1),
(13, 'Subdirección de Programación y Presupuesto', NULL, 1),
(14, 'J.U.D. de Control Presupuestal', NULL, 1),
(15, 'Subdirección de Tesorería', NULL, 1),
(16, 'Dirección de Modernización Administrativa y Tecnologías de la Información y Comunicaciones', NULL, 1),
(17, 'Subdirección de Tecnologías de la Información y Comunicaciones', NULL, 1),
(18, 'J.U.D. de Modernización Administrativa', NULL, 1),
(19, 'J.U.D. de Desarrollo de Sistemas', NULL, 1),
(20, 'J.U.D. de Soporte Técnico', NULL, 1),
(21, 'Subdirección de Cumplimiento de Auditorías y Rendición de Cuentas', NULL, 1),
(22, 'Subdirección de Seguimiento de Proyectos Administrativos y Control de Gestión', NULL, 1),
(23, 'L.C.P. de Gestión Documental', NULL, 1),
(24, 'Dirección de Recursos Materiales y Servicios Generales', NULL, 1),
(25, 'J.U.D. de Almacenes e Inventarios', NULL, 1),
(26, 'Subdirección de Adquisiciones', NULL, 1),
(27, 'Subdirección de Servicios Generales', NULL, 1),
(28, 'J.U.D. de Control Vehicular, Talleres y Combustible', NULL, 1),
(29, 'J.U.D. de Servicios Generales y Apoyo Logístico', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sistema`
--

DROP TABLE IF EXISTS `logs_sistema`;
CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `detalles` text DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `logs_sistema`:
--   `usuario_id`
--       `usuarios` -> `id`
--

--
-- Volcado de datos para la tabla `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `usuario_id`, `accion`, `detalles`, `ip`, `fecha`) VALUES
(1, 1, 'DOCUMENTO_CREADO', 'Oficio 30', '::1', '2025-06-13 19:56:28'),
(2, 1, 'DOCUMENTO_CREADO', 'Oficio 31', '::1', '2025-06-13 20:36:10'),
(3, 1, 'DOCUMENTO_CREADO', 'Oficio 32', '::1', '2025-06-13 20:40:01'),
(4, 1, 'DOCUMENTO_CREADO', 'Oficio 32', '::1', '2025-06-13 20:42:25'),
(5, 1, 'DOCUMENTO_CREADO', 'Oficio 33', '::1', '2025-06-25 18:06:50'),
(6, 1, 'DOCUMENTO_CREADO', 'Oficio 34', '::1', '2025-06-25 18:59:44'),
(7, 1, 'DOCUMENTO_CREADO', 'Oficio 35', '::1', '2025-06-25 20:10:28'),
(8, 1, 'DOCUMENTO_CREADO', 'Oficio 36', '::1', '2025-06-25 20:27:40'),
(9, 1, 'DOCUMENTO_CREADO', 'Oficio 37', '::1', '2025-06-26 18:28:41'),
(10, 1, 'DOCUMENTO_CREADO', 'Oficio 37', '::1', '2025-06-26 18:31:52'),
(11, 1, 'DOCUMENTO_CREADO', 'Oficio 38', '::1', '2025-06-26 18:34:00'),
(12, 1, 'DOCUMENTO_CREADO', 'Oficio 39', '::1', '2025-06-26 18:35:53'),
(13, 1, 'DOCUMENTO_CREADO', 'Oficio 40', '::1', '2025-06-26 18:37:38'),
(14, 1, 'DOCUMENTO_CREADO', 'Oficio 41', '::1', '2025-06-26 18:38:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos`
--

DROP TABLE IF EXISTS `modulos`;
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `modulos`:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `usuario_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `nivel_acceso` enum('LECTURA','ESCRITURA','TOTAL') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `permisos`:
--   `usuario_id`
--       `usuarios` -> `id`
--   `modulo_id`
--       `modulos` -> `id`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secuencia_oficios`
--

DROP TABLE IF EXISTS `secuencia_oficios`;
CREATE TABLE `secuencia_oficios` (
  `id` int(11) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `secuencia_oficios`:
--

--
-- Volcado de datos para la tabla `secuencia_oficios`
--

INSERT INTO `secuencia_oficios` (`id`, `ultimo_numero`) VALUES
(1, 146),
(2, 146),
(3, 146);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nombre_completo` varchar(100) DEFAULT NULL,
  `rol` enum('SISTEMAS','ADMIN','CAPTURISTA') DEFAULT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELACIONES PARA LA TABLA `usuarios`:
--

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `rol`, `departamento`, `activo`, `fecha_creacion`, `ultimo_acceso`) VALUES
(1, 'admin', '$2y$10$WBCaSgMZraXILd65Bnym..2P3S2QZTWmXApQjkSGYhG4ks6Ya90Oi', 'Administrador Principal', 'SISTEMAS', NULL, 1, '2025-05-29 17:15:33', NULL),
(2, 'judds', '$2y$10$2W7JFfRstQhcswqYgJHXyuZchIELRpGoTBETXzbg1lG2hL5/yV8UO', 'Administrador JUDDS', 'ADMIN', NULL, 1, '2025-05-29 17:15:33', NULL),
(3, 'capturista1', '$2y$10$OrPW2kExuEpctZUKO74X9eW3w/VfVuKxJZ7yLmsvqXLueZ93ubwBK', 'Capturista Principal', 'CAPTURISTA', NULL, 1, '2025-05-29 17:15:33', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria_oficios`
--
ALTER TABLE `auditoria_oficios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `catalogo_personal`
--
ALTER TABLE `catalogo_personal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_empleado` (`numero_empleado`),
  ADD UNIQUE KEY `uk_numero_empleado` (`numero_empleado`),
  ADD UNIQUE KEY `email_institucional` (`email_institucional`),
  ADD UNIQUE KEY `idx_email_unique` (`email_institucional`),
  ADD UNIQUE KEY `uk_email` (`email_institucional`),
  ADD KEY `unique_nombre_puesto` (`nombre`,`puesto`) USING BTREE,
  ADD KEY `idx_cat_email` (`email_institucional`),
  ADD KEY `idx_cat_numemp` (`numero_empleado`),
  ADD KEY `idx_email` (`email_institucional`),
  ADD KEY `idx_catalogo_email` (`email_institucional`),
  ADD KEY `idx_catalogo_numemp` (`numero_empleado`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_institucional` (`email_institucional`),
  ADD KEY `idx_doc_numemp` (`numero_empleado`),
  ADD KEY `idx_doc_email` (`email_institucional`),
  ADD KEY `idx_documentos_email` (`email_institucional`),
  ADD KEY `idx_documentos_personal` (`personal_id`),
  ADD KEY `idx_documentos_numero` (`numero_oficio`);

--
-- Indices de la tabla `historial`
--
ALTER TABLE `historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documento_id` (`documento_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `historial_departamentos`
--
ALTER TABLE `historial_departamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_numero_empleado` (`numero_empleado`),
  ADD KEY `idx_email_institucional` (`email_institucional`),
  ADD KEY `idx_hist_email` (`email_institucional`),
  ADD KEY `idx_historial_personal` (`personal_id`),
  ADD KEY `idx_historial_documento` (`documento_id`),
  ADD KEY `idx_historial_email` (`email_institucional`);

--
-- Indices de la tabla `jud_departamentos`
--
ALTER TABLE `jud_departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`usuario_id`,`modulo_id`),
  ADD KEY `modulo_id` (`modulo_id`);

--
-- Indices de la tabla `secuencia_oficios`
--
ALTER TABLE `secuencia_oficios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria_oficios`
--
ALTER TABLE `auditoria_oficios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `catalogo_personal`
--
ALTER TABLE `catalogo_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `historial`
--
ALTER TABLE `historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_departamentos`
--
ALTER TABLE `historial_departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `jud_departamentos`
--
ALTER TABLE `jud_departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `secuencia_oficios`
--
ALTER TABLE `secuencia_oficios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `auditoria_oficios`
--
ALTER TABLE `auditoria_oficios`
  ADD CONSTRAINT `auditoria_oficios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `fk_documentos_personal` FOREIGN KEY (`personal_id`) REFERENCES `catalogo_personal` (`id`);

--
-- Filtros para la tabla `historial`
--
ALTER TABLE `historial`
  ADD CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`),
  ADD CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `historial_departamentos`
--
ALTER TABLE `historial_departamentos`
  ADD CONSTRAINT `fk_empleado_historial` FOREIGN KEY (`numero_empleado`) REFERENCES `catalogo_personal` (`numero_empleado`),
  ADD CONSTRAINT `fk_histdep_documentos` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`),
  ADD CONSTRAINT `fk_histdep_personal` FOREIGN KEY (`personal_id`) REFERENCES `catalogo_personal` (`id`);

--
-- Filtros para la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `permisos_ibfk_2` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
