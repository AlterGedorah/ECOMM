<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $user;

    public function __construct()
    {
        // Start session safely if not running yet
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->user = new User();
    }

    public function user() {
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->name = $_POST['name'];
            $this->user->email = $_POST['email'];
            $this->user->password = $_POST['password'];

            if ($this->user->register()) {
                // Save state to secure server session
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['user_name'] = $this->user->name;
                $_SESSION['user_role'] = $this->user->role;

                header("Location: /Fresh Harvest/index.php");
                exit();
            } else {
                return "Email already exists!";
            }
        }
        return null;
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->email = $_POST['email'];
            $this->user->password = $_POST['password'];

            if ($this->user->login()) {
                // Save state to secure server session
                $_SESSION['user_id'] = $this->user->id;
                $_SESSION['user_name'] = $this->user->name;
                $_SESSION['user_role'] = $this->user->role;

                if ($_SESSION['user_role'] === 'admin') {
                    header("Location: /Fresh Harvest/admin_dashboard.php");
                } else {
                    header("Location: /Fresh Harvest/index.php");
                }
                exit();
            } else {
                return "Invalid email or password!";
            }
        }
        return null;
    }

    public function logout()
    {
        // Clear all session memory keys
        $_SESSION = [];
        
        // Destroy actual session storage unit
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        header("Location: /Fresh Harvest/index.php");
        exit();
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
?>