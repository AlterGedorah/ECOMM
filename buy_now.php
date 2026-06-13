<?php
require_once __DIR__ . '/controllers/CheckoutController.php';

$controller = new CheckoutController();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));

if ($productId < 1) {
    $_SESSION['error'] = "Invalid product.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit();
}

$result = $controller->buyNow($productId, $quantity);

if (!$result['success']) {
    $_SESSION['error'] = $result['error'];
    if (stripos($result['error'], 'log in') !== false) {
        header("Location: pages/login.php");
    } else {
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    }
    exit();
}

header("Location: checkout.php");
exit();
?>
