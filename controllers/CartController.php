<?php
require_once 'models/Cart.php';
require_once 'models/Product.php';

class CartController {
    private $cart;
    private $product;
    
    public function __construct() {
        $this->cart = new Cart();
        $this->product = new Product();
    }
    
    public function add() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'] ?? 1;
            
            $this->cart->add($product_id, $quantity);
            
            // Return JSON for AJAX
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'cart_count' => count($this->cart->getItems())]);
                exit();
            }
            
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit();
        }
    }
    
    public function update() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->cart->update($_POST['product_id'], $_POST['quantity']);
            header("Location: cart.php");
            exit();
        }
    }
    
    public function remove() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->cart->remove($_POST['product_id']);
            header("Location: cart.php");
            exit();
        }
    }
    
    public function getCartData() {
        $items = $this->cart->getItems();
        $cart_items = [];
        $total = 0;
        
        foreach($items as $id => $qty) {
            $this->product->read($id);
            $avg_price = ($this->product->price_min + $this->product->price_max) / 2;
            $subtotal = $avg_price * $qty;
            $total += $subtotal;
            
            $cart_items[] = [
                'id' => $id,
                'name' => $this->product->name,
                'price' => $avg_price,
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'image' => $this->product->image_url
            ];
        }
        
        return ['items' => $cart_items, 'total' => $total];
    }
    
    public function getCartCount() {
        return count($this->cart->getItems());
    }
}
?>