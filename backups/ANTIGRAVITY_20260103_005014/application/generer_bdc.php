<?php
/**
 * generer_bdc.php
 * Générateur de Bon de Commande (PDF) via TCPDF.
 * 
 * @project Antigravity
 * @version 1.0
 */

require_once 'auth.php'; // Securite
require_once 'db.php';
require_once 'functions.php';
require_once 'lib/tcpdf/tcpdf.php';

// Force UTF-8 (Le script output un PDF binaire, pas du HTML text)
// header('Content-Type: text/html; charset=utf-8'); // NON, TCPDF gère ses headers

// ID Commande
$cmd_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cmd_id === 0) die("ID Commande invalide.");

try {
    // 1. DATA FETCHING
    // Header
    $stmt = $pdo->prepare("
        SELECT c.*, f.nom as fournisseur_nom, f.email_commande, f.adresse_enlevement, 
               a.nom_affaire, a.numero_prodevis
        FROM commandes_achats c
        JOIN fournisseurs f ON c.fournisseur_id = f.id
        LEFT JOIN affaires a ON c.affaire_id = a.id
        WHERE c.id = ?
    ");
    $stmt->execute([$cmd_id]);
    $conf = $stmt->fetch();
    if (!$conf) die("Commande introuvable.");

    // Lignes
    $stmt = $pdo->prepare("
        SELECT l.*, ac.designation, ac.ref_fournisseur, fin.nom_couleur
        FROM lignes_achat l
        LEFT JOIN articles ac ON l.article_id = ac.id
        LEFT JOIN finitions fin ON l.finition_id = fin.id
        WHERE l.commande_id = ?
    ");
    $stmt->execute([$cmd_id]);
    $lignes = $stmt->fetchAll();

} catch (Exception $e) {
    die("Erreur SQL : " . $e->getMessage());
}

// 2. CONFIGURATION PDF (TCPDF)
class MYPDF extends TCPDF {
    public function Header() {
        // En-tête : Image
        // On force le chemin absolu propre
        $img_file = 'C:/laragon/www/antigravity/images/header_doc.jpg';
        
        // Image JPG convertie
        // Position X=10, Y=10, Largeur=90 (Un peu plus large)
        $this->Image($img_file, 10, 10, 90, '', 'JPG', '', 'T', false, 300, 'L', false, false, 0, false, false, false);
        
        // Trait de séparation Design
        $this->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        $this->Line(15, 45, 195, 45); // Ligne horizontale sous le header
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Init
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Meta
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Antigravity ERP');
$pdf->SetTitle('Bon de Commande ' . $conf['ref_interne']);
$pdf->SetSubject('Commande Fournisseur');

// Marges
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Page 1
$pdf->AddPage();

// --- CONTENU DU BDC ---

// Bloc Fournisseur (Droite)
// ON REMONTE LE BLOC au niveau du logo (Y=15)
// On décale plus à droite (X=130)
$pdf->SetXY(130, 15);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(65, 5, $conf['fournisseur_nom'], 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(130);
$pdf->MultiCell(65, 4, "Service Commandes\n" . ($conf['email_commande'] ?? ''), 0, 'L');

// DATE (Déplacée à droite sous le fournisseur)
$pdf->SetXY(130, 35);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(65, 5, 'Date : ' . date('d/m/Y', strtotime($conf['date_commande'])), 0, 1, 'L');


// Bloc Info Commande (Gauche)
// On descend pour laisser de l'espace (Y=55)
$pdf->SetXY(15, 55);

// TITRE REDUIT
$pdf->SetFont('helvetica', 'B', 12); 
$pdf->Cell(0, 8, 'BON DE COMMANDE : ' . $conf['ref_interne'], 0, 1, 'L');

// AFFAIRE AGRANDIE
$pdf->SetFont('helvetica', 'B', 14); // Plus gros pour l'affaire
$pdf->Cell(0, 8, 'Affaire : ' . ($conf['nom_affaire'] ?? 'STOCK'), 0, 1, 'L');

$pdf->SetFont('helvetica', '', 10); // Retour normal
$pdf->Cell(0, 6, 'Lieu de Livraison : ' . $conf['lieu_livraison'], 0, 1, 'L');

$pdf->Ln(5);

// TABLEAU LIGNES (HTML Simplifié)
$html_table = '
<table border="1" cellpadding="5" cellspacing="0">
    <tr style="background-color:#eee; font-weight:bold;">
        <th width="15%">Réf.</th>
        <th width="45%">Désignation</th>
        <th width="15%">Finition</th>
        <th width="10%" align="center">Qté</th>
        <th width="15%" align="right">Prix U. HT</th>
    </tr>';

$total_ht = 0;
foreach($lignes as $l) {
    if (!$l['designation_commerciale'] && $l['article_id']) {
        // Fallback nom article si vide dans ligne
        $l['designation_commerciale'] = "Article ID " . $l['article_id'];
    }
    
    $ligne_ht = $l['qte_commandee'] * $l['prix_unitaire_achat'];
    $total_ht += $ligne_ht;
    
    $html_table .= '
    <tr>
        <td>'. h($l['ref_fournisseur']) .'</td>
        <td>'. h($l['designation_commerciale']) .'</td>
        <td>'. h($l['nom_couleur']) .'</td>
        <td align="center">'. ($l['qte_commandee'] + 0) .'</td> <!-- +0 pour enlever decimales inutiles -->
        <td align="right">'. prix_fr($l['prix_unitaire_achat']) .'</td>
    </tr>';
}

$html_table .= '</table>';

$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML($html_table, true, false, false, false, '');

// TOTAL
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Total HT : ' . prix_fr($total_ht), 0, 1, 'R');

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 10);
$date_liv = !empty($conf['date_livraison_prevue']) ? date('d/m/Y', strtotime($conf['date_livraison_prevue'])) : 'Dès que possible';
$pdf->MultiCell(0, 5, "Merci de nous accuser réception de cette commande sous 48h.\nLivraison souhaitée pour le : " . $date_liv, 0, 'L');


// 3. SAUVEGARDE & SORTIE
$filename = 'BDC_' . safe_filename($conf['ref_interne']) . '.pdf';
$rel_path = 'uploads/BDC/' . $filename;
$abs_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'BDC' . DIRECTORY_SEPARATOR . $filename;

// Sauvegarde fichier physique
$pdf->Output($abs_path, 'F');

// Update DB
try {
    $stmt = $pdo->prepare("UPDATE commandes_achats SET chemin_pdf_bdc = ? WHERE id = ?");
    $stmt->execute([$rel_path, $cmd_id]); // On stocke le chemin relatif pour la portabilité
} catch (Exception $e) {
    // Silent error on update
}

// Affichage Navigateur (Seulement si PAS en mode silence)
if (!defined('MODE_SILENT_PDF')) {
    $pdf->Output($filename, 'I');
} else {
    $pdf->Output($abs_path, 'F'); // Force save only
}
?>
