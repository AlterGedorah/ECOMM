<?php
require_once 'config/Database.php';

class Product {
    private $conn;
    private $table = "products";
    
    public $id;
    public $name;
    public $category;
    public $price_min;
    public $price_max;
    public $image_url;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function create() {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, category, price_min, price_max, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdds", $this->name, $this->category, $this->price_min, $this->price_max, $this->image_url);
        
        return $stmt->execute();
    }
    
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
            $this->price_min = $row['price_min'];
            $this->price_max = $row['price_max'];
            $this->image_url = $row['image_url'];
            $this->created_at = $row['created_at'];
            return true;
        }
        
        return false;
    }
    
    public function update() {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET name = ?, category = ?, price_min = ?, price_max = ?, image_url = ? WHERE id = ?");
        $stmt->bind_param("ssddsi", $this->name, $this->category, $this->price_min, $this->price_max, $this->image_url, $this->id);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        
        return $stmt->execute();
    }
    
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
    
    public function getAll() {
        $result = $this->conn->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }
    
    public function uploadImage($file) {
        $target_dir = "uploads/";
        
        // Create dir if not exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate unique filename
        $image_name = time() . "_" . basename($file["name"]);
        $target_file = $target_dir . $image_name;
        
        // Validate image
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            return false;
        }
        
        // Move file
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file;
        }
        
        return false;
    }
}
?>