<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->name = $_POST['name'];
            $this->user->email = $_POST['email'];
            $this->user->password = $_POST['password'];
            
            if ($this->user->register()) {
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['user_name'] = $this->user->name;
                $_SESSION['user_role'] = $this->user->role;
                
                header("Location: ../index.php");
                exit();
            } else {
                return "Email already exists!";
            }
        }
        return null;
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->email = $_POST['email'];
            $this->user->password = $_POST['password'];
            
            if ($this->user->login()) {
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['user_name'] = $this->user->name;
                $_SESSION['user_role'] = $this->user->role;
                
                if($this->user->role == 'admin') {
                    header("Location: ../admin_dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit();
            } else {
                return "Invalid email or password!";
            }
        }
        return null;
    }
    
    public function logout() {
        session_destroy();
        header("Location: ../index.php"); // ← CHANGED THIS LINE
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
    }
}
?>