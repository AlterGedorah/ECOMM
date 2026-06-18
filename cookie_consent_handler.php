<?php
header('Content-Type: application/json');

$valid = ['accepted', 'rejected'];
$consent = $_POST['consent'] ?? $_GET['consent'] ?? null;

if (!in_array($consent, $valid, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid consent value. Must be "accepted" or "rejected".',
    ]);
    exit;
}

$expire = time() + (180 * 24 * 60 * 60); 
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

setcookie('cookie_consent', $consent, [
    'expires'  => $expire,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => false, 
    'samesite' => 'Lax',
]);

echo json_encode([
    'success' => true,
    'consent' => $consent,
    'expires' => $expire,
]);
