<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = "users";
    
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $shop_name;
    public $shop_slug;
    public $farm_location;
    public $shop_bio;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    private function slugify($value) {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : null;
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

        $this->role = $this->role ?: 'user';

        if ($this->role === 'seller') {
            $this->shop_name = trim((string) $this->shop_name);
            if ($this->shop_name === '') {
                return false;
            }

            $candidateSlug = $this->slugify($this->shop_slug ?: $this->shop_name);
            if ($candidateSlug === null) {
                return false;
            }

            $slug = $candidateSlug;
            $suffix = 2;
            while (true) {
                $slugCheck = $this->conn->prepare("SELECT id FROM {$this->table} WHERE shop_slug = ?");
                $slugCheck->bind_param("s", $slug);
                $slugCheck->execute();
                $slugCheck->store_result();

                if ($slugCheck->num_rows === 0) {
                    break;
                }

                $slug = $candidateSlug . '-' . $suffix;
                $suffix++;
            }

            $this->shop_slug = $slug;
        } else {
            $this->shop_name = null;
            $this->shop_slug = null;
            $this->farm_location = null;
            $this->shop_bio = null;
        }
        
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, email, password, role, shop_name, shop_slug, farm_location, shop_bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $this->name, $this->email, $hashedPassword, $this->role, $this->shop_name, $this->shop_slug, $this->farm_location, $this->shop_bio);
        
        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return true;
        }
        return false;
    }
    
    public function login() {
        $stmt = $this->conn->prepare("SELECT id, name, email, password, role, shop_name, shop_slug, farm_location, shop_bio, created_at FROM {$this->table} WHERE email = ?");
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($this->password, $row['password']) || $this->password === $row['password']) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->shop_name = $row['shop_name'];
                $this->shop_slug = $row['shop_slug'];
                $this->farm_location = $row['farm_location'];
                $this->shop_bio = $row['shop_bio'];
                $this->created_at = $row['created_at'];
                return true;
            }
        }
        return false;
    }

    public function findById($user_id) {
        $stmt = $this->conn->prepare("SELECT id, name, email, role, shop_name, shop_slug, farm_location, shop_bio, created_at FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }

        return null;
    }

    public function findByShopSlug($shop_slug) {
        $stmt = $this->conn->prepare("SELECT id, name, email, role, shop_name, shop_slug, farm_location, shop_bio, created_at FROM {$this->table} WHERE shop_slug = ? AND role = 'seller'");
        $stmt->bind_param("s", $shop_slug);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }

        return null;
    }

    public function updateShopProfile() {
        if (!$this->id) {
            return false;
        }

        $this->shop_name = trim((string) $this->shop_name);
        $this->shop_slug = $this->slugify($this->shop_slug ?: $this->shop_name);

        $stmt = $this->conn->prepare("UPDATE {$this->table} SET shop_name = ?, shop_slug = ?, farm_location = ?, shop_bio = ?, role = 'seller' WHERE id = ?");
        $stmt->bind_param("ssssi", $this->shop_name, $this->shop_slug, $this->farm_location, $this->shop_bio, $this->id);
        return $stmt->execute();
    }
    
    public function isAdmin($user_id) {
        $stmt = $this->conn->prepare("SELECT role FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return ($row && $row['role'] == 'admin');
    }

    public function isSeller($user_id) {
        $stmt = $this->conn->prepare("SELECT role FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return ($row && $row['role'] == 'seller');
    }
}
?>