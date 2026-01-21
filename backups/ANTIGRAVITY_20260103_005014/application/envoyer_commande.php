<?php
/**
 * envoyer_commande.php
 * Envoi du BDC par Email via PHPMailer (Transport Mail/Sendmail).
 */
require_once 'auth.php';
require_once 'db.php';
require_once 'functions.php';
require_once 'email_config.php'; 

// Namespaces (Assurez-vous qu'ils sont chargés dans email_config ou l'autoloader)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Si pas d'ID, erreur
$cmd_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cmd_id === 0) die("ID Commande manquant.");

// 1. Récupération Infos Email
$stmt = $pdo->prepare("
    SELECT c.*, f.nom as fournisseur_nom, f.email_commande 
    FROM commandes_achats c 
    JOIN fournisseurs f ON c.fournisseur_id = f.id 
    WHERE c.id = ?
");
$stmt->execute([$cmd_id]);
$cmd = $stmt->fetch();

if (!$cmd) die("Commande introuvable.");

$destinataire = $cmd['email_commande'];
if (empty($destinataire)) {
    die("Erreur: Aucun email configuré pour le fournisseur " . htmlspecialchars($cmd['fournisseur_nom']));
}

// 2. Génération PDF
require_once 'lib/tcpdf/tcpdf.php'; 

ob_start();
$_GET['id'] = $cmd_id;
define('MODE_SILENT_PDF', true);
require_once 'generer_bdc.php';
ob_end_clean();

// Check Chemin PDF
$stmt = $pdo->prepare("SELECT chemin_pdf_bdc, ref_interne FROM commandes_achats WHERE id = ?");
$stmt->execute([$cmd_id]);
$cmd2 = $stmt->fetch();
$file_path = $cmd2['chemin_pdf_bdc'];

if (!file_exists($file_path)) {
    die("Erreur: PDF non généré.");
}

// 3. Envoi via PHPMailer
$mail = new PHPMailer(true);

try {
    // IMPORTANT : On utilise le mode "Mail" natif pour bypasser le blocage SMTP
    // Cela utilisera la config php.ini de Laragon qui marche déjà
    // Tout en gardant la puissance de PHPMailer pour les pièces jointes
    $mail->isMail(); 

    // Encodage
    $mail->CharSet = 'UTF-8';

    // Expéditeur / Destinataire
    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    $mail->addAddress($destinataire);

    // Pièce jointe
    $mail->addAttachment($file_path);

    // Contenu
    $mail->isHTML(false); 
    $mail->Subject = "Commande N° " . $cmd['ref_interne'] . " - ARTS ALU";
    $mail->Body    = "Bonjour,\n\nVeuillez trouver ci-joint notre bon de commande N° " . $cmd['ref_interne'] . ".\n\nMerci de nous en accuser bonne réception.\n\nCordialement,\n\nLe Service Achats\nARTS ALU";

    $mail->send();
    
    // Succès
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#e6fffa;'>";
    echo "<h1 style='color:green;'>✅ Email envoyé via PHPMailer !</h1>";
    echo "<p>(Mode Transport: Native Mail)</p>";
    echo "<p>Destinataire : <strong>$destinataire</strong></p>";
    echo "<a href='commandes_detail.php?id=$cmd_id' style='padding:10px 20px; background:#0f4c75; color:white; text-decoration:none; border-radius:5px;'>Retour Commande</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#fff5f5;'>";
    echo "<h1 style='color:red;'>❌ Erreur Envoi</h1>";
    echo "<p>Erreur PHPMailer : {$mail->ErrorInfo}</p>";
    echo "<a href='commandes_detail.php?id=$cmd_id'>Retour</a>";
    echo "</div>";
}


