<?php
require_once __DIR__ . '/../config/Database.php';

class Product {
    private $conn;
    private $table = "products";
    
    public $id;
    public $name;
    public $category;
    public $price;
    public $quantity;
    public $image_url;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // CREATE product
    public function create() {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, category, price, quantity, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $this->name, $this->category, $this->price, $this->quantity, $this->image_url);
        return $stmt->execute();
    }
    
    // READ product
    public function read($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->category = $row['category'];
            $this->price = $row['price'];
            $this->quantity = $row['quantity']; 
            $this->image_url = $row['image_url'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
    
    // UPDATE product
    public function update() {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET name = ?, category = ?, price = ?, quantity = ?, image_url = ? WHERE id = ?");
        $stmt->bind_param("ssdiss", $this->name, $this->category, $this->price, $this->quantity, $this->image_url, $this->id);
        return $stmt->execute();
    }
    
    // DELETE product - ADD THIS METHOD
    public function delete() {
        // First get image path to delete file
        $this->read($this->id);
        
        // Delete image file if exists
        if ($this->image_url && file_exists($this->image_url)) {
            unlink($this->image_url);
        }
        
        // Delete from database
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }
    
    // UPDATE quantity only
    public function updateQuantity($id, $quantity) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $id);
        return $stmt->execute();
    }
    
    // CHECK stock availability
    public function hasStock($id, $requested_qty) {
        $this->read($id);
        return $this->quantity >= $requested_qty;
    }
    
    // DEDUCT stock after purchase
    public function deductStock($id, $quantity) {
        $this->read($id);
        $new_quantity = $this->quantity - $quantity;
        return $this->updateQuantity($id, $new_quantity);
    }
    
    // GET products by category (with limit)
    public function getByCategory($category, $limit = 4) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE category = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("si", $category, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }
    
    // GET all products
    public function getAll() {
        $result = $this->conn->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }
    
    // UPLOAD product image
    public function uploadImage($file) {
        $target_dir = "uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . "_" . basename($file["name"]);
        $target_file = $target_dir . $image_name;
        
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            return false;
        }
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file;
        }
        return false;
    }
}
?>