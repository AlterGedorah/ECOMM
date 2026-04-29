<?php
require_once 'models/Product.php';

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
            $this->product->price_min = $_POST['price_min'];
            $this->product->price_max = $_POST['price_max'];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $this->product->image_url = $this->product->uploadImage($_FILES['image']);
            }
            
            if ($this->product->create()) {
                header("Location: admin_dashboard.php?success=Product created");
            } else {
                header("Location: admin_dashboard.php?error=Failed to create");
            }
            exit();
        }
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->product->id = $_POST['id'];
            $this->product->read($this->product->id);
            
            $this->product->name = $_POST['name'];
            $this->product->category = $_POST['category'];
            $this->product->price_min = $_POST['price_min'];
            $this->product->price_max = $_POST['price_max'];
            
            // Handle new image
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $this->product->image_url = $this->product->uploadImage($_FILES['image']);
            }
            
            if ($this->product->update()) {
                header("Location: admin_dashboard.php?success=Product updated");
            } else {
                header("Location: admin_dashboard.php?error=Failed to update");
            }
            exit();
        }
    }
    
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->product->id = $_POST['id'];
            
            if ($this->product->delete()) {
                header("Location: admin_dashboard.php?success=Product deleted");
            } else {
                header("Location: admin_dashboard.php?error=Failed to delete");
            }
            exit();
        }
    }
}
?>