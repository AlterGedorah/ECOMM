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
            'role' => $_COOKIE['user_role'],
            'shop_name' => $_COOKIE['user_shop_name'] ?? null,
            'shop_slug' => $_COOKIE['user_shop_slug'] ?? null
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
            $this->user->role = $_POST['role'] ?? 'user';
            $this->user->shop_name = $_POST['shop_name'] ?? null;
            $this->user->shop_slug = $_POST['shop_slug'] ?? null;
            $this->user->farm_location = $_POST['farm_location'] ?? null;
            $this->user->shop_bio = $_POST['shop_bio'] ?? null;

            if ($this->user->register()) {
                setcookie("user_id", $this->user->id, time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_name", $this->user->name, time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_role", $this->user->role, time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_shop_name", $this->user->shop_name ?? '', time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_shop_slug", $this->user->shop_slug ?? '', time() + 60 * 60 * 24 * 7, "/");

                header("Location: " . ($this->user->role === 'seller' ? '/ECOMM/seller_dashboard.php' : '/ECOMM/index.php'));
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
                setcookie("user_id", $this->user->id, time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_name", $this->user->name, time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_role", $this->user->role, time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_shop_name", $this->user->shop_name ?? '', time() + 60 * 60 * 24 * 7, "/");
                setcookie("user_shop_slug", $this->user->shop_slug ?? '', time() + 60 * 60 * 24 * 7, "/");

                if ($this->user->role == 'admin') {
                    header("Location: /ECOMM/admin_dashboard.php");
                } elseif ($this->user->role == 'seller') {
                    header("Location: /ECOMM/seller_dashboard.php");
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

    public function isSeller()
    {
        return isset($_COOKIE['user_role']) && $_COOKIE['user_role'] === 'seller';
    }
}
?>