CREATE TABLE IF NOT EXISTS `sav_interventions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `technicien_id` int(11) DEFAULT NULL,
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'Planifié',
  `compte_rendu` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `technicien_id` (`technicien_id`),
  CONSTRAINT `sav_interventions_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `sav_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
