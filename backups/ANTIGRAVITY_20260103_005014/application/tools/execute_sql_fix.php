<?php
require_once __DIR__ . '/../db.php';

$sql = "CREATE TABLE IF NOT EXISTS `sav_interventions` (
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
  KEY `technicien_id` (`technicien_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $pdo->exec($sql);
    echo "<h1>✅ Table sav_interventions created!</h1>";
} catch(PDOException $e) {
    echo "<h1>❌ Error: " . $e->getMessage() . "</h1>";
}
