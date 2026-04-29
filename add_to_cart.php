<?php
session_start();
require_once 'models/Cart.php';
require_once 'models/Product.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Check stock
    $product = new Product();
    $product->read($product_id);
    
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