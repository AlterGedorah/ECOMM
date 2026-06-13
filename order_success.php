<?php
session_start();
require_once 'models/Order.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_order'])) {
    header("Location: index.php");
    exit();
}

$orderModel = new Order();
$order = $orderModel->getOrderWithItems(
    (int)$_SESSION['last_order']['order_id'],
    (int)$_SESSION['user_id']
);

if (!$order) {
    header("Location: index.php");
    exit();
}

// Keep a copy for the view, then clear the flash
$lastOrder = $_SESSION['last_order'];
unset($_SESSION['last_order']);

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — Fresh Harvest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
    <style>
        body { background:#F5F2EA; font-family:'Segoe UI',system-ui,sans-serif; }
        .success-card { max-width:640px; margin:5rem auto; background:#fff; border-radius:18px; box-shadow:0 10px 40px rgba(0,0,0,.06); overflow:hidden; }
        .success-header { background:linear-gradient(135deg,#1E3028,#2F4A3A); color:#fff; padding:2.5rem 2rem; text-align:center; }
        .check-circle { width:84px; height:84px; border-radius:50%; background:#6BAA7A; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; color:#fff; font-size:2.5rem; }
        .info-row { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid #f0eee9; font-size:.9rem; }
        .info-row:last-child { border-bottom:none; }
        .info-row .lbl { color:#6B7E72; }
        .info-row .val { font-weight:600; color:#1C2B22; }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-header">
            <div class="check-circle"><i class="bi bi-check2"></i></div>
            <h2 class="mb-1">Order Confirmed</h2>
            <p class="mb-0 opacity-75">Thank you for shopping with Fresh Harvest</p>
        </div>
        <div class="p-4">
            <div class="info-row">
                <span class="lbl">Order number</span>
                <span class="val"><?= htmlspecialchars($order['order_number']) ?></span>
            </div>
            <div class="info-row">
                <span class="lbl">Payment method</span>
                <span class="val text-capitalize"><?= htmlspecialchars(str_replace('_',' ',$order['payment_method'])) ?></span>
            </div>
            <div class="info-row">
                <span class="lbl">Shipping to</span>
                <span class="val text-end" style="max-width:60%;"><?= htmlspecialchars($order['street_address']) ?></span>
            </div>
            <div class="info-row">
                <span class="lbl">Estimated delivery</span>
                <span class="val"><?= htmlspecialchars($order['estimated_delivery_date'] ?? '3–5 business days') ?></span>
            </div>

            <h6 class="mt-4 mb-2 fw-bold">Items</h6>
            <table class="table table-sm align-middle">
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td class="text-center text-muted">×<?= (int)$item['quantity'] ?></td>
                        <td class="text-end fw-semibold">₱<?= number_format($item['total_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end">Total paid</th>
                        <th class="text-end text-success">₱<?= number_format($order['total_amount'], 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="d-flex gap-2 mt-3">
                <a href="index.php" class="btn btn-success flex-fill">
                    <i class="bi bi-house me-1"></i> Back to Home
                </a>
                <a href="profile.php" class="btn btn-outline-secondary flex-fill">
                    <i class="bi bi-bag me-1"></i> View Orders
                </a>
            </div>
        </div>
    </div>
</body>
</html>
