-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-08-2026 a las 19:37:10
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
-- Base de datos: `productos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_maestras`
--

CREATE TABLE `categorias_maestras` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL,
  `unidad_predeterminada` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_maestras`
--

INSERT INTO `categorias_maestras` (`id_categoria`, `nombre_categoria`, `unidad_predeterminada`) VALUES
(6, 'Granos/Cereales', 'KG'),
(9, 'Proteínas Animales', 'KG'),
(11, 'Lácteos', 'LT'),
(12, 'Grasas/Aceites', 'LT'),
(13, 'Verduras', 'KG');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_cocina`
--

CREATE TABLE `historial_cocina` (
  `id_historial` int(11) NOT NULL,
  `id_receta` int(11) DEFAULT NULL,
  `fecha_elaboracion` date DEFAULT NULL,
  `hora_elaboracion` time DEFAULT NULL,
  `cantidad_personas` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_cocina`
--

INSERT INTO `historial_cocina` (`id_historial`, `id_receta`, `fecha_elaboracion`, `hora_elaboracion`, `cantidad_personas`) VALUES
(37, 11, '2026-06-17', '05:32:46', 1),
(47, 17, '2026-07-22', '12:54:25', 600);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_movimientos`
--

CREATE TABLE `historial_movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `tipo_movimiento` enum('INGRESO','EGRESO_RECETA') DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_movimientos`
--

INSERT INTO `historial_movimientos` (`id_movimiento`, `id_producto`, `tipo_movimiento`, `cantidad`, `usuario`, `fecha`) VALUES
(1, 24, 'INGRESO', 0.01, 'arnold', '2026-06-17 09:23:44'),
(2, 24, 'INGRESO', 0.01, 'arnold', '2026-06-17 09:27:27'),
(4, 24, 'EGRESO_RECETA', -50.00, 'arnold', '2026-06-17 09:35:51'),
(6, 24, 'INGRESO', 10.00, 'arnold', '2026-06-17 09:56:36'),
(8, 50, 'INGRESO', 50.00, 'arnold', '2026-06-22 18:31:02'),
(9, 54, 'INGRESO', 1.00, 'arnold', '2026-06-22 18:39:22'),
(10, 9, 'INGRESO', 30.00, 'arnold', '2026-06-22 18:40:02'),
(11, 52, 'INGRESO', 30.00, 'arnold', '2026-06-22 18:40:18'),
(12, 24, 'EGRESO_RECETA', -0.90, 'arnold', '2026-06-22 18:49:44'),
(14, 52, 'INGRESO', 1.00, 'arnold', '2026-06-24 18:44:48'),
(18, 42, 'INGRESO', 300.00, 'arnold', '2026-07-04 14:57:19'),
(19, 42, 'INGRESO', 300.00, 'arnold', '2026-07-04 14:58:29'),
(21, 42, 'INGRESO', 200.00, 'arnold', '2026-07-04 15:04:27'),
(22, 42, 'INGRESO', 100.00, 'arnold', '2026-07-04 15:05:55'),
(23, 42, 'INGRESO', 100.00, 'arnold', '2026-07-04 15:21:16'),
(24, 9, 'INGRESO', 30.00, 'arnold', '2026-07-10 10:59:31'),
(25, 20, 'INGRESO', 10.00, 'arnold', '2026-07-10 11:00:25'),
(26, 59, 'INGRESO', 50.00, 'arnold', '2026-07-14 05:10:18'),
(27, 9, 'INGRESO', 30.00, 'arnold', '2026-07-14 05:10:18'),
(28, 20, 'INGRESO', 30.00, 'arnold', '2026-07-14 05:10:50'),
(29, 60, 'INGRESO', 50.00, 'arnold', '2026-07-14 05:12:04'),
(30, 59, 'EGRESO_RECETA', -1.00, 'arnold', '2026-07-15 03:53:31'),
(31, 20, 'EGRESO_RECETA', -1.00, 'arnold', '2026-07-15 03:53:31'),
(32, 9, 'EGRESO_RECETA', -1.00, 'arnold', '2026-07-15 03:53:31'),
(33, 60, 'EGRESO_RECETA', -1.00, 'arnold', '2026-07-15 03:53:31'),
(34, 59, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:29:41'),
(35, 20, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:29:41'),
(36, 9, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:29:41'),
(37, 60, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:29:41'),
(38, 9, 'INGRESO', 100.00, 'arnold', '2026-07-15 04:36:33'),
(39, 20, 'INGRESO', 100.00, 'arnold', '2026-07-15 04:36:33'),
(40, 59, 'INGRESO', 100.00, 'arnold', '2026-07-15 04:36:33'),
(41, 60, 'INGRESO', 100.00, 'arnold', '2026-07-15 04:36:33'),
(42, 59, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:41:48'),
(43, 20, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:41:48'),
(44, 9, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:41:48'),
(45, 60, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:41:48'),
(46, 59, 'EGRESO_RECETA', -22.00, 'arnold', '2026-07-15 04:49:28'),
(47, 20, 'EGRESO_RECETA', -22.00, 'arnold', '2026-07-15 04:49:28'),
(48, 9, 'EGRESO_RECETA', -22.00, 'arnold', '2026-07-15 04:49:28'),
(49, 60, 'EGRESO_RECETA', -22.00, 'arnold', '2026-07-15 04:49:28'),
(50, 59, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:50:17'),
(51, 20, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:50:17'),
(52, 9, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:50:17'),
(53, 60, 'EGRESO_RECETA', -20.00, 'arnold', '2026-07-15 04:50:17'),
(54, 52, 'INGRESO', 20.00, 'evacortez', '2026-07-15 13:40:00'),
(55, 59, 'EGRESO_RECETA', -20.00, 'evacortez', '2026-07-15 13:48:45'),
(56, 20, 'EGRESO_RECETA', -20.00, 'evacortez', '2026-07-15 13:48:45'),
(57, 9, 'EGRESO_RECETA', -20.00, 'evacortez', '2026-07-15 13:48:45'),
(58, 60, 'EGRESO_RECETA', -20.00, 'evacortez', '2026-07-15 13:48:45'),
(59, 59, 'EGRESO_RECETA', -21.00, 'evacortez', '2026-07-15 13:49:31'),
(60, 20, 'EGRESO_RECETA', -21.00, 'evacortez', '2026-07-15 13:49:31'),
(61, 9, 'EGRESO_RECETA', -21.00, 'evacortez', '2026-07-15 13:49:31'),
(62, 60, 'EGRESO_RECETA', -21.00, 'evacortez', '2026-07-15 13:49:31'),
(63, 9, 'INGRESO', 10.00, 'arnold', '2026-07-17 14:53:12'),
(64, 61, 'INGRESO', 100.00, 'ectico', '2026-07-17 17:27:30'),
(65, 62, 'INGRESO', 30.00, 'duglasiuu', '2026-07-18 00:05:46'),
(66, 24, 'INGRESO', 20.00, 'arnold', '2026-07-22 06:54:02'),
(67, 9, 'INGRESO', 200.00, 'arnold', '2026-07-22 16:51:29'),
(68, 9, 'EGRESO_RECETA', -30.00, 'arnold', '2026-07-22 16:54:25'),
(69, 61, 'EGRESO_RECETA', -30.00, 'arnold', '2026-07-22 16:54:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre`, `descripcion`, `id_categoria`, `creado_en`, `estado`) VALUES
(9, 'arroz', NULL, 6, '2026-05-07 00:26:07', 1),
(20, 'aceite', NULL, 12, '2026-05-07 11:42:10', 1),
(24, 'okumo', NULL, 13, '2026-05-12 02:14:32', 1),
(52, 'caraotas', NULL, 6, '2026-06-22 18:38:14', 1),
(59, 'pollo', NULL, 9, '2026-07-14 05:09:50', 1),
(60, 'harina de maiz', NULL, 6, '2026-07-14 05:11:50', 1),
(61, 'leche', NULL, 11, '2026-07-17 17:26:44', 1),
(62, 'nutrichicha', NULL, 11, '2026-07-18 00:05:08', 1),
(64, 'auyama', NULL, 13, '2026-07-22 06:51:42', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas_comida`
--

CREATE TABLE `recetas_comida` (
  `id_receta` int(11) NOT NULL,
  `nombre_plato` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recetas_comida`
--

INSERT INTO `recetas_comida` (`id_receta`, `nombre_plato`) VALUES
(11, 'arroz con pollo'),
(16, 'Arroz con pollo y arepa frita'),
(17, 'Chicha de arroz');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta_detalles`
--

CREATE TABLE `receta_detalles` (
  `id_detalle` int(11) NOT NULL,
  `id_receta` int(11) DEFAULT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `cantidad_necesaria` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `receta_detalles`
--

INSERT INTO `receta_detalles` (`id_detalle`, `id_receta`, `id_producto`, `cantidad_necesaria`) VALUES
(30, 11, 9, 2.00),
(31, 11, 59, 2.00),
(32, 16, 59, 16.00),
(33, 16, 9, 8.00),
(34, 16, 60, 4.00),
(35, 16, 20, 1.00),
(36, 17, 9, 4.00),
(37, 17, 61, 4.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock`
--

CREATE TABLE `stock` (
  `id_stock` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad_actual` decimal(10,2) DEFAULT 0.00,
  `stock_minimo` decimal(10,2) DEFAULT 1.00,
  `fecha_caducidad` date DEFAULT NULL,
  `ubicacion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `stock`
--

INSERT INTO `stock` (`id_stock`, `id_producto`, `cantidad_actual`, `stock_minimo`, `fecha_caducidad`, `ubicacion`) VALUES
(43, 9, 190.00, 5.00, '2026-08-01', NULL),
(44, 20, 5.00, 6.00, '2026-08-21', NULL),
(45, 59, 26.00, 10.00, '2026-08-28', NULL),
(46, 60, 26.00, 1.00, '2026-08-26', NULL),
(47, 52, 20.00, 1.00, '2026-07-15', NULL),
(48, 61, 70.00, 1.00, '2027-10-25', NULL),
(52, 24, 20.00, 1.00, '2026-08-08', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias_maestras`
--
ALTER TABLE `categorias_maestras`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`);

--
-- Indices de la tabla `historial_cocina`
--
ALTER TABLE `historial_cocina`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `id_receta` (`id_receta`);

--
-- Indices de la tabla `historial_movimientos`
--
ALTER TABLE `historial_movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `fk_historial_movimientos_productos` (`id_producto`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `fk_productos_categorias` (`id_categoria`);

--
-- Indices de la tabla `recetas_comida`
--
ALTER TABLE `recetas_comida`
  ADD PRIMARY KEY (`id_receta`);

--
-- Indices de la tabla `receta_detalles`
--
ALTER TABLE `receta_detalles`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_receta` (`id_receta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id_stock`),
  ADD KEY `id_producto` (`id_producto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias_maestras`
--
ALTER TABLE `categorias_maestras`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `historial_cocina`
--
ALTER TABLE `historial_cocina`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `historial_movimientos`
--
ALTER TABLE `historial_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de la tabla `recetas_comida`
--
ALTER TABLE `recetas_comida`
  MODIFY `id_receta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `receta_detalles`
--
ALTER TABLE `receta_detalles`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `stock`
--
ALTER TABLE `stock`
  MODIFY `id_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_cocina`
--
ALTER TABLE `historial_cocina`
  ADD CONSTRAINT `historial_cocina_ibfk_1` FOREIGN KEY (`id_receta`) REFERENCES `recetas_comida` (`id_receta`);

--
-- Filtros para la tabla `historial_movimientos`
--
ALTER TABLE `historial_movimientos`
  ADD CONSTRAINT `fk_historial_movimientos_productos` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categorias` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_maestras` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `receta_detalles`
--
ALTER TABLE `receta_detalles`
  ADD CONSTRAINT `receta_detalles_ibfk_1` FOREIGN KEY (`id_receta`) REFERENCES `recetas_comida` (`id_receta`),
  ADD CONSTRAINT `receta_detalles_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
