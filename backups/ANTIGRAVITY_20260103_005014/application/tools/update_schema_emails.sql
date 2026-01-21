-- TABLE ARCHIVAGE EMAILS
DROP TABLE IF EXISTS emails_archives;
CREATE TABLE emails_archives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT,
    affaire_id INT,
    client_id INT,
    nom_fichier VARCHAR(255),
    chemin_fichier VARCHAR(255),
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    type_fichier ENUM('MSG', 'EML') DEFAULT 'MSG',
    traite_ia BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (commande_id) REFERENCES commandes_achats(id) ON DELETE SET NULL,
    FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
