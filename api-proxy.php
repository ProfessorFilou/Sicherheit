<?php
/**
 * Mini-Proxy für Breach-Checker (CORS-Umgehung)
 * Nur erlaubte Ziele: LeakCheck Public API
 *
 * Aufruf: api-proxy.php?q=email@example.com
 * Optional: api-proxy.php?service=leakcheck&q=...
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '' || strlen($q) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parameter q fehlt oder zu kurz (min. 3 Zeichen)']);
    exit;
}

// Nur erlaubte Zeichen (E-Mail / Username)
if (!preg_match('/^[a-zA-Z0-9._%+\-@]+$/', $q)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Zeichen in q']);
    exit;
}

$url = 'https://leakcheck.io/api/public?check=' . rawurlencode($q);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_CONNECTTIMEOUT => 6,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: ProfessorFilou-BreachChecker/1.0',
    ],
]);

$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($body === false || $code === 0) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Upstream nicht erreichbar', 'detail' => $err]);
    exit;
}

http_response_code($code > 0 ? $code : 502);
echo $body;
