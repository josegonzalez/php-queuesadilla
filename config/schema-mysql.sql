CREATE TABLE IF NOT EXISTS `jobs` (
  `id` mediumint(20) NOT NULL AUTO_INCREMENT,
  `queue` char(32) NOT NULL DEFAULT 'default',
  `data` mediumtext NOT NULL,
  `priority` int(1) NOT NULL DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `delay_until` datetime DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `attempts`int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `queue` (`queue`,`locked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;