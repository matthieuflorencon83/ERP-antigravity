<?php
declare(strict_types=1);

/**
 * mcp_bridge.php - Bridge API pour Gemini
 * Version 2.0 - Support complet des outils MCP
 */

header('Content-Type: application/json');
require_once '../db.php';

$action = $_GET['action'] ?? '';

// Helper: Charger une variable .env manuellement
function getEnvValue(string $key): string {
    $envPath = __DIR__ . '/../mcp-server/.env';
    if (!file_exists($envPath)) return '';
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        if (trim($name) === $key) return trim($value);
    }
    return '';
}

try {
    switch ($action) {
        // ----------------------------------------------------------------
        // Tool A : Database Schema
        // ----------------------------------------------------------------
        case 'query_schema':
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $schema = [];
            foreach ($tables as $table) {
                $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
                $schema[$table] = $columns;
            }
            echo json_encode([
                'success' => true, 
                'tables' => $tables, 
                'schema' => $schema
            ], JSON_PRETTY_PRINT);
            break;

        // ----------------------------------------------------------------
        // Tool B : Safe SQL
        // ----------------------------------------------------------------
        case 'execute_sql':
            $query = $_GET['query'] ?? '';
            if (!preg_match('/^\s*SELECT/i', $query)) {
                throw new Exception('Only SELECT queries allowed');
            }
            $stmt = $pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'rowCount' => count($results), 'data' => $results]);
            break;

        // ----------------------------------------------------------------
        // Tool C : Code Reader
        // ----------------------------------------------------------------
        case 'read_file':
            $filepath = $_GET['filepath'] ?? '';
            if (strpos($filepath, '..') !== false) throw new Exception('Path traversal detected');
            $fullPath = __DIR__ . '/../' . $filepath;
            if (!file_exists($fullPath)) throw new Exception('File not found');
            echo json_encode(['success' => true, 'content' => file_get_contents($fullPath)]);
            break;

        // ----------------------------------------------------------------
        // Tool D : Error Logs (V2 - Centralized)
        // ----------------------------------------------------------------
        case 'read_logs':
            $lines = (int)($_GET['lines'] ?? 50);
            $logPath = __DIR__ . '/../antigravity_errors.log';
            if (!file_exists($logPath)) throw new Exception('Log file not found');
            $content = file_get_contents($logPath);
            $allLines = explode("\n", $content);
            $lastLines = array_slice($allLines, -$lines);
            echo json_encode(['success' => true, 'logs' => $lastLines]);
            break;

        // ----------------------------------------------------------------
        // Tool E : Météo Chantier (Site Foreman)
        // ----------------------------------------------------------------
        case 'check_weather':
            $lat = $_GET['lat'] ?? '';
            $lon = $_GET['lon'] ?? '';
            $date = $_GET['date'] ?? date('Y-m-d');
            $apiKey = getEnvValue('OPENWEATHER_API_KEY');

            if (!$apiKey) throw new Exception('OPENWEATHER_API_KEY not configured');

            $url = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$apiKey&units=metric";
            $response = @file_get_contents($url);
            
            if (!$response) throw new Exception('Weather API call failed');
            
            $data = json_decode($response, true);
            $risks = [];
            $windMax = 0;
            
            foreach ($data['list'] as $item) {
                if (strpos($item['dt_txt'], $date) === 0) {
                    $weatherId = $item['weather'][0]['id'];
                    $wind = $item['wind']['speed'] * 3.6; // m/s to km/h
                    if ($wind > $windMax) $windMax = $wind;
                    
                    if ($weatherId >= 200 && $weatherId < 600) $risks[] = "PLUIE/ORAGE";
                    if ($weatherId >= 600 && $weatherId < 700) $risks[] = "NEIGE";
                }
            }
            if ($windMax > 50) $risks[] = "VENT FORT (>50km/h)";
            
            $status = !empty($risks) ? "CHANTIER À RISQUE" : "Conditions OK";
            echo json_encode(['success' => true, 'status' => $status, 'details' => array_unique($risks), 'max_wind' => $windMax]);
            break;

        // ----------------------------------------------------------------
        // Tool F : Estimate Duration (Site Foreman)
        // ----------------------------------------------------------------
        case 'estimate_duration':
            $surface = (float)($_GET['surface'] ?? 0);
            $difficulty = (int)($_GET['difficulty'] ?? 1);
            $installers = (int)($_GET['installers'] ?? 1);
            
            if ($installers < 1) $installers = 1;
            
            // Formula: (Surface * 1.5h) * (Difficulty / 2) / Installers
            $hours = ($surface * 1.5 * ($difficulty / 2)) / $installers;
            $days = ceil($hours / 7); // 7h per day
            
            echo json_encode(['success' => true, 'hours' => $hours, 'days' => $days]);
            break;

        // ----------------------------------------------------------------
        // Tool G : Take Screenshot (Visual Inspector)
        // ----------------------------------------------------------------
        case 'take_screenshot':
            $url = $_GET['url'] ?? '';
            $device = $_GET['device'] ?? 'desktop';
            
            // Validation basique
            if (!filter_var($url, FILTER_VALIDATE_URL)) throw new Exception('Invalid URL');
            if (strpos($url, 'localhost') === false && strpos($url, '127.0.0.1') === false) {
                throw new Exception('Only localhost URLs allowed');
            }

            $scriptPath = __DIR__ . '/../tools/screenshot_cli.js';
            $nodeCmd = "node \"$scriptPath\" \"$url\" \"$device\"";
            
            // Exécution (c'est synchrone PHP, donc ça peut prendre qques secondes)
            $output = [];
            $resultCode = 0;
            exec($nodeCmd, $output, $resultCode);
            
            $jsonResult = implode("\n", $output);
            $decoded = json_decode($jsonResult, true);
            
            if ($decoded) {
                echo $jsonResult; // Déjà JSON
            } else {
                throw new Exception("Screenshot failed: " . json_encode($output));
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
