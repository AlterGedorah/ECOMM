<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }
    public function user() {
    if (isset($_COOKIE['user_id'])) {
        return [
            'id' => $_COOKIE['user_id'],
            'name' => $_COOKIE['user_name'],
            'role' => $_COOKIE['user_role']
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
                setcookie("user_id", $this->user->id, time() + 60, "/");
                setcookie("user_name", $this->user->name, time() + 60, "/");
                setcookie("user_role", $this->user->role, time() + 60, "/");

                header("Location: /ECOMM/index.php");
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
                setcookie("user_id", $this->user->id, time() + 60, "/"); // 1 hour
                setcookie("user_name", $this->user->name, time() + 60, "/");
                setcookie("user_role", $this->user->role, time() + 60, "/");

                if ($this->user->role == 'admin') {
                    header("Location: /ECOMM/admin_dashboard.php");
                } else {
                    header("Location: /ECOMM/index.php");
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
        session_destroy();
        header("Location: /ECOMM/index.php");
        exit();
    }

    public function isLoggedIn()
    {
        return isset($_COOKIE['user_id']);
    }

    public function isAdmin()
    {
        return isset($_COOKIE['user_role']) && $_COOKIE['user_role'] === 'admin';
    }
}
?>