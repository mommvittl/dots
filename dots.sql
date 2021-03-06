-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Янв 11 2017 г., 07:06
-- Версия сервера: 10.0.28-MariaDB-0ubuntu0.16.04.1
-- Версия PHP: 5.6.29-1+deb.sury.org~xenial+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `dots`
--
CREATE DATABASE IF NOT EXISTS `dots` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dots`;

-- --------------------------------------------------------

DROP TABLE IF EXISTS `deleted_points`;
CREATE TABLE `deleted_points` (
  `id` bigint(20) UNSIGNED NOT NULL   PRIMARY KEY AUTO_INCREMENT,
  `point_id` bigint(20) UNSIGNED NOT NULL  ,
  `game_id` int(10) UNSIGNED NOT NULL,
  `del_time`   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `deleted_polygons`;
CREATE TABLE `deleted_polygons` (
  `id` int(10) UNSIGNED NOT NULL   PRIMARY KEY AUTO_INCREMENT,
  `polygon_id` int(10) UNSIGNED NOT NULL ,
  `game_id` int(10) UNSIGNED NOT NULL ,
 `del_time`   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `user_has_points`;
CREATE TABLE `user_has_points` (
  `id` bigint(20) UNSIGNED NOT NULL   PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `point` point NOT NULL,
  `accuracy`   int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `game_id` int(10) UNSIGNED NOT NULL,
  `status` int(1)  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `user_has_polygons`;
CREATE TABLE `user_has_polygons` (
  `id` int(10) UNSIGNED NOT NULL  PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `polygon` polygon NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `game_id` int(10) UNSIGNED NOT NULL,
  `status` int(1)  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `chat`;
CREATE TABLE `chat`
(
  `id` int(20) unsigned NOT NULL auto_increment PRIMARY KEY,
  `data_post`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `user_id` int(10) UNSIGNED DEFAULT NULL,
  `message` text DEFAULT NULL   
);
-- --------------------------------------------------------

--
-- Структура таблицы `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE `game` (
  `id` int(10) UNSIGNED NOT NULL   PRIMARY KEY AUTO_INCREMENT,
  `user1_id` int(10) UNSIGNED NOT NULL,
  `user2_id` int(10) UNSIGNED NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `stop_time` timestamp NOT NULL DEFAULT NULL,
  `winner_id` int(10)  DEFAULT NULL,
  `user1_scores` int(10)  DEFAULT 0,
  `user2_scores`  int(10)  DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `ready`
--

DROP TABLE IF EXISTS `ready`;
CREATE TABLE `ready` (
  `id` int(10) UNSIGNED NOT NULL   PRIMARY KEY AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `point` point NOT NULL,
  `opponent_id` int(10)   DEFAULT  NULL,
  `update_time`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) UNSIGNED NOT NULL   PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `game_id` int(10) UNSIGNED DEFAULT NULL,
  `scores` smallint(5) UNSIGNED DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `authKey` varchar(32) DEFAULT NULL,
  `accessToken` varchar(32) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ready`
--
ALTER TABLE `ready`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_has_points`
--
ALTER TABLE `user_has_points`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_has_points_copy1`
--
ALTER TABLE `user_has_points_copy1`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_has_polygons`
--
ALTER TABLE `user_has_polygons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `ready`
--
ALTER TABLE `ready`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
