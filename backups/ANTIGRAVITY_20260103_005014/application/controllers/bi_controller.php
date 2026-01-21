<?php
declare(strict_types=1);

/**
 * controllers/bi_controller.php
 * Logique métier pour le Module Business Intelligence
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../db.php';
}

$year = (int)($_GET['year'] ?? date('Y'));

// 1. CHIFFRES CLÉS (KPIs)
// CA Signé (Somme des affaires signées cette année)
$sql_ca = "SELECT SUM(montant_ht) FROM affaires WHERE YEAR(date_signature) = ? AND statut IN ('Signé', 'Terminé', 'Facturé')";
$stmt = $pdo->prepare($sql_ca);
$stmt->execute([$year]);
$ca_annuel = (float)($stmt->fetchColumn() ?: 0);

// Achats Engagés (Somme des lignes d'achat pour commandes de cette année)
$sql_achats = "SELECT SUM(la.prix_unitaire_achat * la.qte_commandee) 
               FROM lignes_achat la
               JOIN commandes_achats ca ON la.commande_id = ca.id
               WHERE YEAR(ca.date_commande) = ? AND ca.statut != 'Annulée'";
$stmt = $pdo->prepare($sql_achats);
$stmt->execute([$year]);
$achats_annuel = (float)($stmt->fetchColumn() ?: 0);

// Marge
$marge_brute = $ca_annuel - $achats_annuel;
$taux_marge = ($ca_annuel > 0) ? ($marge_brute / $ca_annuel) * 100 : 0.0;

// 2. ÉVOLUTION MENSUELLE LE (CA vs ACHATS)
$mois_stats = [];

// Optimisation : Une seule requête pour tout récupérer groupé par mois
$sql_monthly = "
    SELECT 
        MONTH(a.date_signature) as m, 
        SUM(a.montant_ht) as ca
    FROM affaires a
    WHERE YEAR(a.date_signature) = ? AND a.statut IN ('Signé', 'Terminé', 'Facturé')
    GROUP BY m
";
$data_ca = $pdo->prepare($sql_monthly);
$data_ca->execute([$year]);
$map_ca = $data_ca->fetchAll(PDO::FETCH_KEY_PAIR); // [1 => 1000, 2 => 2000]

$sql_monthly_achat = "
    SELECT 
        MONTH(ca.date_commande) as m,
        SUM(la.prix_unitaire_achat * la.qte_commandee) as achat
    FROM lignes_achat la
    JOIN commandes_achats ca ON la.commande_id = ca.id
    WHERE YEAR(ca.date_commande) = ? AND ca.statut != 'Annulée'
    GROUP BY m
";
$data_achat = $pdo->prepare($sql_monthly_achat);
$data_achat->execute([$year]);
$map_achat = $data_achat->fetchAll(PDO::FETCH_KEY_PAIR);

for ($m = 1; $m <= 12; $m++) {
    $mois_stats[] = [
        'mois' => $m,
        'ca' => (float)($map_ca[$m] ?? 0),
        'achat' => (float)($map_achat[$m] ?? 0)
    ];
}

// 3. TOP 5 FOURNISSEURS
$sql_top_fourn = "SELECT f.nom, SUM(la.prix_unitaire_achat * la.qte_commandee) as total_achat
                  FROM lignes_achat la
                  JOIN commandes_achats ca ON la.commande_id = ca.id
                  JOIN fournisseurs f ON ca.fournisseur_id = f.id
                  WHERE YEAR(ca.date_commande) = ? AND ca.statut != 'Annulée'
                  GROUP BY f.id, f.nom
                  ORDER BY total_achat DESC
                  LIMIT 5";
$stmt = $pdo->prepare($sql_top_fourn);
$stmt->execute([$year]);
$top_fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. RÉPARTITION PAR FAMILLE (Barres Aluminium / Quincaillerie / Moteurs...)
$sql_repartition = "SELECT fam.nom_famille, SUM(la.prix_unitaire_achat * la.qte_commandee) as total
                    FROM lignes_achat la
                    JOIN articles_catalogue ac ON la.ref_fournisseur = ac.ref_fournisseur 
                    LEFT JOIN familles fam ON ac.famille_id = fam.id
                    JOIN commandes_achats ca ON la.commande_id = ca.id
                    WHERE YEAR(ca.date_commande) = ? AND ca.statut != 'Annulée'
                    GROUP BY fam.id, fam.nom_famille
                    ORDER BY total DESC
                    LIMIT 6";

try {
    $stmt = $pdo->prepare($sql_repartition);
    $stmt->execute([$year]);
    $repartition_familles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $repartition_familles = [];
}

// 5. TOP 10 CLIENTS (par CA)
$sql_top_clients = "SELECT c.nom_principal, c.prenom, COUNT(DISTINCT a.id) as nb_affaires, SUM(a.montant_ht) as ca_total
                    FROM clients c
                    LEFT JOIN affaires a ON c.id = a.client_id
                    WHERE YEAR(a.date_signature) = ? AND a.statut IN ('Signé', 'Terminé', 'Facturé')
                    GROUP BY c.id, c.nom_principal, c.prenom
                    ORDER BY ca_total DESC
                    LIMIT 10";
$stmt = $pdo->prepare($sql_top_clients);
$stmt->execute([$year]);
$top_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. AFFAIRES PAR STATUT (pour graphique)
$sql_affaires_statut = "SELECT statut, COUNT(*) as nombre
                        FROM affaires
                        WHERE YEAR(date_creation) = ?
                        GROUP BY statut";
$stmt = $pdo->prepare($sql_affaires_statut);
$stmt->execute([$year]);
$affaires_statut = $stmt->fetchAll(PDO::FETCH_ASSOC);
