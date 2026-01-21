-- Email Module Schema
-- Tables pour le système de messagerie intégré

-- Templates d'emails avec variables dynamiques
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    categorie ENUM('DEVIS', 'FACTURE', 'RELANCE', 'CONFIRMATION', 'AUTRE') DEFAULT 'AUTRE',
    sujet VARCHAR(255) NOT NULL,
    corps TEXT NOT NULL,
    variables JSON COMMENT 'Liste des variables disponibles: {NOM_CLIENT}, {NUM_DEVIS}, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache IMAP pour performance
CREATE TABLE IF NOT EXISTS email_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder VARCHAR(50) NOT NULL,
    email_data JSON NOT NULL,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_folder_date (folder, cached_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historique des emails envoyés
CREATE TABLE IF NOT EXISTS email_sent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attachments JSON,
    affaire_id INT NULL,
    client_id INT NULL,
    user_id INT NULL COMMENT 'Utilisateur qui a envoyé',
    FOREIGN KEY (affaire_id) REFERENCES affaires(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_affaire (affaire_id),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Brouillons
CREATE TABLE IF NOT EXISTS email_drafts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255),
    subject VARCHAR(255),
    body TEXT,
    attachments JSON,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates par défaut
INSERT INTO email_templates (nom, categorie, sujet, corps, variables) VALUES
('Envoi Devis', 'DEVIS', 'Devis {NUM_DEVIS} - {NOM_CLIENT}', 
'<p>Bonjour {NOM_CLIENT},</p>
<p>Veuillez trouver ci-joint notre devis n°<strong>{NUM_DEVIS}</strong> pour vos travaux de menuiserie.</p>
<p>Montant total : <strong>{MONTANT_TTC}€ TTC</strong></p>
<p>Ce devis est valable 30 jours à compter de ce jour.</p>
<p>N\'hésitez pas à nous contacter pour toute question.</p>
<p>Cordialement,<br>L\'équipe Antigravity</p>',
'["NOM_CLIENT", "NUM_DEVIS", "MONTANT_TTC"]'),

('Confirmation RDV Métrage', 'CONFIRMATION', 'Confirmation RDV Métrage - {DATE_RDV}',
'<p>Bonjour {NOM_CLIENT},</p>
<p>Nous confirmons votre rendez-vous de métrage le <strong>{DATE_RDV}</strong> à <strong>{HEURE_RDV}</strong>.</p>
<p>Adresse du chantier :<br>{ADRESSE_CHANTIER}</p>
<p>Notre technicien <strong>{NOM_TECHNICIEN}</strong> se présentera à l\'heure convenue.</p>
<p>Merci de prévoir environ 1 heure pour cette intervention.</p>
<p>À bientôt,<br>L\'équipe Antigravity</p>',
'["NOM_CLIENT", "DATE_RDV", "HEURE_RDV", "ADRESSE_CHANTIER", "NOM_TECHNICIEN"]'),

('Relance Facture', 'RELANCE', 'Relance Facture {NUM_FACTURE}',
'<p>Bonjour {NOM_CLIENT},</p>
<p>Nous vous informons que la facture n°<strong>{NUM_FACTURE}</strong> d\'un montant de <strong>{MONTANT_TTC}€ TTC</strong> est arrivée à échéance.</p>
<p>Date d\'échéance : <strong>{DATE_ECHEANCE}</strong></p>
<p>Nous vous remercions de bien vouloir procéder au règlement dans les meilleurs délais.</p>
<p>Pour toute question, n\'hésitez pas à nous contacter.</p>
<p>Cordialement,<br>L\'équipe Antigravity</p>',
'["NOM_CLIENT", "NUM_FACTURE", "MONTANT_TTC", "DATE_ECHEANCE"]'),

('Confirmation Commande', 'CONFIRMATION', 'Confirmation de commande - {NOM_CLIENT}',
'<p>Bonjour {NOM_CLIENT},</p>
<p>Nous accusons réception de votre commande et vous en remercions.</p>
<p>Référence : <strong>{REF_COMMANDE}</strong></p>
<p>Montant : <strong>{MONTANT_TTC}€ TTC</strong></p>
<p>Délai de livraison estimé : <strong>{DELAI_LIVRAISON}</strong></p>
<p>Nous vous tiendrons informé de l\'avancement de votre commande.</p>
<p>Cordialement,<br>L\'équipe Antigravity</p>',
'["NOM_CLIENT", "REF_COMMANDE", "MONTANT_TTC", "DELAI_LIVRAISON"]');
