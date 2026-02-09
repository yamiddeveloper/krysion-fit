-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-12-2025 a las 20:02:50
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
-- Base de datos: `sistema_krysion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_personalizados`
--

CREATE TABLE `planes_personalizados` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `goal` varchar(50) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `hip` decimal(5,2) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `activity_level` varchar(50) DEFAULT NULL,
  `injuries` text DEFAULT NULL,
  `equipment` varchar(50) DEFAULT NULL,
  `nutrition_pref` varchar(50) DEFAULT NULL,
  `daily_msg` varchar(5) DEFAULT NULL,
  `calendar_pref` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes_personalizados`
--

INSERT INTO `planes_personalizados` (`id`, `full_name`, `email`, `phone`, `age`, `sex`, `goal`, `weight`, `height`, `waist`, `hip`, `photo_path`, `activity_level`, `injuries`, `equipment`, `nutrition_pref`, `daily_msg`, `calendar_pref`, `created_at`, `password_hash`) VALUES
(1, 'pablo', 'pablo@correo.com', '30303030', 27, 'male', 'gain_muscle', 70.00, 167.00, 60.00, 90.00, 'uploads/694838da7d02c_914232.png', 'moderate', 'ninguna', 'gym', 'cook', 'yes', '', '2025-12-21 18:13:46', NULL),
(2, 'pablo', 'pablo@correo.com', '30303030', 27, 'male', 'gain_muscle', 70.00, 167.00, 60.00, 90.00, 'uploads/69483d2d8b513_914232.png', 'moderate', 'ninguna', 'gym', 'cook', 'yes', '', '2025-12-21 18:32:13', '$2y$10$kX/qjgiyENhoWgczlILgz.U7UEYxwewBBfc4mcpnTsQHxyJ20ua5e'),
(3, 'pedro', 'pedro@correo.com', '90909090', 27, 'male', 'gain_muscle', 70.00, 175.00, NULL, NULL, 'uploads/69483f0a806fd_fondoninntendo.jpg', 'moderate', 'ninguna', 'home', 'cook', 'yes', '', '2025-12-21 18:40:10', '$2y$10$CpCUvzJN.twuQc1uD5AlzO7tnHGKr7do7.WBnIpOgW2ubEUqLu7dm'),
(4, 'JUAN', 'juan@correo.com', '7070707070', 27, 'male', 'gain_muscle', 70.00, 167.00, NULL, NULL, 'uploads/694843f4b9159_usuarios2.png', 'moderate', 'ninguno', 'home', 'cook', 'yes', '', '2025-12-21 19:01:08', '$2y$10$v3v.cx0KZdHg72M65v5Useom17qojHRhSYvokM98Dv6NPKVvW1U9e');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `planes_personalizados`
--
ALTER TABLE `planes_personalizados`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `planes_personalizados`
--
ALTER TABLE `planes_personalizados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
