<?php
/**
 * Endpoint for programmatic updates to the user's cookie consent.
 *
 * Accepts a POST request with a `consent` field that is either
 * "accepted" or "rejected" and mirrors that value into a first-party
 * cookie (cookie_consent) with a 180-day expiry.
 *
 * Usage example (from the browser):
 *   fetch('cookie_consent_handler.php', {
 *       method: 'POST',
 *       headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
 *       body: 'consent=accepted'
 *   });
 */
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

$expire = time() + (180 * 24 * 60 * 60); // 180 days
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

// SameSite=Lax; path=/ so the preference applies to the entire site.
setcookie('cookie_consent', $consent, [
    'expires'  => $expire,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => false, // we want JS to be able to read it
    'samesite' => 'Lax',
]);

echo json_encode([
    'success' => true,
    'consent' => $consent,
    'expires' => $expire,
]);
