<?php
require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

class CommandeExpressPDF extends TCPDF {

    private $commandeData;
    private $supplierData;

    public function __construct($commandeData, $supplierData) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $this->commandeData = $commandeData;
        $this->supplierData = $supplierData;

        // Meta
        $this->SetCreator('Antigravity ERP');
        $this->SetAuthor('Antigravity');
        $this->SetTitle('Commande Express - ' . $commandeData['id']);
        
        // No default header/footer (custom implemented)
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);

        // Margins
        $this->SetMargins(10, 40, 10);
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(10);
        
        // Auto page break
        $this->SetAutoPageBreak(TRUE, 20);
    }

    public function Header() {
        // Logo
        $image_file = __DIR__ . '/../images/header_doc.png'; // Correct path found via search
        if (file_exists($image_file)) {
            $this->Image($image_file, 10, 5, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            $this->SetFont('helvetica', 'B', 20);
            $this->Cell(40, 10, 'ARTSALU', 0, 0);
        }

        // Supplier Info Box (Right)
        $this->SetXY(110, 10);
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(90, 6, "DESTINATAIRE : " . strtoupper($this->supplierData['nom']), 0, 1, 'L');
        $this->SetX(110);
        $this->SetFont('helvetica', '', 10);
        // Multicell for address if available (placeholder for now)
        $this->MultiCell(90, 15, "Commande transmise via Antigravity\nDate : " . date('d/m/Y H:i'), 0, 'L');

        // Title
        $this->SetXY(10, 25);
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'BON DE COMMANDE EXPRESS N° ' . $this->commandeData['id'], 0, 1, 'C');
        
        // Line
        $this->Line(10, 38, 200, 38);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        
        $imputation = $this->commandeData['imputation_type'];
        $ref = $this->commandeData['imputation_ref'] ?? 'N/A';
        
        $footerText = "Généré par Antigravity | Imputation : $imputation ($ref) | Page " . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->Cell(0, 10, $footerText, 0, 0, 'C');
    }

    public function renderBody() {
        $module = $this->commandeData['module_type'];
        $details = $this->commandeData['details']; // JSON Object already decoded

        $this->AddPage();
        $this->SetFont('helvetica', '', 11);

        // Common Info
        $this->SetFillColor(240, 240, 240);
        $this->Cell(0, 8, "TYPE DE COMMANDE : " . $module, 1, 1, 'L', 1);
        $this->Ln(5);

        switch ($module) {
            case 'VITRAGE':
                $this->renderVitrage($details);
                break;
            case 'PLIAGE':
                $this->renderPliage($details);
                break;
            case 'PROFIL':
                $this->renderProfil($details);
                break;
            case 'PANNEAUX':
                $this->renderPanneaux($details);
                break;
            case 'QUINCAILLERIE':
                $this->renderQuincaillerie($details);
                break;
            case 'LIBRE':
                $this->renderLibre($details);
                break;
        }
        
        // Comments / Notes
        if (!empty($details->notes) || !empty($this->commandeData['commentaire'])) {
            $this->Ln(10);
            $this->SetFont('helvetica', 'B', 10);
            $this->Cell(0, 6, "NOTES / COMMENTAIRES :", 0, 1);
            $this->SetFont('helvetica', '', 10);
            $comment = !empty($details->notes) ? $details->notes : $this->commandeData['commentaire'];
            $this->MultiCell(0, 20, $comment, 1, 'L');
        }
    }

    // Helper for defensive coding
    private function safe($d, $prop, $default = 'N/A') {
        return isset($d->$prop) && $d->$prop !== '' ? $d->$prop : $default;
    }

    private function renderVitrage($d) {
        $comp = $this->safe($d, 'composition');
        $opt = $this->safe($d, 'options', '');
        $forme = $this->safe($d, 'forme', 'RECTANGLE');
        $larg = $this->safe($d, 'largeur', 0);
        $haut = $this->safe($d, 'hauteur', 0);
        $qty = $this->safe($d, 'quantite', 1);
        $poids = $this->safe($d, 'poids', '-');

        $html = "
        <h3>Détails du Vitrage</h3>
        <table border=\"1\" cellpadding=\"5\">
            <tr style=\"background-color:#eee; font-weight:bold;\">
                <th width=\"40%\">Description</th>
                <th width=\"20%\">Type</th>
                <th width=\"20%\">Dimensions</th>
                <th width=\"10%\">Qté</th>
                <th width=\"10%\">Poids/U</th>
            </tr>
            <tr>
                <td>{$comp} - {$opt}</td>
                <td>{$forme}</td>
                <td>{$larg} x {$haut} mm</td>
                <td><strong>{$qty}</strong></td>
                <td>{$poids} kg</td>
            </tr>
        </table>";
        $this->writeHTML($html);
    }

    private function renderPliage($d) {
        $this->SetFont('helvetica', 'B', 12);
        // Fallback for color/ral mismatch
        $couleur = $d->couleur ?? ($d->ral ?? 'N/A');
        $matiere = $this->safe($d, 'matiere');
        $quantite = $this->safe($d, 'quantite', 1);

        $this->Cell(0, 10, "Croquis de Pliage ({$matiere} - {$couleur})", 0, 1);
        
        $this->SetFont('helvetica', '', 11);
        $this->Cell(50, 6, "Quantité : " . $quantite, 0, 1);
        $this->Ln(5);

        // Image Canvas
        if (!empty($d->canvas_image)) {
            // Remove data:image/png;base64,
            $imgdata = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $d->canvas_image));
            
            // Embed image using stream (TCPDF special @ syntax)
            $this->Image('@'.$imgdata, 15, '', 180, '', 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
        } else {
            $this->Cell(0, 10, "[Erreur: Image manquant]", 1, 1, 'C');
        }
    }

    private function renderProfil($d) {
        // Bicoloration Logic
        $finish = "";
        if (isset($d->is_bicoloration) && $d->is_bicoloration === 'true') {
             $int = $this->safe($d, 'couleur_int');
             $ext = $this->safe($d, 'couleur_ext');
             $finish = "BICOLORE (Int: {$int} / Ext: {$ext})";
        } else {
             $finish = "MONOCOLORE: " . $this->safe($d, 'couleur_mono');
        }

        $fournisseur = $this->safe($d, 'fournisseur', 'Inconnu');
        $ref = $this->safe($d, 'reference');
        $des = $this->safe($d, 'designation');
        $cond = $this->safe($d, 'type_cond', 'Barre');
        $cond_txt = $cond == 'COUPE' ? 'Coupe: '.$this->safe($d, 'longueur_sav', 0).'mm' : 'Barre';
        $qty = $this->safe($d, 'quantite', 1);

        $html = "
        <h3>Profil Alu - {$fournisseur}</h3>
        <table border=\"1\" cellpadding=\"5\">
            <tr style=\"background-color:#eee; font-weight:bold;\">
                <th>Référence</th>
                <th>Désignation</th>
                <th>Finition</th>
                <th>Conditionnement</th>
                <th>Qté</th>
            </tr>
            <tr>
                <td>{$ref}</td>
                <td>{$des}</td>
                <td>{$finish}</td>
                <td>{$cond_txt}</td>
                <td>{$qty}</td>
            </tr>
        </table>";
        $this->writeHTML($html);
    }

    private function renderPanneaux($d) {
        $usage = $this->safe($d, 'usage');
        $ep = $this->safe($d, 'epaisseur', 0);
        $perf = $this->safe($d, 'perf_type');
        $ext = $this->safe($d, 'face_ext');
        $int = $this->safe($d, 'face_int');
        $mode = $this->safe($d, 'mode_decoupe');
        $dim = $mode == 'CUSTOM' ? "Sur Mesure: ".$this->safe($d, 'largeur')." x ".$this->safe($d, 'longueur')." mm" : "Plaque Complète";
        $qty = $this->safe($d, 'quantite', 1);

        $html = "
        <h3>Panneaux Sandwich</h3>
        <table border=\"1\" cellpadding=\"5\">
            <tr>
                <td width=\"30%\" style=\"background-color:#eee;\"><strong>Usage</strong></td>
                <td width=\"70%\">{$usage} - Ep. {$ep} mm ({$perf})</td>
            </tr>
            <tr>
                <td style=\"background-color:#eee;\"><strong>Composition</strong></td>
                <td>Ext: {$ext} / Int: {$int}</td>
            </tr>
            <tr>
                <td style=\"background-color:#eee;\"><strong>Dimensions</strong></td>
                <td>{$dim}</td>
            </tr>
            <tr>
                <td style=\"background-color:#eee;\"><strong>Quantité</strong></td>
                <td>{$qty}</td>
            </tr>
        </table>";
        $this->writeHTML($html);
    }

    private function renderQuincaillerie($d) {
        $cat = $this->safe($d, 'fournisseur_cat', 'Catalogue');
        
        $html = "
        <h3>Liste Quincaillerie - {$cat}</h3>
        <table border=\"1\" cellpadding=\"5\">
            <tr style=\"background-color:#eee; font-weight:bold;\">
                <th width=\"20%\">Réf.</th>
                <th width=\"50%\">Désignation</th>
                <th width=\"15%\">Qté</th>
                <th width=\"15%\">Unité</th>
            </tr>";
        
        if (isset($d->lines) && is_array($d->lines)) {
            foreach ($d->lines as $line) {
                // Defensive for lines too, treating them as objects or arrays? Usually json_decode makes objects
                $l_ref = is_object($line) ? ($line->ref ?? '') : ($line['ref'] ?? '');
                $l_des = is_object($line) ? ($line->designation ?? '') : ($line['designation'] ?? '');
                $l_qty = is_object($line) ? ($line->qty ?? 0) : ($line['qty'] ?? 0);
                $l_unit = is_object($line) ? ($line->unit ?? 'U') : ($line['unit'] ?? 'U');

                $html .= "
                <tr>
                    <td>{$l_ref}</td>
                    <td>{$l_des}</td>
                    <td>{$l_qty}</td>
                    <td>{$l_unit}</td>
                </tr>";
            }
        } else {
             $html .= "<tr><td colspan=\"4\">Aucune ligne définie.</td></tr>";
        }

        $html .= "</table>";
        $this->writeHTML($html);
    }

    private function renderLibre($d) {
        $this->SetFont('helvetica', '', 12);
        
        $fourn = $this->safe($d, 'fournisseur_id', 'Non spécifié');
        $txt = $this->safe($d, 'details_texte', '');

        $html = "
        <h3>Demande Spéciale (Texte Libre)</h3>
        <p><strong>Fournisseur Cible :</strong> {$fourn}</p>
        <hr>
        <div style=\"border:1px solid #ccc; padding:10px;\">
            " . nl2br($txt) . "
        </div>";
        
        $this->writeHTML($html);

        if (!empty($d->has_attachment)) {
            $this->Ln(10);
            $this->Cell(0, 10, "Note: Une pièce jointe a été fournie avec cette commande.", 0, 1, 'I');
        }
    }
}
