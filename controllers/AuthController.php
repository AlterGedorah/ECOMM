<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthHelper.php';

class AuthController
{
    private $user;

    private function buildFullName(?string $firstName, ?string $middleName, ?string $lastName): string
    {
        $parts = [];

        $firstName = trim((string) $firstName);
        $middleName = trim((string) $middleName);
        $lastName = trim((string) $lastName);

        if ($firstName !== '') {
            $parts[] = $firstName;
        }

        if ($middleName !== '') {
            $parts[] = $middleName;
        }

        if ($lastName !== '') {
            $parts[] = $lastName;
        }

        return trim(implode(' ', $parts));
    }

    public function __construct()
    {
        // Start session safely if not running yet
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // If the visitor has valid auth cookies, promote them into the session.
        AuthHelper::hydrateSessionFromCookies();
        $this->user = new User();
    }

    public function user() {
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'first_name' => $_SESSION['user_first_name'] ?? '',
                'middle_name' => $_SESSION['user_middle_name'] ?? '',
                'last_name' => $_SESSION['user_last_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'phone_num' => $_SESSION['user_phone_num'] ?? null,
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
                $rememberMe = !empty($_POST['remember_me']);

                $fullName = $this->buildFullName($this->user->first_name, $this->user->middle_name, $this->user->last_name);
                if ($fullName === '') {
                    $fullName = $this->user->name;
                }

                // Persist to cookies + session via the central helper.
                AuthHelper::persistLogin([
                    'id'          => $this->user->id,
                    'name'        => $fullName,
                    'first_name'  => $this->user->first_name ?? '',
                    'middle_name' => $this->user->middle_name ?? '',
                    'last_name'   => $this->user->last_name ?? '',
                    'email'       => $this->user->email,
                    'phone_num'   => $this->user->phone_num ?? null,
                    'role'        => $this->user->role ?: 'user',
                ], $rememberMe);

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
                $rememberMe = !empty($_POST['remember_me']);

                $fullName = $this->buildFullName($this->user->first_name, $this->user->middle_name, $this->user->last_name);
                if ($fullName === '') {
                    $fullName = $this->user->name;
                }

                AuthHelper::persistLogin([
                    'id'          => $this->user->id,
                    'name'        => $fullName,
                    'first_name'  => $this->user->first_name ?? '',
                    'middle_name' => $this->user->middle_name ?? '',
                    'last_name'   => $this->user->last_name ?? '',
                    'email'       => $this->user->email,
                    'phone_num'   => $this->user->phone_num ?? null,
                    'role'        => $this->user->role,
                ], $rememberMe);

                if (($_SESSION['user_role'] ?? '') === 'admin') {
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
        AuthHelper::clearAuthState();

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