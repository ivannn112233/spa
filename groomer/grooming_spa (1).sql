-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-05-2026 a las 01:39:59
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `grooming_spa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `accion` varchar(120) NOT NULL,
  `detalle` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audit_log`
--

INSERT INTO `audit_log` (`id`, `usuario_id`, `rol`, `accion`, `detalle`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'admin', 'CREACION_CITA', 'Creó cita para Firulais con groomer Ana', '192.168.1.1', 'Mozilla/5.0', '2026-05-07 15:11:42'),
(2, 2, 'recepcion', 'FACTURACION', 'Facturó servicio de baño completo', '192.168.1.2', 'Mozilla/5.0', '2026-05-07 15:11:42'),
(3, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:12:33'),
(4, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:16:14'),
(5, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: juanOmar795@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:17:00'),
(6, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: juanOmar795@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:24:01'),
(7, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: juanOmar795@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:29:56'),
(8, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:30:34'),
(9, 10, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:31:09'),
(10, 10, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:48:31'),
(11, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:49:39'),
(12, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: juanOmar795@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:50:16'),
(13, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:51:50'),
(14, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:55:57'),
(15, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: juanOmar795@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:56:27'),
(16, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:57:00'),
(17, 12, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:57:14'),
(18, 12, 'groomer', 'PASSWORD_CAMBIADA', 'Usuario cambió su contraseña', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:57:42'),
(19, 12, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:57:50'),
(20, 12, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 15:58:04'),
(21, 12, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 16:08:35'),
(22, 5, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 16:09:02'),
(23, 5, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 16:09:21'),
(24, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: cliente5@gamai.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 16:10:29'),
(25, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: juanOmar795@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 16:52:58'),
(26, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: juanOmar795@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 17:03:12'),
(27, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: juanOmar795@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:44:56'),
(28, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: omarlimachi473@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:20:10'),
(29, 17, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:22:11'),
(30, 17, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:23:52'),
(31, 2, 'recepcion', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:39:13'),
(32, 2, 'recepcion', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:39:39'),
(33, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:40:21'),
(34, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: omarlimachi437@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:46:21'),
(35, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:46:39'),
(36, 18, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:47:02'),
(37, 18, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:47:23'),
(38, 18, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:47:40'),
(39, 18, 'groomer', 'PASSWORD_CAMBIADA', 'Usuario cambió su contraseña', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:48:06'),
(40, 18, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:48:14'),
(41, 18, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:48:26'),
(42, 18, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:49:00'),
(43, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 19:49:12'),
(44, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: baadads@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 20:00:00'),
(45, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 20:01:41'),
(46, 18, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 20:02:59'),
(47, 18, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 20:03:14'),
(48, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 02:46:51'),
(49, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 02:47:57'),
(50, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: omarlimachi473@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 02:49:53'),
(51, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: omarlimachi473@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:01:21'),
(52, 21, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:02:18'),
(53, 21, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:02:50'),
(54, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:03:34'),
(55, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: omarlimachi437@gamil.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:05:45'),
(56, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:09:52'),
(57, 22, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:10:30'),
(58, 22, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:10:35'),
(59, 22, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:10:38'),
(60, 22, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 03:10:42'),
(61, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: omarlimachi473@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:42:19'),
(62, 23, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:43:27'),
(63, 23, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:43:54'),
(64, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:44:31'),
(65, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: omarlimachi437@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:47:46'),
(66, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:48:40'),
(67, 24, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:49:00'),
(68, 24, 'groomer', 'PASSWORD_CAMBIADA', 'Usuario cambió su contraseña', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:49:52'),
(69, 24, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:50:22'),
(70, NULL, NULL, 'LOGIN_FALLIDO', 'Usuario no existe: admin@groomingspa.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:50:34'),
(71, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:50:53'),
(72, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:51:11'),
(73, 24, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:51:32'),
(74, 24, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:51:37'),
(75, 24, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:51:41'),
(76, 24, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:51:44'),
(77, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: omarlimachi473@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:56:39'),
(78, 25, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:57:57'),
(79, 25, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:58:12'),
(80, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 01:58:32'),
(81, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: omarlimachi437@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:00:29'),
(82, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:02:16'),
(83, NULL, NULL, 'REGISTRO_CLIENTE', 'Nuevo cliente registrado: omarlimachi473@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:04:34'),
(84, 27, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:05:22'),
(85, 27, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:05:38'),
(86, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:06:05'),
(87, 1, 'admin', 'USUARIO_CREADO', 'Creó usuario: omarlimachi437@gmail.com (rol_id: 3)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:08:00'),
(88, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:08:31'),
(89, 28, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:08:52'),
(90, 28, 'groomer', 'PASSWORD_CAMBIADA', 'Usuario cambió su contraseña', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:09:48'),
(91, 28, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:10:01'),
(92, 28, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:10:19'),
(93, 28, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:10:24'),
(94, 28, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:10:29'),
(95, 28, 'groomer', 'LOGIN_FALLIDO', 'Contraseña incorrecta', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 02:10:33'),
(96, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 16:04:35'),
(97, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-12 16:14:09'),
(98, NULL, NULL, 'LOGIN_FALLIDO', 'Usuario no existe: dmin@petspa.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 03:58:49'),
(99, NULL, NULL, 'LOGIN_FALLIDO', 'Usuario no existe: dmin@petspa.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 03:59:01'),
(100, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-24 03:59:21'),
(101, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 00:49:07'),
(102, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 01:32:44'),
(103, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 01:37:20'),
(104, 3, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 01:38:19'),
(105, 3, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 02:25:22'),
(106, 5, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 02:26:08'),
(107, 5, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 02:36:38'),
(108, 2, 'recepcion', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 02:39:13'),
(109, 2, 'recepcion', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:31:54'),
(110, 7, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:32:42'),
(111, 7, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:40:10'),
(112, 2, 'recepcion', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:42:21'),
(113, 2, 'recepcion', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:44:17'),
(114, 5, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:45:29'),
(115, 5, 'cliente', 'CITA_SOLICITADA', 'Solicitó cita para mascota ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:47:51'),
(116, 5, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:48:08'),
(117, 3, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:48:32'),
(118, 3, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:49:51'),
(119, 2, 'recepcion', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:50:10'),
(120, 2, 'recepcion', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:53:09'),
(121, 5, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:53:29'),
(122, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 00:46:37'),
(123, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 00:52:48'),
(124, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 00:59:01'),
(125, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:25:34'),
(126, 5, 'cliente', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:28:50'),
(127, 5, 'cliente', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:29:39'),
(128, 4, 'groomer', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:29:50'),
(129, 4, 'groomer', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:29:56'),
(130, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:30:26'),
(131, 2, 'recepcion', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-28 01:31:03'),
(132, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 01:36:29'),
(133, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-28 01:36:58'),
(134, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 20:36:04'),
(135, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 20:43:43'),
(136, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 20:44:12'),
(137, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 20:44:25'),
(138, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 20:55:57'),
(139, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:03:24'),
(140, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:04:10'),
(141, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:04:26'),
(142, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:06:51'),
(143, 1, 'admin', 'IP_RESTRICCION_CONFIG', 'Configuró restricción IP: Activada IP: 192.168.100.11', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:09:29'),
(144, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:11:32'),
(145, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:11:47'),
(146, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:12:08'),
(147, 1, 'admin', 'ACCESO_DENEGADO_IP', 'Intento de acceso desde IP no autorizada: ::1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:12:20'),
(148, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:27:05'),
(149, 1, 'admin', 'HOSTNAME_RESTRICCION_CONFIG', 'Configuró restricción por hostname: Activada Hostname: DESKTOP-NISO3AR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:31:25'),
(150, 1, 'admin', 'HOSTNAME_RESTRICCION_CONFIG', 'Configuró restricción por hostname: Activada Hostname: DESKTOP-NISO3AR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:31:38'),
(151, 1, 'admin', 'LOGOUT', 'Cierre de sesion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:32:13'),
(152, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:32:43'),
(153, 1, 'admin', 'HOSTNAME_RESTRICCION_CONFIG', 'Configuró restricción por hostname (hasheado)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:37:08'),
(154, 1, 'admin', 'LOGOUT', 'Cierre de sesion', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:37:38'),
(155, 1, 'admin', 'ACCESO_DENEGADO_HOSTNAME', 'Intento de acceso desde hostname no autorizado: DESKTOP-NISO3AR (Esperado: $2y$10$EO4k7iAA6kRoTNz9jVmjZOQVe3U/.TjM7zLGE86RfS3MLxCQgQoHC)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:38:05'),
(156, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:40:09'),
(157, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:40:14'),
(158, 1, 'admin', 'LOGIN_EXITOSO', 'Inicio de sesión exitoso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:40:27'),
(159, 1, 'admin', 'LOGOUT', 'Cierre de sesión', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-30 21:40:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloqueos_calendario`
--

CREATE TABLE `bloqueos_calendario` (
  `id` int(10) UNSIGNED NOT NULL,
  `groomer_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = bloqueo global',
  `tipo` enum('feriado','vacaciones','mantenimiento','ausencia') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carritos`
--

CREATE TABLE `carritos` (
  `id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED DEFAULT NULL,
  `session_token` varchar(100) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carritos`
--

INSERT INTO `carritos` (`id`, `cliente_id`, `session_token`, `expires_at`, `created_at`) VALUES
(1, 1, NULL, '2026-05-09 15:11:42', '2026-05-07 15:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_productos`
--

CREATE TABLE `categorias_productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `padre_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_productos`
--

INSERT INTO `categorias_productos` (`id`, `nombre`, `padre_id`) VALUES
(1, 'Alimentos', NULL),
(2, 'Shampoos', NULL),
(3, 'Accesorios', NULL),
(4, 'Juguetes', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checklist_items_plantilla`
--

CREATE TABLE `checklist_items_plantilla` (
  `id` int(10) UNSIGNED NOT NULL,
  `servicio_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `requiere_observacion` tinyint(1) NOT NULL DEFAULT 0,
  `orden` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `checklist_items_plantilla`
--

INSERT INTO `checklist_items_plantilla` (`id`, `servicio_id`, `nombre`, `requiere_observacion`, `orden`) VALUES
(1, NULL, 'Baño', 0, 1),
(2, NULL, 'Secado', 0, 2),
(3, NULL, 'Corte de pelo', 0, 3),
(4, NULL, 'Corte de uñas', 0, 4),
(5, NULL, 'Limpieza de oídos', 0, 5),
(6, NULL, 'Limpieza de glándulas', 0, 6),
(7, NULL, 'Perfume', 0, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(10) UNSIGNED NOT NULL,
  `mascota_id` int(10) UNSIGNED NOT NULL,
  `groomer_id` int(10) UNSIGNED NOT NULL,
  `servicio_id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime NOT NULL,
  `duracion_real_min` smallint(6) DEFAULT NULL,
  `estado` enum('agendada','confirmada','en_progreso','completada','cancelada','no_asistio') NOT NULL DEFAULT 'agendada',
  `precio_acordado` decimal(10,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `reprogramado_fecha` datetime DEFAULT NULL,
  `reprogramado_por` int(10) UNSIGNED DEFAULT NULL,
  `motivo_cancelacion` varchar(255) DEFAULT NULL,
  `sucursal_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `mascota_id`, `groomer_id`, `servicio_id`, `cliente_id`, `fecha_hora_inicio`, `fecha_hora_fin`, `duracion_real_min`, `estado`, `precio_acordado`, `notas`, `creado_por`, `reprogramado_fecha`, `reprogramado_por`, `motivo_cancelacion`, `sucursal_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 3, 1, '2026-05-07 10:00:00', '2026-05-07 12:00:00', NULL, 'completada', 180.00, 'Primera cita, mascota tranquila', 1, NULL, NULL, NULL, NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(2, 2, 1, 1, 1, '2026-05-07 15:00:00', '2026-05-07 16:00:00', NULL, 'en_progreso', 80.00, NULL, 2, NULL, NULL, NULL, NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(3, 3, 2, 2, 2, '2026-05-08 09:00:00', '2026-05-08 10:30:00', NULL, 'confirmada', 120.00, 'Gato agresivo, usar bozal', 5, NULL, NULL, NULL, NULL, '2026-05-07 15:11:41', '2026-05-25 03:52:41'),
(4, 4, 1, 3, 3, '2026-05-09 11:00:00', '2026-05-09 13:00:00', NULL, 'confirmada', 180.00, 'Piel sensible, usar shampoo hipoalergénico', 2, NULL, NULL, NULL, NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(5, 1, 2, 4, 1, '2026-05-10 14:00:00', '2026-05-10 14:15:00', NULL, 'cancelada', 30.00, 'Cancelado por enfermedad de la mascota', 5, NULL, NULL, NULL, NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(6, 3, 2, 1, 2, '2026-05-25 11:00:00', '2026-05-25 12:06:00', NULL, 'confirmada', NULL, 'nota...', 2, NULL, NULL, NULL, NULL, '2026-05-25 03:30:52', '2026-05-25 03:30:52'),
(7, 2, 1, 4, 1, '2026-05-25 10:00:00', '2026-05-25 10:15:00', NULL, 'confirmada', NULL, NULL, 5, NULL, NULL, NULL, NULL, '2026-05-25 03:47:51', '2026-05-25 03:50:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `apellido` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ci` varchar(20) DEFAULT NULL COMMENT 'Carnet de Identidad',
  `direccion` varchar(255) DEFAULT NULL,
  `canal_notificacion` enum('email','whatsapp','sms','telegram') NOT NULL DEFAULT 'email',
  `horario_preferido` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `usuario_id`, `nombre`, `apellido`, `telefono`, `ci`, `direccion`, `canal_notificacion`, `horario_preferido`, `created_at`, `updated_at`) VALUES
(1, 5, 'María', 'González', '69912345', '1234567', 'Calle 1 #123', 'whatsapp', 'Mañanas', '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(2, 6, 'Carlos', 'Pérez', '69954321', '7654321', 'Avenida 2 #456', 'email', 'Tardes', '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(3, 7, 'Laura', 'Fernández', '69987654', '1122334', 'Plaza 3 #789', 'telegram', 'Mañanas', '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(4, 13, 'ana', 'ana', '3564854', '1358885', 'diereccio', 'email', NULL, '2026-05-07 16:10:27', '2026-05-07 16:10:27'),
(13, 27, 'juan', 'juan', '2154684', '354684', 'dir..', 'email', NULL, '2026-05-12 02:04:26', '2026-05-12 02:04:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consumo_insumos`
--

CREATE TABLE `consumo_insumos` (
  `id` int(10) UNSIGNED NOT NULL,
  `groomer_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `cita_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `consumo_insumos`
--

INSERT INTO `consumo_insumos` (`id`, `groomer_id`, `producto_id`, `cantidad`, `cita_id`, `created_at`) VALUES
(1, 1, 1, 2.00, 1, '2026-05-07 14:30:00'),
(2, 1, 1, 1.50, 2, '2026-05-07 19:30:00'),
(3, 1, 3, 1.00, 1, '2026-05-07 14:30:00'),
(4, 1, 2, 1.00, 2, '2026-05-07 19:30:00'),
(5, 2, 1, 1.00, 3, '2026-05-08 13:30:00'),
(6, 2, 3, 1.00, 3, '2026-05-08 13:30:00'),
(7, 2, 4, 1.00, 5, '2026-05-10 18:15:00'),
(8, 1, 2, 2.00, NULL, '2026-05-25 01:47:21'),
(9, 2, 1, 1.50, NULL, '2026-05-25 01:47:21'),
(10, 1, 4, 5.00, NULL, '2026-05-23 01:47:21'),
(11, 2, 3, 2.00, NULL, '2026-05-24 01:47:21'),
(12, 1, 1, 3.00, NULL, '2026-05-20 01:47:21'),
(13, 1, 1, 2.00, NULL, '2026-05-15 01:47:21'),
(14, 1, 2, 1.00, NULL, '2026-05-10 01:47:21'),
(15, 2, 1, 2.00, NULL, '2026-05-17 01:47:21'),
(16, 2, 2, 1.00, NULL, '2026-05-13 01:47:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_carrito`
--

CREATE TABLE `detalle_carrito` (
  `id` int(10) UNSIGNED NOT NULL,
  `carrito_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `variante_id` int(10) UNSIGNED DEFAULT NULL,
  `cantidad` smallint(6) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL
) ;

--
-- Volcado de datos para la tabla `detalle_carrito`
--

INSERT INTO `detalle_carrito` (`id`, `carrito_id`, `producto_id`, `variante_id`, `cantidad`, `precio_unitario`) VALUES
(1, 1, 1, 2, 2, 35.00),
(2, 1, 3, NULL, 1, 15.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `variante_id` int(10) UNSIGNED DEFAULT NULL,
  `cantidad` smallint(6) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id`, `pedido_id`, `producto_id`, `variante_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 2, 2, 35.00, 70.00),
(2, 1, 3, NULL, 1, 15.00, 15.00),
(3, 2, 1, NULL, 1, 25.00, 25.00),
(4, 2, 4, NULL, 2, 12.00, 24.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidad_groomer`
--

CREATE TABLE `disponibilidad_groomer` (
  `id` int(10) UNSIGNED NOT NULL,
  `groomer_id` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(4) NOT NULL COMMENT '0=Dom,1=Lun,...,6=Sab',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `intervalo_descanso` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"inicio":"13:00","fin":"14:00"}' CHECK (json_valid(`intervalo_descanso`))
) ;

--
-- Volcado de datos para la tabla `disponibilidad_groomer`
--

INSERT INTO `disponibilidad_groomer` (`id`, `groomer_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `intervalo_descanso`) VALUES
(1, 1, 1, '09:00:00', '13:00:00', '{\"inicio\":\"13:00\",\"fin\":\"14:00\"}'),
(2, 1, 1, '14:00:00', '18:00:00', NULL),
(3, 1, 2, '09:00:00', '13:00:00', '{\"inicio\":\"13:00\",\"fin\":\"14:00\"}'),
(4, 1, 2, '14:00:00', '18:00:00', NULL),
(5, 1, 3, '09:00:00', '13:00:00', '{\"inicio\":\"13:00\",\"fin\":\"14:00\"}'),
(6, 1, 3, '14:00:00', '18:00:00', NULL),
(7, 1, 4, '09:00:00', '18:00:00', '{\"inicio\":\"13:00\",\"fin\":\"14:00\"}'),
(8, 1, 5, '09:00:00', '18:00:00', '{\"inicio\":\"13:00\",\"fin\":\"14:00\"}'),
(9, 2, 1, '08:00:00', '14:00:00', NULL),
(10, 2, 2, '08:00:00', '14:00:00', NULL),
(11, 2, 3, '08:00:00', '14:00:00', NULL),
(12, 2, 4, '08:00:00', '14:00:00', NULL),
(13, 2, 5, '08:00:00', '14:00:00', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas_satisfaccion`
--

CREATE TABLE `encuestas_satisfaccion` (
  `id` int(10) UNSIGNED NOT NULL,
  `cita_id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `calificacion` tinyint(4) NOT NULL COMMENT '1-5',
  `comentario` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `encuestas_satisfaccion`
--

INSERT INTO `encuestas_satisfaccion` (`id`, `cita_id`, `cliente_id`, `calificacion`, `comentario`, `created_at`) VALUES
(1, 1, 1, 5, 'Excelente servicio, mi perro quedó hermoso. Muy profesionales.', '2026-05-07 15:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL COMMENT 'Generado por trigger',
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `cita_id` int(10) UNSIGNED DEFAULT NULL,
  `pedido_id` int(10) UNSIGNED DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('efectivo','qr','transferencia') NOT NULL DEFAULT 'efectivo',
  `estado` enum('pendiente','pagada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_emision` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `numero`, `cliente_id`, `cita_id`, `pedido_id`, `subtotal`, `impuesto`, `total`, `metodo_pago`, `estado`, `fecha_emision`, `created_at`) VALUES
(1, 1, 1, 1, NULL, 180.00, 18.00, 198.00, 'qr', 'pagada', '2026-05-07 12:00:00', '2026-05-07 15:11:42'),
(2, 2, 1, NULL, 1, 85.00, 8.50, 93.50, 'transferencia', 'pagada', '2026-05-07 13:00:00', '2026-05-07 15:11:42');

--
-- Disparadores `facturas`
--
DELIMITER $$
CREATE TRIGGER `trg_factura_numero` BEFORE INSERT ON `facturas` FOR EACH ROW BEGIN
    DECLARE next_num INT;
    DECLARE current_year INT;
    SET current_year = YEAR(NEW.fecha_emision);
    SET next_num = COALESCE(
        (SELECT MAX(numero) + 1 FROM facturas WHERE YEAR(fecha_emision) = current_year),
        1
    );
    SET NEW.numero = next_num;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_total_factura_ins` BEFORE INSERT ON `facturas` FOR EACH ROW BEGIN
  SET NEW.total = NEW.subtotal + NEW.impuesto;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_total_factura_upd` BEFORE UPDATE ON `facturas` FOR EACH ROW BEGIN
  SET NEW.total = NEW.subtotal + NEW.impuesto;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fichas_grooming`
--

CREATE TABLE `fichas_grooming` (
  `id` int(10) UNSIGNED NOT NULL,
  `cita_id` int(10) UNSIGNED NOT NULL,
  `raza_al_momento` varchar(80) DEFAULT NULL,
  `peso_al_momento` decimal(5,2) DEFAULT NULL,
  `temperatura_ingreso` decimal(4,1) DEFAULT NULL,
  `estado_inicial` text DEFAULT NULL,
  `estado_final` text DEFAULT NULL,
  `notas_internas` text DEFAULT NULL,
  `inventario_consumido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_cierre` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fichas_grooming`
--

INSERT INTO `fichas_grooming` (`id`, `cita_id`, `raza_al_momento`, `peso_al_momento`, `temperatura_ingreso`, `estado_inicial`, `estado_final`, `notas_internas`, `inventario_consumido`, `fecha_cierre`, `created_at`) VALUES
(1, 1, 'Golden Retriever', 28.50, 38.2, 'Pelaje enredado leve', 'Muy limpio y peinado', 'Se portó bien', 1, '2026-05-07 12:30:00', '2026-05-07 15:11:41'),
(2, 2, 'Poodle', 5.20, 38.0, 'Nudos en orejas', NULL, NULL, 0, NULL, '2026-05-07 15:11:41');

--
-- Disparadores `fichas_grooming`
--
DELIMITER $$
CREATE TRIGGER `trg_descontar_inventario` AFTER UPDATE ON `fichas_grooming` FOR EACH ROW BEGIN
  DECLARE v_consumo JSON;
  DECLARE v_len INT;
  DECLARE i INT DEFAULT 0;
  DECLARE v_pid INT;
  DECLARE v_qty INT;

  IF NEW.inventario_consumido = 1 AND OLD.inventario_consumido = 0 THEN
    SELECT s.consumo_insumos INTO v_consumo
    FROM citas c
    JOIN servicios s ON s.id = c.servicio_id
    WHERE c.id = NEW.cita_id;

    IF v_consumo IS NOT NULL AND JSON_LENGTH(v_consumo) > 0 THEN
      SET v_len = JSON_LENGTH(v_consumo);
      WHILE i < v_len DO
        SET v_pid = JSON_UNQUOTE(JSON_EXTRACT(v_consumo, CONCAT('$[',i,'].producto_id')));
        SET v_qty = JSON_UNQUOTE(JSON_EXTRACT(v_consumo, CONCAT('$[',i,'].cantidad')));
        IF v_pid IS NOT NULL AND v_qty IS NOT NULL THEN
          UPDATE productos SET stock = GREATEST(0, stock - v_qty) WHERE id = v_pid;
        END IF;
        SET i = i + 1;
      END WHILE;
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ficha_checklist`
--

CREATE TABLE `ficha_checklist` (
  `id` int(10) UNSIGNED NOT NULL,
  `ficha_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `completado` tinyint(1) NOT NULL DEFAULT 0,
  `observacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ficha_checklist`
--

INSERT INTO `ficha_checklist` (`id`, `ficha_id`, `item_id`, `completado`, `observacion`) VALUES
(1, 1, 1, 1, NULL),
(2, 1, 2, 1, NULL),
(3, 1, 3, 1, 'Se respetó el largo solicitado'),
(4, 1, 4, 1, NULL),
(5, 1, 5, 1, NULL),
(6, 1, 6, 1, NULL),
(7, 1, 7, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos_mascota`
--

CREATE TABLE `fotos_mascota` (
  `id` int(10) UNSIGNED NOT NULL,
  `ficha_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('antes','despues') NOT NULL,
  `url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fotos_mascota`
--

INSERT INTO `fotos_mascota` (`id`, `ficha_id`, `tipo`, `url`, `created_at`) VALUES
(1, 1, 'antes', '/uploads/firulais_antes.jpg', '2026-05-07 15:11:42'),
(2, 1, 'despues', '/uploads/firulais_despues.jpg', '2026-05-07 15:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `groomers`
--

CREATE TABLE `groomers` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `apellido` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `especialidad` varchar(120) DEFAULT NULL,
  `turno` enum('manana','tarde','completo') NOT NULL DEFAULT 'completo',
  `capacidad_simultanea` tinyint(4) NOT NULL DEFAULT 1,
  `horario_trabajo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Configuración semanal JSON' CHECK (json_valid(`horario_trabajo`)),
  `estado_activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `groomers`
--

INSERT INTO `groomers` (`id`, `usuario_id`, `nombre`, `apellido`, `telefono`, `especialidad`, `turno`, `capacidad_simultanea`, `horario_trabajo`, `estado_activo`, `created_at`, `updated_at`) VALUES
(1, 3, 'Ana', 'Martínez', '69911122', 'Corte fino', 'completo', 2, '{\"lunes\":{\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"martes\":{\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"miercoles\":{\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"jueves\":{\"inicio\":\"09:00\",\"fin\":\"18:00\"},\"viernes\":{\"inicio\":\"09:00\",\"fin\":\"18:00\"}}', 1, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(2, 4, 'Luis', 'Rodríguez', '69933344', 'Baño y secado', 'manana', 1, '{\"lunes\":{\"inicio\":\"08:00\",\"fin\":\"14:00\"},\"martes\":{\"inicio\":\"08:00\",\"fin\":\"14:00\"},\"miercoles\":{\"inicio\":\"08:00\",\"fin\":\"14:00\"},\"jueves\":{\"inicio\":\"08:00\",\"fin\":\"14:00\"},\"viernes\":{\"inicio\":\"08:00\",\"fin\":\"14:00\"}}', 1, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(13, 28, 'Ivan', 'Limachi', '543646', 'Corte fino', 'manana', 1, NULL, 1, '2026-05-12 02:07:52', '2026-05-12 02:07:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_mascota`
--

CREATE TABLE `historial_mascota` (
  `id` int(10) UNSIGNED NOT NULL,
  `mascota_id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('servicio','recomendacion','alerta','vacuna','otro') NOT NULL,
  `descripcion` text NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Quien registró el evento',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `especie` enum('perro','gato','otro') NOT NULL DEFAULT 'perro',
  `raza` varchar(80) DEFAULT NULL,
  `tamano` enum('pequeno','mediano','grande','gigante') NOT NULL DEFAULT 'mediano',
  `sexo` enum('macho','hembra') DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `peso_kg` decimal(5,2) DEFAULT NULL,
  `temperamento` enum('tranquilo','jugueton','agresivo','ansioso','otro') DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `restricciones_medicas` text DEFAULT NULL,
  `vacunas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '[{"nombre":"..","fecha_aplicacion":"..","fecha_vencimiento":".."}]' CHECK (json_valid(`vacunas`)),
  `foto_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id`, `nombre`, `especie`, `raza`, `tamano`, `sexo`, `fecha_nacimiento`, `peso_kg`, `temperamento`, `alergias`, `restricciones_medicas`, `vacunas`, `foto_url`, `created_at`, `updated_at`) VALUES
(1, 'Firulais', 'perro', 'Golden Retriever', 'mediano', 'macho', '2020-03-15', 28.50, 'tranquilo', NULL, NULL, '[{\"nombre\":\"Rabia\",\"fecha_aplicacion\":\"2025-01-10\",\"fecha_vencimiento\":\"2026-01-10\"},{\"nombre\":\"Parvovirus\",\"fecha_aplicacion\":\"2025-01-10\",\"fecha_vencimiento\":\"2026-01-10\"}]', NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(2, 'Luna', 'perro', 'Poodle', 'pequeno', 'hembra', '2021-07-22', 5.20, 'jugueton', 'Polen', 'Cuidado con oídos', '[{\"nombre\":\"Rabia\",\"fecha_aplicacion\":\"2025-02-20\",\"fecha_vencimiento\":\"2026-02-20\"}]', NULL, '2026-05-07 15:11:41', '2026-05-25 01:55:48'),
(3, 'Max', 'gato', 'Siamés', 'mediano', 'macho', '2019-11-05', 4.80, 'agresivo', NULL, 'Evitar baño con agua caliente', NULL, NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41'),
(4, 'Bella', 'perro', 'Bulldog Francés', 'mediano', 'hembra', '2022-01-30', 10.30, 'tranquilo', 'Piel sensible', NULL, '[{\"nombre\":\"Rabia\",\"fecha_aplicacion\":\"2025-03-01\",\"fecha_vencimiento\":\"2026-03-01\"}]', NULL, '2026-05-07 15:11:41', '2026-05-07 15:11:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascota_dueno`
--

CREATE TABLE `mascota_dueno` (
  `mascota_id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mascota_dueno`
--

INSERT INTO `mascota_dueno` (`mascota_id`, `cliente_id`, `es_principal`) VALUES
(1, 1, 1),
(2, 1, 0),
(3, 2, 1),
(4, 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `cita_id` int(10) UNSIGNED DEFAULT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `tipo_evento` enum('confirmacion','recordatorio_24h','recordatorio_2h','listo_recoger','encuesta','promocion') NOT NULL,
  `canal` enum('email','whatsapp','sms','telegram') NOT NULL DEFAULT 'email',
  `destino` varchar(120) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha_programada` datetime NOT NULL,
  `fecha_enviada` datetime DEFAULT NULL,
  `estado` enum('pendiente','enviada','fallida') NOT NULL DEFAULT 'pendiente',
  `intentos` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `cita_id`, `cliente_id`, `tipo_evento`, `canal`, `destino`, `mensaje`, `fecha_programada`, `fecha_enviada`, `estado`, `intentos`, `created_at`) VALUES
(1, 3, 2, 'recordatorio_24h', 'email', 'cliente2@hotmail.com', 'Recordatorio: su cita es mañana a las 09:00', '2026-05-07 09:00:00', NULL, 'pendiente', 0, '2026-05-07 15:11:42'),
(2, 4, 3, 'confirmacion', 'whatsapp', '69987654', 'Su cita ha sido confirmada para el 2026-05-09 a las 11:00', '2026-05-08 10:00:00', NULL, 'enviada', 0, '2026-05-07 15:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(10) UNSIGNED NOT NULL,
  `factura_id` int(10) UNSIGNED NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo` enum('efectivo','qr','transferencia') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `estado` enum('completado','pendiente','fallido') NOT NULL DEFAULT 'completado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `factura_id`, `monto`, `metodo`, `referencia`, `estado`, `created_at`) VALUES
(1, 1, 198.00, 'qr', 'QR-12345', 'completado', '2026-05-07 15:11:42'),
(2, 2, 93.50, 'transferencia', 'TRF-67890', 'completado', '2026-05-07 15:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `carrito_id` int(10) UNSIGNED DEFAULT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_contacto` enum('whatsapp','telegram') DEFAULT NULL,
  `estado` enum('pendiente','enviado','confirmado','pagado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `carrito_id`, `cliente_id`, `subtotal`, `descuento`, `total`, `metodo_contacto`, `estado`, `notas`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 85.00, 0.00, 85.00, 'whatsapp', 'pagado', 'Entregar en domicilio', '2026-05-07 15:11:42', '2026-05-07 15:11:42'),
(2, NULL, 1, 49.00, 0.00, 49.00, 'whatsapp', 'pendiente', NULL, '2026-05-25 03:55:01', '2026-05-25 03:55:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sku` varchar(50) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 5,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio_base`, `sku`, `imagen_url`, `stock`, `stock_minimo`, `activo`, `created_at`) VALUES
(1, 2, 'Shampoo Hipoalergénico', 'Para piel sensible', 25.00, 'SH-001', NULL, 50, 5, 1, '2026-05-07 15:11:42'),
(2, 2, 'Shampoo Antipulgas', 'Elimina pulgas y garrapatas', 35.00, 'SH-002', NULL, 30, 5, 1, '2026-05-07 15:11:42'),
(3, 3, 'Cepillo Desenredante', 'Cepillo profesional', 15.00, 'ACC-001', NULL, 100, 10, 1, '2026-05-07 15:11:42'),
(4, 1, 'Snacks Premium', 'Galletas para perro', 12.00, 'ALI-001', NULL, 200, 20, 1, '2026-05-07 15:11:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promociones`
--

CREATE TABLE `promociones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('porcentaje','monto_fijo','2x1','servicio_gratis') DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `producto_id` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `uso_maximo` int(11) DEFAULT 1,
  `usos_actuales` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` enum('admin','recepcion','groomer','cliente') NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'admin', 'Acceso total al sistema'),
(2, 'recepcion', 'Gestión de citas y clientes'),
(3, 'groomer', 'Atención de mascotas y fichas'),
(4, 'cliente', 'Auto-registro, citas y mascotas propias');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duracion_base_minutos` smallint(6) NOT NULL DEFAULT 60,
  `permite_doble_booking` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_bloqueo_consecutivo` tinyint(1) NOT NULL DEFAULT 0,
  `factor_tamaño_raza` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`factor_tamaño_raza`)),
  `consumo_insumos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`consumo_insumos`)),
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `descripcion`, `precio_base`, `duracion_base_minutos`, `permite_doble_booking`, `requiere_bloqueo_consecutivo`, `factor_tamaño_raza`, `consumo_insumos`, `activo`, `created_at`) VALUES
(1, 'Baño y Secado', NULL, 80.00, 60, 0, 0, NULL, NULL, 1, '2026-05-07 15:11:39'),
(2, 'Corte de Pelo', NULL, 120.00, 90, 0, 0, NULL, NULL, 1, '2026-05-07 15:11:39'),
(3, 'Baño + Corte Completo', NULL, 180.00, 120, 0, 0, NULL, NULL, 1, '2026-05-07 15:11:39'),
(4, 'Corte de Uñas', NULL, 30.00, 15, 0, 0, NULL, NULL, 1, '2026-05-07 15:11:39'),
(5, 'Limpieza de Oídos', NULL, 30.00, 15, 0, 0, NULL, NULL, 1, '2026-05-07 15:11:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_usuario`
--

CREATE TABLE `sesiones_usuario` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `token_jwt` text NOT NULL,
  `refresh_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  `fecha_expiracion` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'BCrypt costo 12',
  `two_factor_secret` varchar(64) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `ip_autorizada` varchar(255) DEFAULT NULL,
  `hostname_autorizado` varchar(100) DEFAULT NULL,
  `hostname_restriccion_activa` tinyint(1) NOT NULL DEFAULT 0,
  `ip_restriccion_activa` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('activo','inactivo','bloqueado','pendiente') NOT NULL DEFAULT 'pendiente',
  `token_activacion` varchar(100) DEFAULT NULL,
  `token_expiracion` datetime DEFAULT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `token_rec_expiracion` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint(4) NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `creado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `codigo_2fa` varchar(10) DEFAULT NULL,
  `codigo_2fa_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `rol_id`, `email`, `password_hash`, `two_factor_secret`, `two_factor_enabled`, `ip_autorizada`, `hostname_autorizado`, `hostname_restriccion_activa`, `ip_restriccion_activa`, `estado`, `token_activacion`, `token_expiracion`, `token_recuperacion`, `token_rec_expiracion`, `intentos_fallidos`, `bloqueado_hasta`, `ultimo_acceso`, `creado_por`, `created_at`, `updated_at`, `codigo_2fa`, `codigo_2fa_expira`) VALUES
(1, 1, 'admin@petspa.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, '$2y$10$EO4k7iAA6kRoTNz9jVmjZOQVe3U/.TjM7zLGE86RfS3MLxCQgQoHC', 1, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-30 17:40:27', NULL, '2026-05-07 15:11:41', '2026-05-30 21:40:27', NULL, NULL),
(2, 2, 'recepcion@petspa.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-27 21:31:03', NULL, '2026-05-07 15:11:41', '2026-05-28 01:31:03', NULL, NULL),
(3, 3, 'groomer1@petspa.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-24 23:48:32', NULL, '2026-05-07 15:11:41', '2026-05-25 03:48:32', NULL, NULL),
(4, 3, 'groomer2@petspa.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-27 21:29:50', NULL, '2026-05-07 15:11:41', '2026-05-28 01:29:50', NULL, NULL),
(5, 4, 'cliente1@gmail.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-27 21:28:50', NULL, '2026-05-07 15:11:41', '2026-05-28 01:28:50', NULL, NULL),
(6, 4, 'cliente2@hotmail.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-05-07 15:11:41', '2026-05-07 15:14:57', NULL, NULL),
(7, 4, 'cliente3@yahoo.com', '$2y$10$UyJmu7H3nBGua8b/dgEsL.xw1ZkoJpspieafWXribJI4QLX7HB/Uq', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-24 23:32:42', NULL, '2026-05-07 15:11:41', '2026-05-25 03:32:42', NULL, NULL),
(13, 4, 'cliente5@gamai.com', '$2y$12$FU7G/tuwnfKPRsxYl43PFOUowBXO9p0fb5Pdcc2wBB2DUtNhcmUG2', NULL, 0, NULL, NULL, 0, 0, 'pendiente', '28148f8170273bb9b984ea8a5d6af0f723e650315481d0ffdfe49e64104ff6fa3da80e8ac630bf0a96f81181a4344ac945aa', '2026-05-07 12:25:26', NULL, NULL, 0, NULL, NULL, NULL, '2026-05-07 16:10:27', '2026-05-07 16:10:27', NULL, NULL),
(27, 4, 'omarlimachi473@gmail.com', '$2y$12$NhuJcB08QTknB9Kxg78Q8u81t7Ol5rur59nisfrlwEOXR4fUv3qHW', NULL, 0, NULL, NULL, 0, 0, 'activo', NULL, NULL, NULL, NULL, 0, NULL, '2026-05-11 22:05:22', NULL, '2026-05-12 02:04:26', '2026-05-12 02:05:22', NULL, NULL),
(28, 3, 'omarlimachi437@gmail.com', '$2y$10$u9LdBChD3Z5z2uCEdHNYBOAFFVLDqUjQLaW/ahmp26VpszXGyZxSq', NULL, 0, NULL, NULL, 0, 0, 'bloqueado', NULL, NULL, NULL, NULL, 5, '2026-05-11 22:25:37', '2026-05-11 22:08:52', NULL, '2026-05-12 02:07:52', '2026-05-12 02:10:37', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `variantes_producto`
--

CREATE TABLE `variantes_producto` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `atributo` varchar(60) NOT NULL,
  `valor` varchar(60) NOT NULL,
  `precio_extra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `sku_variante` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `variantes_producto`
--

INSERT INTO `variantes_producto` (`id`, `producto_id`, `atributo`, `valor`, `precio_extra`, `stock`, `sku_variante`) VALUES
(1, 1, 'Tamaño', '250ml', 0.00, 30, 'SH-001-250'),
(2, 1, 'Tamaño', '500ml', 10.00, 20, 'SH-001-500'),
(3, 2, 'Fragancia', 'Lavanda', 0.00, 15, 'SH-002-LAV'),
(4, 2, 'Fragancia', 'Menta', 0.00, 15, 'SH-002-MEN');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_citas_completas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_citas_completas` (
`id` int(10) unsigned
,`fecha_hora_inicio` datetime
,`fecha_hora_fin` datetime
,`estado` enum('agendada','confirmada','en_progreso','completada','cancelada','no_asistio')
,`precio_acordado` decimal(10,2)
,`cliente` varchar(241)
,`mascota` varchar(80)
,`especie` enum('perro','gato','otro')
,`groomer_email` varchar(180)
,`groomer_nombre` varchar(120)
,`servicio` varchar(120)
,`precio_base` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_facturas_cliente`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_facturas_cliente` (
`id` int(10) unsigned
,`numero` int(10) unsigned
,`cliente` varchar(241)
,`subtotal` decimal(10,2)
,`impuesto` decimal(10,2)
,`total` decimal(10,2)
,`metodo_pago` enum('efectivo','qr','transferencia')
,`estado` enum('pendiente','pagada','cancelada')
,`fecha_emision` datetime
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_resumen_diario`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_resumen_diario` (
`fecha` date
,`total_citas` bigint(21)
,`completadas` decimal(22,0)
,`canceladas` decimal(22,0)
,`no_asistio` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_citas_completas`
--
DROP TABLE IF EXISTS `v_citas_completas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_citas_completas`  AS SELECT `c`.`id` AS `id`, `c`.`fecha_hora_inicio` AS `fecha_hora_inicio`, `c`.`fecha_hora_fin` AS `fecha_hora_fin`, `c`.`estado` AS `estado`, `c`.`precio_acordado` AS `precio_acordado`, concat(`cl`.`nombre`,' ',`cl`.`apellido`) AS `cliente`, `m`.`nombre` AS `mascota`, `m`.`especie` AS `especie`, `u`.`email` AS `groomer_email`, `gr`.`nombre` AS `groomer_nombre`, `s`.`nombre` AS `servicio`, `s`.`precio_base` AS `precio_base` FROM (((((`citas` `c` join `clientes` `cl` on(`cl`.`id` = `c`.`cliente_id`)) join `mascotas` `m` on(`m`.`id` = `c`.`mascota_id`)) join `groomers` `gr` on(`gr`.`id` = `c`.`groomer_id`)) join `usuarios` `u` on(`u`.`id` = `gr`.`usuario_id`)) join `servicios` `s` on(`s`.`id` = `c`.`servicio_id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_facturas_cliente`
--
DROP TABLE IF EXISTS `v_facturas_cliente`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_facturas_cliente`  AS SELECT `f`.`id` AS `id`, `f`.`numero` AS `numero`, concat(`c`.`nombre`,' ',`c`.`apellido`) AS `cliente`, `f`.`subtotal` AS `subtotal`, `f`.`impuesto` AS `impuesto`, `f`.`total` AS `total`, `f`.`metodo_pago` AS `metodo_pago`, `f`.`estado` AS `estado`, `f`.`fecha_emision` AS `fecha_emision` FROM (`facturas` `f` join `clientes` `c` on(`c`.`id` = `f`.`cliente_id`)) ORDER BY `f`.`fecha_emision` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_resumen_diario`
--
DROP TABLE IF EXISTS `v_resumen_diario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_resumen_diario`  AS SELECT cast(`citas`.`fecha_hora_inicio` as date) AS `fecha`, count(0) AS `total_citas`, sum(case when `citas`.`estado` = 'completada' then 1 else 0 end) AS `completadas`, sum(case when `citas`.`estado` = 'cancelada' then 1 else 0 end) AS `canceladas`, sum(case when `citas`.`estado` = 'no_asistio' then 1 else 0 end) AS `no_asistio` FROM `citas` GROUP BY cast(`citas`.`fecha_hora_inicio` as date) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_usuario` (`usuario_id`),
  ADD KEY `idx_audit_fecha` (`created_at`);

--
-- Indices de la tabla `bloqueos_calendario`
--
ALTER TABLE `bloqueos_calendario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bloqueo_groomer` (`groomer_id`);

--
-- Indices de la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carrito_cliente` (`cliente_id`);

--
-- Indices de la tabla `categorias_productos`
--
ALTER TABLE `categorias_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cat_padre` (`padre_id`);

--
-- Indices de la tabla `checklist_items_plantilla`
--
ALTER TABLE `checklist_items_plantilla`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_checklist_servicio` (`servicio_id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cita_mascota` (`mascota_id`),
  ADD KEY `fk_cita_groomer` (`groomer_id`),
  ADD KEY `fk_cita_servicio` (`servicio_id`),
  ADD KEY `fk_cita_cliente` (`cliente_id`),
  ADD KEY `fk_cita_creado_por` (`creado_por`),
  ADD KEY `idx_cita_fecha` (`fecha_hora_inicio`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clientes_usuario` (`usuario_id`),
  ADD KEY `idx_clientes_ci` (`ci`);

--
-- Indices de la tabla `consumo_insumos`
--
ALTER TABLE `consumo_insumos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_groomer` (`groomer_id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_cita` (`cita_id`);

--
-- Indices de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dc_carrito` (`carrito_id`),
  ADD KEY `fk_dc_producto` (`producto_id`),
  ADD KEY `fk_dc_variante` (`variante_id`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dp_pedido` (`pedido_id`),
  ADD KEY `fk_dp_producto` (`producto_id`);

--
-- Indices de la tabla `disponibilidad_groomer`
--
ALTER TABLE `disponibilidad_groomer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_disp_groomer` (`groomer_id`);

--
-- Indices de la tabla `encuestas_satisfaccion`
--
ALTER TABLE `encuestas_satisfaccion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_encuesta_cita` (`cita_id`),
  ADD KEY `fk_enc_cliente` (`cliente_id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_factura_numero` (`numero`),
  ADD KEY `fk_factura_cliente` (`cliente_id`),
  ADD KEY `fk_factura_cita` (`cita_id`),
  ADD KEY `fk_factura_pedido` (`pedido_id`);

--
-- Indices de la tabla `fichas_grooming`
--
ALTER TABLE `fichas_grooming`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ficha_cita` (`cita_id`);

--
-- Indices de la tabla `ficha_checklist`
--
ALTER TABLE `ficha_checklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ficha_item` (`ficha_id`,`item_id`),
  ADD KEY `fk_fcheck_item` (`item_id`);

--
-- Indices de la tabla `fotos_mascota`
--
ALTER TABLE `fotos_mascota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_foto_ficha` (`ficha_id`);

--
-- Indices de la tabla `groomers`
--
ALTER TABLE `groomers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_groomers_usuario` (`usuario_id`);

--
-- Indices de la tabla `historial_mascota`
--
ALTER TABLE `historial_mascota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_historial_mascota` (`mascota_id`),
  ADD KEY `fk_historial_usuario` (`usuario_id`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mascota_dueno`
--
ALTER TABLE `mascota_dueno`
  ADD PRIMARY KEY (`mascota_id`,`cliente_id`),
  ADD KEY `fk_md_cliente` (`cliente_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_cita` (`cita_id`),
  ADD KEY `fk_notif_cliente` (`cliente_id`),
  ADD KEY `idx_notif_estado` (`estado`,`fecha_programada`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pago_factura` (`factura_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_carrito` (`carrito_id`),
  ADD KEY `fk_pedido_cliente` (`cliente_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_producto_sku` (`sku`),
  ADD KEY `fk_producto_cat` (`categoria_id`);

--
-- Indices de la tabla `promociones`
--
ALTER TABLE `promociones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_nombre` (`nombre`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sesiones_usuario`
--
ALTER TABLE `sesiones_usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sesiones_usuario` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `fk_usuarios_rol` (`rol_id`),
  ADD KEY `fk_usuarios_creado_por` (`creado_por`);

--
-- Indices de la tabla `variantes_producto`
--
ALTER TABLE `variantes_producto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variante_sku` (`sku_variante`),
  ADD KEY `fk_variante_producto` (`producto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT de la tabla `bloqueos_calendario`
--
ALTER TABLE `bloqueos_calendario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carritos`
--
ALTER TABLE `carritos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `categorias_productos`
--
ALTER TABLE `categorias_productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `checklist_items_plantilla`
--
ALTER TABLE `checklist_items_plantilla`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `consumo_insumos`
--
ALTER TABLE `consumo_insumos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `disponibilidad_groomer`
--
ALTER TABLE `disponibilidad_groomer`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuestas_satisfaccion`
--
ALTER TABLE `encuestas_satisfaccion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `fichas_grooming`
--
ALTER TABLE `fichas_grooming`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ficha_checklist`
--
ALTER TABLE `ficha_checklist`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `fotos_mascota`
--
ALTER TABLE `fotos_mascota`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `groomers`
--
ALTER TABLE `groomers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `historial_mascota`
--
ALTER TABLE `historial_mascota`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `promociones`
--
ALTER TABLE `promociones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_usuario`
--
ALTER TABLE `sesiones_usuario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `variantes_producto`
--
ALTER TABLE `variantes_producto`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bloqueos_calendario`
--
ALTER TABLE `bloqueos_calendario`
  ADD CONSTRAINT `fk_bloqueo_groomer` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `carritos`
--
ALTER TABLE `carritos`
  ADD CONSTRAINT `fk_carrito_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categorias_productos`
--
ALTER TABLE `categorias_productos`
  ADD CONSTRAINT `fk_cat_padre` FOREIGN KEY (`padre_id`) REFERENCES `categorias_productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `checklist_items_plantilla`
--
ALTER TABLE `checklist_items_plantilla`
  ADD CONSTRAINT `fk_checklist_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_cita_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_cita_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cita_groomer` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`),
  ADD CONSTRAINT `fk_cita_mascota` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`),
  ADD CONSTRAINT `fk_cita_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`);

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `consumo_insumos`
--
ALTER TABLE `consumo_insumos`
  ADD CONSTRAINT `fk_consumo_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_consumo_groomer` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_consumo_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalle_carrito`
--
ALTER TABLE `detalle_carrito`
  ADD CONSTRAINT `fk_dc_carrito` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dc_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  ADD CONSTRAINT `fk_dc_variante` FOREIGN KEY (`variante_id`) REFERENCES `variantes_producto` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `fk_dp_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dp_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `disponibilidad_groomer`
--
ALTER TABLE `disponibilidad_groomer`
  ADD CONSTRAINT `fk_disp_groomer` FOREIGN KEY (`groomer_id`) REFERENCES `groomers` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `encuestas_satisfaccion`
--
ALTER TABLE `encuestas_satisfaccion`
  ADD CONSTRAINT `fk_enc_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  ADD CONSTRAINT `fk_enc_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_factura_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_factura_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_factura_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `fichas_grooming`
--
ALTER TABLE `fichas_grooming`
  ADD CONSTRAINT `fk_ficha_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`);

--
-- Filtros para la tabla `ficha_checklist`
--
ALTER TABLE `ficha_checklist`
  ADD CONSTRAINT `fk_fcheck_ficha` FOREIGN KEY (`ficha_id`) REFERENCES `fichas_grooming` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fcheck_item` FOREIGN KEY (`item_id`) REFERENCES `checklist_items_plantilla` (`id`);

--
-- Filtros para la tabla `fotos_mascota`
--
ALTER TABLE `fotos_mascota`
  ADD CONSTRAINT `fk_foto_ficha` FOREIGN KEY (`ficha_id`) REFERENCES `fichas_grooming` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `groomers`
--
ALTER TABLE `groomers`
  ADD CONSTRAINT `fk_groomers_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_mascota`
--
ALTER TABLE `historial_mascota`
  ADD CONSTRAINT `fk_historial_mascota` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `mascota_dueno`
--
ALTER TABLE `mascota_dueno`
  ADD CONSTRAINT `fk_md_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_md_mascota` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notif_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notif_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_carrito` FOREIGN KEY (`carrito_id`) REFERENCES `carritos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `sesiones_usuario`
--
ALTER TABLE `sesiones_usuario`
  ADD CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `variantes_producto`
--
ALTER TABLE `variantes_producto`
  ADD CONSTRAINT `fk_variante_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
