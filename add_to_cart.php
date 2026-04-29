<?php
session_start();
require_once 'models/Cart.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart = new Cart();
    $cart->add($_POST['product_id'], $_POST['quantity']);
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit();
}
?>