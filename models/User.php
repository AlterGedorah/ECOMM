<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = "users";
    
    public $id;
    public $name;
    public $email;
    public $password; // Plain text
    public $role;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register() {
        // Check if email exists
        $check = $this->conn->prepare("SELECT id FROM {$this->table} WHERE email = ?");
        $check->bind_param("s", $this->email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            return false;
        }
        
        // Insert with plain text password (NO HASH)
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $this->name, $this->email, $this->password);
        
        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return true;
        }
        return false;
    }
    
    public function login() {
        // Direct comparison of plain text
        $stmt = $this->conn->prepare("SELECT id, name, email, password, role FROM {$this->table} WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $this->email, $this->password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }
    
    public function isAdmin($user_id) {
        $stmt = $this->conn->prepare("SELECT role FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return ($row && $row['role'] == 'admin');
    }
}
?>