-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Фев 01 2026 г., 18:43
-- Версия сервера: 10.4.27-MariaDB
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `permits`
--

-- --------------------------------------------------------

--
-- Структура таблицы `applications`
--

CREATE TABLE `applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `ward` char(3) NOT NULL DEFAULT '0',
  `statusCode` int(11) NOT NULL DEFAULT 0,
  `folderYear` varchar(10) NOT NULL DEFAULT '0',
  `folderSequence` varchar(10) NOT NULL DEFAULT '0',
  `folderName` varchar(100) NOT NULL DEFAULT '0',
  `folderAddress` varchar(100) NOT NULL DEFAULT '0',
  `folderRsn` int(11) NOT NULL DEFAULT 0,
  `folderType` varchar(4) NOT NULL DEFAULT '0',
  `folderSection` varchar(4) NOT NULL DEFAULT '0',
  `folderRevision` varchar(4) NOT NULL DEFAULT '0',
  `statusDesc` varchar(50) NOT NULL DEFAULT '0',
  `folderTypeDesc` varchar(50) NOT NULL DEFAULT '0',
  `inDate` datetime NOT NULL DEFAULT current_timestamp(),
  `hidden` tinyint(1) NOT NULL DEFAULT 0,
  `app_descr` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `settings_`
--

CREATE TABLE `settings_` (
  `id` int(10) UNSIGNED NOT NULL,
  `ward` char(3) NOT NULL DEFAULT '0',
  `pos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `amount_apps` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `date_start` datetime NOT NULL DEFAULT current_timestamp(),
  `date_curr` datetime NOT NULL DEFAULT current_timestamp(),
  `blocked` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `upload_status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `street` varchar(60) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `wards`
--

CREATE TABLE `wards` (
  `id` int(10) UNSIGNED NOT NULL,
  `ward` char(3) NOT NULL DEFAULT '0',
  `ward_text` varchar(100) NOT NULL,
  `last_update` datetime NOT NULL DEFAULT current_timestamp(),
  `complete_update` tinyint(1) NOT NULL DEFAULT 1,
  `last_address` varchar(150) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `folderRsn_idx` (`folderRsn`),
  ADD KEY `statusCode_idx` (`statusCode`),
  ADD KEY `ward_idx` (`ward`),
  ADD KEY `inDate_idx` (`inDate`);

--
-- Индексы таблицы `settings_`
--
ALTER TABLE `settings_`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_settings__wards` (`ward`);

--
-- Индексы таблицы `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ward_uniq_idx` (`ward`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `settings_`
--
ALTER TABLE `settings_`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `FK_applications_wards` FOREIGN KEY (`ward`) REFERENCES `wards` (`ward`);

--
-- Ограничения внешнего ключа таблицы `settings_`
--
ALTER TABLE `settings_`
  ADD CONSTRAINT `FK_settings__wards` FOREIGN KEY (`ward`) REFERENCES `wards` (`ward`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
