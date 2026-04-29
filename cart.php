<?php
session_start();
require_once 'controllers/CartController.php';

$cartController = new CartController();
$cartData = $cartController->getCartData();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Cart - R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Shopping Cart</h2>
            <a href="index.php" class="btn btn-outline-success">← Continue Shopping</a>
        </div>
        
        <?php if(empty($cartData['items'])): ?>
            <div class="alert alert-info text-center p-5">
                <h4>Cart is empty</h4>
                <p>Add some fresh products!</p>
                <a href="index.php" class="btn btn-success">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <?php foreach($cartData['items'] as $item): ?>
                    <div class="card mb-3">
                        <div class="row g-0">
                            <div class="col-md-3">
                                <img src="<?= $item['image'] ?? 'placeholder.jpg' ?>" class="img-fluid rounded-start" style="height: 150px; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                    <p class="card-text">₱<?= number_format($item['price'], 2) ?> each</p>
                                    <div class="d-flex align-items-center">
                                        <form method="POST" action="controllers/CartController.php?action=update" class="me-2">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 80px; display: inline-block;">
                                            <button type="submit" class="btn btn-sm btn-info">Update</button>
                                        </form>
                                        <form method="POST" action="controllers/CartController.php?action=remove">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </div>
                                    <p class="mt-2"><strong>Subtotal: ₱<?= number_format($item['subtotal'], 2) ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <p>Items: <?= count($cartData['items']) ?></p>
                            <hr>
                            <h5>Total: ₱<?= number_format($cartData['total'], 2) ?></h5>
                            <button class="btn btn-success w-100 mt-3" onclick="alert('Checkout coming soon!')">Proceed to Checkout</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>