<?php
// tools/test_besoins_engines.php
require_once __DIR__ . '/../core/BarOptimization.php';
require_once __DIR__ . '/../core/VerificationEngine.php';

echo "--- TEST: BarOptimization ---\n";
$optimizer = new BarOptimization();
$stock = [6000, 6500, 7000, 4700];

// Case 1: Need 5000mm -> Should pick 6000mm
$res1 = $optimizer->optimize(5000, $stock);
echo "Need 5000 | Available [4700, 6000, 6500, 7000] -> Picked: " . $res1['recommended_bar'] . " (Waste: {$res1['waste_percent']}%) - " . ($res1['recommended_bar'] == 6000 ? "PASS" : "FAIL") . "\n";

// Case 2: Need 6100mm -> Should pick 6500mm
$res2 = $optimizer->optimize(6100, $stock);
echo "Need 6100 | Available [4700, 6000, 6500, 7000] -> Picked: " . $res2['recommended_bar'] . " (Waste: {$res2['waste_percent']}%) - " . ($res2['recommended_bar'] == 6500 ? "PASS" : "FAIL") . "\n";

// Case 3: Need 8000mm -> ERROR
$res3 = $optimizer->optimize(8000, $stock);
echo "Need 8000 | Available [...] -> Status: " . $res3['status'] . " - " . ($res3['status'] == 'ERROR' ? "PASS" : "FAIL") . "\n";


echo "\n--- TEST: VerificationEngine ---\n";
$verifier = new VerificationEngine();

// Case 1: Chevron 4000mm (OK)
$line1 = ['type' => 'CHEVRON', 'longueur_brute' => 3000, 'renfort_acier' => 0];
$alerts1 = $verifier->checkConsistency($line1);
echo "Chevron 3m (No Steel) -> Alerts: " . count($alerts1) . " - " . (count($alerts1) == 0 ? "PASS" : "FAIL") . "\n";

// Case 2: Chevron 4000mm (WARNING > 3500)
$line2 = ['type' => 'CHEVRON', 'longueur_brute' => 4000, 'renfort_acier' => 0];
$alerts2 = $verifier->checkConsistency($line2);
echo "Chevron 4m (No Steel) -> Alerts: " . count($alerts2) . " - " . (count($alerts2) > 0 ? "PASS" : "FAIL") . "\n";
if (count($alerts2) > 0) echo "  MSG: " . $alerts2[0]['msg'] . "\n";

// Case 3: Chevron 4000mm WITH Steel (OK)
$line3 = ['type' => 'CHEVRON', 'longueur_brute' => 4000, 'renfort_acier' => 1];
$alerts3 = $verifier->checkConsistency($line3);
echo "Chevron 4m (WITH Steel) -> Alerts: " . count($alerts3) . " - " . (count($alerts3) == 0 ? "PASS" : "FAIL") . "\n";
