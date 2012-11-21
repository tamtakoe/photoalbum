-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Хост: 10.0.2.10
-- Время создания: Ноя 21 2012 г., 16:50
-- Версия сервера: 5.5.27-28.1-log
-- Версия PHP: 5.3.16

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `a50859_6`
--

-- --------------------------------------------------------

--
-- Структура таблицы `album`
--

CREATE TABLE IF NOT EXISTS `album` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `position` int(10) NOT NULL,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `preview` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1597 ;

--
-- Дамп данных таблицы `album`
--

INSERT INTO `album` (`id`, `position`, `title`, `file`, `preview`) VALUES
(1595, 3, '', '50ac9e0a9e063.jpeg', '50ac9e0a9e063.png'),
(1499, 0, 'Дерево', '50ab80eb9f7d3.jpeg', '50ab80eb9f7d3.png'),
(1521, 4, '', '50ac65146d974.jpeg', '50ac65146d974.png'),
(1506, 2, 'Листик', '50ab81148b2e0.jpeg', '50ab81148b2e0.png'),
(1507, 1, 'Роза', '50ab81210f4fe.jpeg', '50ab81210f4fe.png'),
(1556, 6, '', '50ac7b5297836.jpeg', '50ac7b5297836.png'),
(1592, 5, '', '50ac95c7c072c.jpeg', '50ac95c7c072c.png');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
