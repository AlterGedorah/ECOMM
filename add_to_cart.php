<?php
session_start();
require_once 'models/Cart.php';
require_once 'models/Product.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    
    if (empty($product_id)) {
        $_SESSION['error'] = "Invalid product!";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    if (!is_numeric($product_id) || !is_int($product_id + 0)) {
        $_SESSION['error'] = "Invalid product ID!";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    if (!is_numeric($quantity) || $quantity < 1) {
        $_SESSION['error'] = "Invalid quantity!";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    $product = new Product();
    $product->read($product_id);
    
    // Check if product exists
    if (is_null($product->id)) {
        $_SESSION['error'] = "Product not found!";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    // Check stock
    if ($product->quantity < $quantity) {
        $_SESSION['error'] = "Only {$product->quantity} items available for {$product->name}!";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }
    
    // Add to cart
    $cart = new Cart();
    $cart->add($product_id, $quantity);
    
    $_SESSION['success'] = "Added {$quantity} x {$product->name} to cart!";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit();
}
?>