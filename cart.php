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

// Handle cart updates
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/cart.css">
    <link rel="icon" href="images/icon.png">
</head>

<body>
    <div class="container mt-5 pt-1">
        <?php include('components/navbar.comp.php') ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Shopping Cart</h2>
            <a href="index.php" class="btn btn-outline-success">← Continue Shopping</a>
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
                <div class="col-md-8">
                    <?php foreach ($cart_data as $item): ?>
                        <div class="card mb-3">
                            <div class="row g-0">
                                <div class="col-md-3">
                                    <img src="<?= $item['image'] ?>" class="img-fluid rounded-start"
                                        style="height: 150px; object-fit: cover; width: 100%;">
                                </div>
                                <div class="col-md-9">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($item['name']) ?></h5>
                                        <p class="card-text">₱<?= number_format($item['price'], 2) ?> each</p>

                                        <!-- Stock warning -->
                                        <?php if ($item['stock_status'] == 'insufficient'): ?>
                                            <div class="text-danger small mb-2">
                                                ⚠️ Only <?= $item['stock'] ?> items available in stock! (You have
                                                <?= $item['quantity'] ?>)
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex align-items-center">
                                            <form method="POST" class="me-2">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1"
                                                    max="<?= $item['stock'] ?>" class="form-control"
                                                    style="width: 80px; display: inline-block;">
                                                <button type="submit" name="update_cart"
                                                    class="btn btn-sm btn-info">Update</button>
                                            </form>
                                            <form method="POST">
                                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                                <button type="submit" name="remove_item"
                                                    class="btn btn-sm btn-danger">Remove</button>
                                            </form>
                                        </div>
                                        <p class="mt-2"><strong>Subtotal: ₱<?= number_format($item['subtotal'], 2) ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header text-white" style="background-color: #2f4a3a;">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <p>Items: <?= count($cart_data) ?></p>
                            <p>Total Quantity: <?= array_sum(array_column($cart_data, 'quantity')) ?></p>
                            <hr>
                            <h5>Total: ₱<?= number_format($total, 2) ?></h5>

                            <?php if ($has_insufficient): ?>
                                <div class="alert alert-warning mt-3 small">
                                    ⚠️ Some items have insufficient stock. Please update quantities before checking out.
                                </div>
                                <button class="btn btn-secondary w-100 mt-3" disabled>
                                    Checkout Unavailable
                                </button>
                            <?php else: ?>

                                <a href="checkout.php"><button name="checkout" class="btn w-100 mt-3 text-white" style="background-color: #2f4a3a;">
                                    Proceed to Checkout
                                </button></a>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>