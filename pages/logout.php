<?php
require_once __DIR__ . '/../controllers/AuthHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// AuthHelper::clearAuthState() removes every auth cookie (only those that
// actually exist — harmless when they don't), empties $_SESSION and
// destroys the session storage. It does NOT clear cookie_consent, so the
// user's overall cookie preference persists across logouts.
AuthHelper::clearAuthState();

header("Location: /Fresh Harvest/index.php");
exit();
?>  