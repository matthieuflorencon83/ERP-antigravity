<?php
// tools/inspect_last_order_details.php
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query("SELECT id, module_type, details_json FROM commandes_express ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "Last Order ID: " . $row['id'] . "\n";
        echo "Module: " . $row['module_type'] . "\n";
        $details = json_decode($row['details_json'], true);
        
        if (isset($details['canvas_image'])) {
            echo "Canvas Image: Present (Length: " . strlen($details['canvas_image']) . ")\n";
            echo "Start of data: " . substr($details['canvas_image'], 0, 50) . "...\n";
        } else {
            echo "Canvas Image: MISSING\n";
        }
        print_r($details);
    } else {
        echo "No orders found.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
