<?php
session_set_cookie_params(0);
session_start();
require_once 'models/Cart.php';
require_once 'models/Product.php';

// Initialize cart and product
$cart = new Cart();
$product_model = new Product();

// Process checkout
if (isset($_POST['checkout'])) {
    $cart_items = $cart->getItems();
    $all_in_stock = true;
    $out_of_stock_items = [];

    // Check all items have sufficient stock
    foreach ($cart_items as $id => $qty) {
        $product_model->read($id);
        if ($product_model->quantity < $qty) {
            $all_in_stock = false;
            $out_of_stock_items[] = $product_model->name . " (only {$product_model->quantity} left, you ordered $qty)";
        }
    }

    if ($all_in_stock) {
        // Deduct stock for all items
        foreach ($cart_items as $id => $qty) {
            $product_model->read($id);
            $new_quantity = $product_model->quantity - $qty;
            $product_model->updateQuantity($id, $new_quantity);
        }

        // Clear cart
        $cart->clear();
        $_SESSION['success'] = "Order placed successfully! Thank you for shopping.";
        header("Location: cart.php");
        exit();
    } else {
        $_SESSION['error'] = "Insufficient stock: " . implode(", ", $out_of_stock_items);
        header("Location: cart.php");
        exit();
    }
}

// Handle cart updates (Auto-submitted)
if (isset($_POST['update_cart'])) {
    $product_id = $_POST['product_id'];
    $new_qty = (int) $_POST['quantity'];

    $product_model->read($product_id);
    if ($new_qty <= $product_model->quantity) {
        $cart->update($product_id, $new_qty);
        $_SESSION['success'] = "Cart updated!";
    } else {
        $_SESSION['error'] = "Only {$product_model->quantity} items available for {$product_model->name}!";
    }
    header("Location: cart.php");
    exit();
}

if (isset($_POST['remove_item'])) {
    $cart->remove($_POST['product_id']);
    $_SESSION['success'] = "Item removed from cart!";
    header("Location: cart.php");
    exit();
}

// Get cart data with stock info
$cart_items = $cart->getItems();
$cart_data = [];
$total = 0;
$has_insufficient = false;

foreach ($cart_items as $id => $qty) {
    if ($product_model->read($id)) {
        $stock_status = ($product_model->quantity >= $qty) ? 'sufficient' : 'insufficient';
        if ($stock_status == 'insufficient') {
            $has_insufficient = true;
        }

        $subtotal = $product_model->price * $qty;
        $total += $subtotal;

        $cart_data[] = [
            'id' => $id,
            'name' => $product_model->name,
            'price' => $product_model->price,
            'quantity' => $qty,
            'stock' => $product_model->quantity,
            'stock_status' => $stock_status,
            'subtotal' => $subtotal,
            'image' => $product_model->image_url ?? 'placeholder.jpg'
        ];
    }
}
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Your Cart - Fresh Harvest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/cart.css">
    <link href="styles/cookie.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
    <style>
        /* Hide default number input arrows for a cleaner look */
        input[type="number"].qty-input::-webkit-inner-spin-button,
        input[type="number"].qty-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"].qty-input {
            -moz-appearance: textfield;
            border-left: none;
            border-right: none;
        }
        .qty-btn {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            color: #2f4a3a;
            font-weight: bold;
        }
        .qty-btn:hover {
            background-color: #e2e6ea;
        }
    </style>
</head>

<body class="Background">
    <div class="container mt-5 pt-1">
        <?php include('components/navbar.comp.php') ?>
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 mb-4 text-center text-sm-start">
            <h2 class="mb-0">Shopping Cart</h2>
            <a href="index.php" class="btn btn-outline-success w-100 w-sm-auto">← Continue Shopping</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (empty($cart_data)): ?>
            <div class="alert alert-info text-center p-5">
                <h4>Cart is empty</h4>
                <p>Add some fresh products!</p>
                <a href="index.php" class="btn btn-success">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8 col-md-12 mb-4">
                    <?php foreach ($cart_data as $item): ?>
                        <div class="card mb-3 shadow-sm border-0">
                            <div class="row g-0">
                                <div class="col-12 col-md-4 col-lg-3">
                                    <img src="<?= $item['image'] ?>" class="img-fluid rounded-top rounded-md-start w-100"
                                        style="height: 150px; object-fit: cover;">
                                </div>
                                <div class="col-12 col-md-8 col-lg-9">
                                    <div class="card-body">
                                        <div class="d-flex flex-column flex-sm-row justify-content-between">
                                            <h5 class="card-title text-success mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                            <p class="card-text fw-bold mb-0">₱<?= number_format($item['price'], 2) ?></p>
                                        </div>

                                        <?php if ($item['stock_status'] == 'insufficient'): ?>
                                            <div class="text-danger small mt-1 mb-2">
                                                 Only <?= $item['stock'] ?> items available in stock! (You have <?= $item['quantity'] ?>)
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex flex-wrap align-items-center mt-3 gap-2">
                                            <form method="POST" class="d-flex align-items-center mb-0">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="update_cart" value="1"> 
                                                <div class="input-group input-group-sm" style="width: 110px;">
                                                    <button class="btn qty-btn btn-minus" type="button">&minus;</button>
                                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" class="form-control text-center qty-input">
                                                    <button class="btn qty-btn btn-plus" type="button">&plus;</button>
                                                </div>
                                            </form>
                                            
                                            <form method="POST" class="mb-0">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        </div>
                                        <p class="mt-3 mb-0 small text-muted text-end">Subtotal: <strong class="text-dark">₱<?= number_format($item['subtotal'], 2) ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-4 col-md-12">
                    <div class="card shadow-sm border-0 position-sticky" style="top: 100px;">
                        <div class="card-header text-white" style="background-color: #2f4a3a;">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Unique Items:</span>
                                <strong><?= count($cart_data) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Quantity:</span>
                                <strong><?= array_sum(array_column($cart_data, 'quantity')) ?></strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="mb-0">Total:</h5>
                                <h5 class="mb-0 text-success">₱<?= number_format($total, 2) ?></h5>
                            </div>

                            <?php if ($has_insufficient): ?>
                                <div class="alert alert-warning mt-3 small">
                                    Some items have insufficient stock. Please update quantities before checking out.
                                </div>
                                <button class="btn btn-secondary w-100 mt-3" disabled>
                                    Checkout Unavailable
                                </button>
                            <?php else: ?>
                                <a href="checkout.php" style="text-decoration: none;">
                                    <button name="checkout" class="btn w-100 mt-3 text-white fw-bold" style="background-color: #2f4a3a;">
                                        Proceed to Checkout
                                    </button>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Handle minus buttons
            document.querySelectorAll('.btn-minus').forEach(function(btn) {
                btn.addEventListener('click', function () {
                    const input = this.nextElementSibling;
                    let currentValue = parseInt(input.value) || 1;
                    let min = parseInt(input.getAttribute('min')) || 1;
                    if (currentValue > min) {
                        input.value = currentValue - 1;
                        this.closest('form').submit(); // Submits the form instantly
                    }
                });
            });

            // Handle plus buttons
            document.querySelectorAll('.btn-plus').forEach(function(btn) {
                btn.addEventListener('click', function () {
                    const input = this.previousElementSibling;
                    let currentValue = parseInt(input.value) || 1;
                    let max = parseInt(input.getAttribute('max'));
                    if (!max || currentValue < max) {
                        input.value = currentValue + 1;
                        this.closest('form').submit(); // Submits the form instantly
                    }
                });
            });

            // Handle manual typing in the input box
            document.querySelectorAll('.qty-input').forEach(function(input) {
                input.addEventListener('change', function () {
                    // Ensures the value stays within bounds before submitting
                    let val = parseInt(this.value) || 1;
                    let max = parseInt(this.getAttribute('max'));
                    let min = parseInt(this.getAttribute('min')) || 1;
                    
                    if (val < min) this.value = min;
                    if (max && val > max) this.value = max;

                    this.closest('form').submit();
                });
            });
        });
    </script>

    <?php include __DIR__ . '/components/cookie/cookie-banner.php'; ?>
    <script src="scripts/cookie-consent.js"></script>
</body>

</html>