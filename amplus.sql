-- Si se utilizan otros nombres para las tablas, modificar main.php

--
-- Estructura de tabla para la tabla `oia_pages`
--

CREATE TABLE IF NOT EXISTS `amp_pages` (
  `pid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `site` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `tot` blob NOT NULL,
  `nodes` blob NOT NULL,
  `score` decimal(4,1) NOT NULL DEFAULT '0.0',
  `conform` varchar(255) NOT NULL DEFAULT '',
  `revs` smallint(5) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `error` text NOT NULL,
  `changes` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `hash` varchar(50) NOT NULL DEFAULT '',
  `info` text,
  `pagecode` blob,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oia_sitemap`
--

CREATE TABLE IF NOT EXISTS `amp_sitemap` (
  `siteid` mediumint(8) unsigned NOT NULL,
  `home` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(50) NOT NULL DEFAULT '',
  `uris` mediumtext NOT NULL,
  `error404` text NOT NULL,
  `errors` text NOT NULL,
  `info` text NOT NULL,
  PRIMARY KEY (`siteid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oia_sites`
--

CREATE TABLE IF NOT EXISTS `amp_sites` (
  `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `cat` varchar(255) NOT NULL DEFAULT '',
  `home` varchar(255) NOT NULL DEFAULT '',
  `pages` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `scores` decimal(4,1) NOT NULL DEFAULT '0.0',
  `conform` varchar(255) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `advance` varchar(5) NOT NULL DEFAULT '',
  `info` text NOT NULL,
  `monitor` mediumint(8) unsigned NOT NULL,
  `timestats` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `recordstats` blob,
  `stats` text NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oia_stats`
--

CREATE TABLE IF NOT EXISTS `amp_stats` (
  `catid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(125) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `cats` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `sites` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `pages` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `score` decimal(4,1) NOT NULL DEFAULT '0.0',
  `errors` text NOT NULL,
  `scores` text NOT NULL,
  `conform` varchar(255) NOT NULL DEFAULT '',
  `vals` text NOT NULL,
  `recordstats` blob,
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
