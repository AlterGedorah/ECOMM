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
    
    public function create() {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, category, price, quantity, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $this->name, $this->category, $this->price, $this->quantity, $this->image_url);
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
            $this->price = $row['current_price'];
            $this->quantity = $row['quantity']; 
            $this->image_url = $row['image_url'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
    
    public function update() {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET name = ?, category = ?, price = ?, quantity = ?, image_url = ? WHERE id = ?");
        $stmt->bind_param("ssdiss", $this->name, $this->category, $this->price, $this->quantity, $this->image_url, $this->id);
        return $stmt->execute();
    }
    
    public function delete() {
        // Delete image file if exists
        $this->read($this->id);
        if ($this->image_url && file_exists($this->image_url)) {
            unlink($this->image_url);
        }
        
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }
    
    public function updateQuantity($id, $quantity) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $id);
        return $stmt->execute();
    }
    
    public function hasStock($id, $requested_qty) {
        $this->read($id);
        return $this->quantity >= $requested_qty;
    }
    
    public function deductStock($id, $quantity) {
        $this->read($id);
        $new_quantity = $this->quantity - $quantity;
        return $this->updateQuantity($id, $new_quantity);
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
    
    // Enhanced uploadImage with resize functionality
    public function uploadImage($file) {
        $target_dir = "uploads/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Read image file
        $image_data = file_get_contents($file["tmp_name"]);
        
        // Create image resource from string
        $source = imagecreatefromstring($image_data);
        
        if (!$source) {
            return false;
        }
        
        // Get original dimensions
        $width = imagesx($source);
        $height = imagesy($source);
        
        // Create thumbnail (300x300)
        $thumb_size = 300;
        $thumbnail = imagecreatetruecolor($thumb_size, $thumb_size);
        
        // Calculate resize ratio
        $ratio = $thumb_size / max($width, $height);
        $new_width = $width * $ratio;
        $new_height = $height * $ratio;
        $x_offset = ($thumb_size - $new_width) / 2;
        $y_offset = ($thumb_size - $new_height) / 2;
        
        // Fill background with white
        $white = imagecolorallocate($thumbnail, 255, 255, 255);
        imagefill($thumbnail, 0, 0, $white);
        
        // Resize and copy image
        imagecopyresampled($thumbnail, $source, $x_offset, $y_offset, 0, 0, 
                          $new_width, $new_height, $width, $height);
        
        // Generate unique filename
        $image_name = time() . "_" . basename($file["name"]);
        $target_file = $target_dir . $image_name;
        
        // Save as JPEG
        imagejpeg($thumbnail, $target_file, 80);
        
        // Clean up memory
        unset($source, $thumbnail);
        
        return $target_file;
    }
}
?>