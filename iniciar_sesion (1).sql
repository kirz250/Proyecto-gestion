-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-08-2026 a las 19:37:30
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
-- Base de datos: `iniciar sesion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_catalogo`
--

CREATE TABLE `preguntas_catalogo` (
  `id_pregunta_cat` int(11) NOT NULL,
  `pregunta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preguntas_catalogo`
--

INSERT INTO `preguntas_catalogo` (`id_pregunta_cat`, `pregunta`) VALUES
(1, '¿Cuál es tu color favorito?'),
(2, '¿Cuál es tu película favorita?'),
(3, '¿Cómo se llama tu mascota?');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `Id` int(11) NOT NULL,
  `Usuario` varchar(70) NOT NULL,
  `Clave` varchar(255) NOT NULL,
  `correo` varchar(30) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 0,
  `rol` varchar(20) NOT NULL DEFAULT 'usuario' COMMENT 'usuario o admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`Id`, `Usuario`, `Clave`, `correo`, `cedula`, `estado`, `rol`) VALUES
(1, 'arnold', 'bigmax335', '', '', 1, 'admin'),
(8, 'anders', 'Anders2006', 'andersscaranoscarano@gmail.com', '', 0, 'usuario'),
(27, 'elias', 'hola123', 'eliasslow@gmail.com', '33245222', 1, 'usuario'),
(28, 'evacortez', '123456', 'eva@gmail.com', '15740652', 1, 'usuario'),
(29, 'ectico', 'h.12345', 'hectico@gmail.com', '34263598', 0, 'usuario'),
(30, 'duglasiuu', 'harry23', 'douglsriver095@gmail.com', '30989306', 0, 'usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_respuestas`
--

CREATE TABLE `usuario_respuestas` (
  `id_respuesta` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_pregunta_cat` int(11) NOT NULL,
  `respuesta` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_respuestas`
--

INSERT INTO `usuario_respuestas` (`id_respuesta`, `id_usuario`, `id_pregunta_cat`, `respuesta`) VALUES
(1, 27, 1, 'verde'),
(2, 27, 2, 'wonka'),
(3, 27, 3, 'panda'),
(4, 28, 1, 'azul'),
(5, 28, 2, 'michael'),
(6, 28, 3, 'gati'),
(7, 29, 1, 'azul'),
(8, 29, 2, 'spidderman'),
(9, 29, 3, 'firulaiz'),
(10, 30, 1, 'azul'),
(11, 30, 2, 'shrek 2'),
(12, 30, 3, 'lana');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `preguntas_catalogo`
--
ALTER TABLE `preguntas_catalogo`
  ADD PRIMARY KEY (`id_pregunta_cat`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`Id`);

--
-- Indices de la tabla `usuario_respuestas`
--
ALTER TABLE `usuario_respuestas`
  ADD PRIMARY KEY (`id_respuesta`),
  ADD KEY `fk_respuestas_usuarios` (`id_usuario`),
  ADD KEY `fk_respuestas_catalogo` (`id_pregunta_cat`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `preguntas_catalogo`
--
ALTER TABLE `preguntas_catalogo`
  MODIFY `id_pregunta_cat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `usuario_respuestas`
--
ALTER TABLE `usuario_respuestas`
  MODIFY `id_respuesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `usuario_respuestas`
--
ALTER TABLE `usuario_respuestas`
  ADD CONSTRAINT `fk_respuestas_catalogo` FOREIGN KEY (`id_pregunta_cat`) REFERENCES `preguntas_catalogo` (`id_pregunta_cat`),
  ADD CONSTRAINT `fk_respuestas_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`Id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
