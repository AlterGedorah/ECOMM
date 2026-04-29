<?php
class Cart {
    private $cart_items = [];
    
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['cart'])) {
            $this->cart_items = $_SESSION['cart'];
        }
    }
    
    public function add($product_id, $quantity = 1) {
        if (isset($this->cart_items[$product_id])) {
            $this->cart_items[$product_id] += $quantity;
        } else {
            $this->cart_items[$product_id] = $quantity;
        }
        
        $_SESSION['cart'] = $this->cart_items;
        return true;
    }
    
    public function remove($product_id) {
        if (isset($this->cart_items[$product_id])) {
            unset($this->cart_items[$product_id]);
            $_SESSION['cart'] = $this->cart_items;
            return true;
        }
        
        return false;
    }
    
    public function update($product_id, $quantity) {
        if ($quantity <= 0) {
            return $this->remove($product_id);
        }
        
        $this->cart_items[$product_id] = $quantity;
        $_SESSION['cart'] = $this->cart_items;
        return true;
    }
    
    public function getItems() {
        return $this->cart_items;
    }
    
    public function clear() {
        $this->cart_items = [];
        $_SESSION['cart'] = [];
        return true;
    }
    
    public function getTotal($product_model) {
        $total = 0;
        
        foreach ($this->cart_items as $id => $qty) {
            $product_model->read($id);
            $avg_price = ($product_model->price_min + $product_model->price_max) / 2;
            $total += $avg_price * $qty;
        }
        
        return $total;
    }
}
?>