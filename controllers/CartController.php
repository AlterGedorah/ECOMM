<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

class CartController {
    private $cart;
    private $product;
    
    public function __construct() {
        $this->cart = new Cart();
        $this->product = new Product();
    }
    
    public function getCartData() {
        $items = $this->cart->getItems();
        $cart_items = [];
        $total = 0;
        
        foreach($items as $id => $qty) {
            if($this->product->read($id)) {
                $subtotal = $this->product->price * $qty;
                $total += $subtotal;
                
                $cart_items[] = [
                    'id' => $id,
                    'name' => $this->product->name,
                    'price' => $this->product->price,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                    'image' => $this->product->image_url ?? 'placeholder.jpg'
                ];
            }
        }
        
        return ['items' => $cart_items, 'total' => $total];
    }
    public function clear() {
        $this->cart->clear();
        
    }
    public function add() {
        if(isset($_POST['product_id'], $_POST['quantity'])) {
            $id = (int)$_POST['product_id'];
            $qty = (int)$_POST['quantity'];
            $this->cart->add($id, $qty);
        }
    }
     public function update() {
        if(isset($_POST['product_id'], $_POST['quantity'])) {
            $id = (int)$_POST['product_id'];
            $qty = (int)$_POST['quantity'];
            $this->cart->update($id, $qty);
        }
     }
}
?>