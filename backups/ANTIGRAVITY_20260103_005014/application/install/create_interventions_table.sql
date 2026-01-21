-- Table pour les métrages (interventions)
CREATE TABLE IF NOT EXISTS `interventions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `affaire_id` int(11) DEFAULT NULL,
  `nom_affaire` varchar(255) DEFAULT 'Métrage Libre',
  `client_nom` varchar(255) DEFAULT 'Non lié',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `statut` varchar(50) DEFAULT 'en_cours',
  `utilisateur_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `affaire_id` (`affaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
