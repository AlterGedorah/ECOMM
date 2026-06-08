<?php
require_once __DIR__ . '/../models/Product.php';

class ProductController {
    private $product;
    
    public function __construct() {
        $this->product = new Product();
    }
    
    public function index() {
        $categories = [
            'vegetables' => 'Fresh Vegetables',
            'fruits' => 'Fresh Fruits',
            'grains' => 'Grains / Cereals',
            'cash_crops' => 'Cash Crops'
        ];
        
        $products_by_cat = [];
        
        foreach ($categories as $key => $label) {
            $products_by_cat[$key] = $this->product->getByCategory($key, 4);
        }
        
        return ['categories' => $categories, 'products' => $products_by_cat];
    }
    
    public function adminIndex() {
        return $this->product->getAll();
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->product->name = $_POST['name'];
            $this->product->category = $_POST['category'];
            $this->product->price = $_POST['price'];
            $this->product->quantity = $_POST['quantity'];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $this->product->image_url = $this->product->uploadImage($_FILES['image']);
            } else {
                $this->product->image_url = '';
            }
            
            if ($this->product->create()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product created successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => ' Failed to create product'];
            }
            
            header("Location: /Fresh Harvest/admin_dashboard.php");
            exit();
        }
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->product->id = $_POST['id'];
            
            $this->product->name = $_POST['name'];
            $this->product->category = $_POST['category'];
            $this->product->price = $_POST['price'];
            $this->product->quantity = $_POST['quantity'];
            
            // Only update image if new one uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $this->product->image_url = $this->product->uploadImage($_FILES['image']);
            } else {
                // Keep existing image
                $current = new Product();
                $current->read($this->product->id);
                $this->product->image_url = $current->image_url;
            }
            
            if ($this->product->update()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product updated successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update product'];
            }
            
            header("Location: /Fresh Harvest/admin_dashboard.php");
            exit();
        }
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->product->id = $_POST['id'];
            
            if ($this->product->delete()) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product deleted successfully!'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete product'];
            }
            
            header("Location: /Fresh Harvest/admin_dashboard.php");
            exit();
        }
    }
}
?>