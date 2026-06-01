<?php
session_start();
require_once '../config/Database.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'user';
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_slug = trim($_POST['shop_slug'] ?? '');
    $farm_location = trim($_POST['farm_location'] ?? '');
    $shop_bio = trim($_POST['shop_bio'] ?? '');

    if (!in_array($role, ['user', 'seller'], true)) {
        $error = "Invalid account type!";
    }

    // Validation using string functions
    if (!$error && empty($name)) {
        $error = "Name is required!";
    } elseif (!$error && !preg_match("/^[a-zA-Z ]+$/", $name)) {
        $error = "Name can only contain letters and spaces!";
    } elseif (!$error && strlen($name) < 2) {
        $error = "Name must be at least 2 characters!";
    }

    // Email validation
    if (!$error && empty($email)) {
        $error = "Email is required!";
    } elseif (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }

    // Password validation
    if (!$error && empty($password)) {
        $error = "Password is required!";
    } elseif (!$error && strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif (!$error && $password !== $confirm_password) {
        $error = "Passwords do not match!";
    }

    if (!$error && $role === 'seller' && empty($shop_name)) {
        $error = "Shop name is required for seller accounts!";
    }

    if (!$error && $role === 'seller' && empty($shop_slug)) {
        $shop_slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($shop_name));
        $shop_slug = trim($shop_slug, '-');
    }

    if (!$error) {
        // Check if email exists
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $shop_name_value = $role === 'seller' ? $shop_name : null;
            $shop_slug_value = $role === 'seller' ? $shop_slug : null;
            $farm_location_value = $role === 'seller' ? $farm_location : null;
            $shop_bio_value = $role === 'seller' ? $shop_bio : null;

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, shop_name, shop_slug, farm_location, shop_bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $name, $email, $hashed_password, $role, $shop_name_value, $shop_slug_value, $farm_location_value, $shop_bio_value);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_shop_name'] = $shop_name_value;
                $_SESSION['user_shop_slug'] = $shop_slug_value;
                setcookie("user_id", $stmt->insert_id, time() + (86400 * 7), "/");
                setcookie("user_name", $name, time() + (86400 * 7), "/");
                setcookie("user_role", $role, time() + (86400 * 7), "/");
                setcookie("user_shop_name", $shop_name_value ?? '', time() + (86400 * 7), "/");
                setcookie("user_shop_slug", $shop_slug_value ?? '', time() + (86400 * 7), "/");
                $success = "Account created successfully! Redirecting...";
                header("Refresh: 2; url=/ECOMM/" . ($role === 'seller' ? 'seller_dashboard.php' : 'index.php'));
            } else {
                $error = "Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register – R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet" />
    
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --panel-bg: #2f4a3c;
            --accent: #f5f0e8;
            --link: #8ec99a;
            --input-bg: transparent;
            --input-border: rgba(255, 255, 255, 0.35);
            --input-focus: rgba(255, 255, 255, 0.7);
            --text-main: #f0ece3;
            --text-muted: rgba(240, 236, 227, 0.65);
            --btn-primary-bg: #f0ece3;
            --btn-primary-text: #2f4a3c;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: 'DM Sans', sans-serif;
        }

        .page-wrapper {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* ── LEFT hero ── */
        .hero-pane {
            flex: 1 1 70%;
            position: relative;
            overflow: hidden;
        }

        .hero-pane img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hero-pane::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(0, 0, 0, .38) 0%, transparent 70%),
                linear-gradient(to top, rgba(0, 0, 0, .30) 0%, transparent 60%);
        }

        .hero-text {
            position: absolute;
            bottom: 80px;
            left: 48px;
            color: #fff;
            z-index: 2;
            max-width: 500px;
        }

        .hero-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 3.2vw, 3rem);
            line-height: 1.18;
            margin-bottom: 16px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, .3);
        }

        .hero-text p {
            font-size: 0.97rem;
            font-weight: 300;
            line-height: 1.65;
            opacity: .88;
        }

        /* ── RIGHT panel ── */
        .auth-pane {
            width: 360px;
            min-width: 320px;
            background: var(--panel-bg);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto;
        }

        /* top nav */
        .auth-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 28px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-logo {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo svg {
            width: 20px;
            height: 20px;
            fill: #fff;
        }

        .lang-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.78rem;
            letter-spacing: .08em;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: color .2s;
        }

        .lang-btn:hover {
            color: var(--text-main);
        }

        .lang-btn svg {
            width: 16px;
            height: 16px;
        }

        /* form body */
        .auth-body {
            flex: 1;
            padding: 36px 36px 28px;
            display: flex;
            flex-direction: column;
            animation: fadeUp .45s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-body h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.85rem;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .auth-body .subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 26px;
        }

        /* labels & inputs */
        .field-label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 400;
            letter-spacing: .03em;
        }

        .auth-input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 4px;
            padding: 9px 12px;
            color: var(--text-main);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            caret-color: var(--link);
        }

        .auth-input::placeholder {
            color: rgba(240, 236, 227, 0.35);
        }

        .auth-input:focus {
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(142, 201, 154, .15);
        }

        .mb-field {
            margin-bottom: 14px;
        }

        /* password strength bar */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            margin-top: 6px;
            background: rgba(255, 255, 255, .1);
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: width .3s, background .3s;
        }

        /* remember row */
        .meta-row {
            display: flex;
            align-items: center;
            margin-top: 8px;
            margin-bottom: 24px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .meta-row label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .meta-row input[type=checkbox] {
            accent-color: var(--link);
            width: 13px;
            height: 13px;
        }

        /* primary btn */
        .btn-primary-auth {
            width: 100%;
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            border-radius: 4px;
            padding: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: .02em;
            transition: background .2s, transform .12s, box-shadow .2s;
        }

        .btn-primary-auth:hover {
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .18);
        }

        .btn-primary-auth:active {
            transform: translateY(0);
        }

        /* footer note */
        .auth-footer {
            margin-top: 18px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--link);
            text-decoration: none;
            font-weight: 500;
            margin-left: 4px;
            transition: opacity .2s;
        }

        .auth-footer a:hover {
            opacity: .8;
            text-decoration: underline;
        }

        .page-footer {
            position: absolute;
            bottom: 10px;
            right: 20px;
            font-size: 0.65rem;
            color: rgba(240, 236, 227, 0.2);
            pointer-events: none;
        }

        /* error state */
        .auth-input.error {
            border-color: #e07070 !important;
            box-shadow: 0 0 0 3px rgba(220, 80, 80, .18) !important;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">

        <!-- LEFT hero -->
        <div class="hero-pane">
            <img src="../images/crops.jpg" alt="Rice field with hand holding grain stalks" />
            <div class="hero-text">
                <h1>Cultivating the<br>future of global<br>agriculture.</h1>
                <p>Tomorrow's farming will grow smarter, not just bigger—where<br>
                    technology and nature work hand in hand to feed the world.</p>
            </div>
        </div>

        <!-- RIGHT auth panel -->
        <div class="auth-pane">
            <nav class="auth-nav">
              <a href="../index.php">
                            <svg width="36" height="36" viewBox="0 0 36 36" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M36 18C36 27.9411 27.9411 36 18 36C8.05887 36 0 27.9411 0 18C0 8.05887 8.05887 0 18 0C27.9411 0 36 8.05887 36 18Z"
                                    fill="white" />
                                <path
                                    d="M17.7059 3.69396C17.4303 3.82877 16.7633 4.46196 16.4491 4.88681C15.9364 5.58128 15.6167 6.44732 15.6167 7.14996V7.55847L15.3632 7.37873C14.834 6.99473 13.7481 6.54128 13.3622 6.54128C13.0645 6.54128 13.0039 6.66792 13.0425 7.248C13.1142 8.39592 13.6874 9.33958 14.8781 10.2546C15.1592 10.4712 15.6388 10.7857 15.953 10.9532C16.2617 11.1207 16.6034 11.3127 16.7026 11.378C17.0444 11.5946 17.0499 11.615 17.0499 12.8283C17.0499 13.4329 17.0279 13.9394 17.0058 13.9517C16.9782 13.9639 16.6475 13.7392 16.2727 13.4533C12.3645 10.4752 11.8794 10.1443 10.8872 9.75626C10.3084 9.53158 9.95012 9.45396 9.77924 9.51932C9.53119 9.61736 9.66348 10.0626 10.1486 10.7694C10.6226 11.4638 11.3392 12.0807 12.6401 12.91C13.5276 13.4778 14.4481 14.1232 14.9993 14.5685C15.7104 15.1363 16.4436 16.0228 16.7081 16.6315C17.0113 17.3096 17.0113 17.3178 17.0334 19.6626C17.0444 20.8718 17.0389 21.8604 17.0113 21.8604C16.9838 21.8604 16.7412 21.6929 16.4711 21.4887C16.1955 21.2844 15.6829 20.9331 15.3301 20.7084L14.6796 20.2958V19.7035C14.6796 17.4608 13.3677 15.4019 11.0691 14.0293C9.6249 13.1673 7.51921 12.5382 5.74978 12.4361C5.32534 12.4116 5.31982 12.4116 5.2206 12.53C5.12689 12.6363 5.11587 12.7261 5.12689 13.4655C5.13792 14.7033 5.38597 15.598 5.99232 16.5947C6.49393 17.4158 7.17745 18.143 8.06493 18.7966C8.89728 19.4135 9.83436 19.8792 12.1275 20.831C13.2024 21.2763 13.7922 21.5949 14.608 22.1668C15.4514 22.7592 15.9089 23.2044 16.3609 23.8826C16.7247 24.43 16.9011 24.8344 16.9948 25.3736C17.0609 25.7699 17.0664 29.2953 17.0003 29.2953C16.9727 29.2953 16.9397 29.279 16.9286 29.2626C16.868 29.1442 15.1151 28.0453 14.8064 27.9309C14.8009 27.9268 14.7733 27.6858 14.7403 27.3876C14.5529 25.6351 13.5276 23.9683 11.9456 22.8368C10.0549 21.4928 7.31526 20.7329 4.59221 20.8065C3.66615 20.831 3.6 20.8596 3.6 21.2232C3.6 21.7583 3.88112 22.6652 4.28903 23.4373C4.87884 24.5607 5.7608 25.5452 6.95145 26.4031C8.15864 27.2732 9.40992 27.8329 11.5817 28.4742C12.0448 28.6131 12.6511 28.8051 12.9323 28.8991C14.9056 29.5854 16.3774 30.6883 17.0279 31.9792C17.2428 32.4 17.2428 32.4 17.9264 32.4C18.3453 32.4 18.5437 32.3837 18.6264 32.3428C18.687 32.3101 18.8028 32.1549 18.8855 31.9997C19.2162 31.3787 19.8887 30.6352 20.5612 30.145C21.4652 29.4914 22.4629 29.0502 24.271 28.5191C26.3877 27.89 27.6775 27.3426 28.8241 26.5787C30.6431 25.3654 31.9991 23.4495 32.3464 21.5908C32.4787 20.8923 32.4181 20.831 31.5582 20.8065C30.4502 20.7738 28.9288 20.9331 27.7547 21.2109C27.0216 21.3825 25.9687 21.7624 25.2962 22.0933C24.6623 22.4078 23.7197 23.0614 23.163 23.5762C22.7165 23.9888 22.2094 24.6383 21.9338 25.153C21.5424 25.8883 21.2392 26.9709 21.2392 27.6409C21.2392 27.8941 21.2337 27.9064 21.0353 28.0126C20.2691 28.4252 19.9383 28.6254 19.4643 28.9644L18.9241 29.3443V27.4856C18.9241 25.0836 18.9792 24.7854 19.5855 23.8826C20.4124 22.6489 21.7353 21.7052 23.9127 20.7983C24.3095 20.6349 24.9159 20.3816 25.2632 20.2346C26.5089 19.7157 27.4515 19.1888 28.2122 18.5801C29.3863 17.6487 30.147 16.607 30.5825 15.3447C30.8085 14.687 30.8857 14.1682 30.8857 13.3062C30.8857 12.6812 30.8802 12.6322 30.7644 12.526C30.6486 12.4197 30.6321 12.4157 30.2793 12.4361C29.3643 12.4933 27.639 12.8609 26.7184 13.1959C25.197 13.7474 23.8079 14.6543 22.8323 15.7369C22.496 16.1045 22.0661 16.7663 21.8566 17.2238C21.5203 17.9592 21.2943 18.9968 21.2943 19.8383V20.3285L20.8975 20.5532C20.677 20.6757 20.1423 21.0352 19.7068 21.3498L18.9186 21.9176L18.9406 19.6749C18.9571 17.5711 18.9682 17.4077 19.0729 17.0809C19.2989 16.4068 19.6186 15.8921 20.2691 15.1813C20.688 14.7197 22.1046 13.6575 23.2236 12.9672C24.5521 12.1501 25.3293 11.4965 25.7979 10.8102C26.0404 10.4589 26.3105 9.89107 26.3105 9.73583C26.3105 9.61736 26.1672 9.48256 26.0404 9.48256C25.9081 9.48256 25.1419 9.72766 24.7505 9.89515C24.0394 10.2015 23.615 10.4875 21.2558 12.2563L18.9296 13.9966L18.9241 12.8283C18.9241 11.7008 18.9296 11.6558 19.0398 11.5496C19.1005 11.4924 19.4973 11.2514 19.9163 11.0145C21.9558 9.86656 22.9039 8.64511 22.959 7.08868C22.9756 6.65975 22.8984 6.54128 22.6007 6.54128C22.259 6.54128 21.151 7.0029 20.5778 7.38281L20.3848 7.51353L20.3793 7.1377C20.3573 6.07149 19.7399 4.93992 18.6485 3.97175C18.3839 3.73481 18.1358 3.6 17.9705 3.6C17.9264 3.6 17.8106 3.64085 17.7059 3.69396Z"
                                    fill="#003932" />
                            </svg>
                        </a>
                <button class="lang-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.86-7-7.93s3.06-7.44 7-7.93v15.86zm2 0V4.07c3.94.49 7 3.86 7 7.93s-3.06 7.44-7 7.93z" />
                    </svg>
                    ENGLISH &#9662;
                </button>
            </nav>

            <div class="auth-body">
                <h2>Register an account</h2>
                <p class="subtitle">Cultivating the
                    future of global
                    agriculture.</p>

                <?php if ($error): ?>
                <div style="background:#e07070;color:#fff;padding:10px 12px;border-radius:4px;margin-bottom:16px;font-size:0.85rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div style="background:#5aaa6e;color:#fff;padding:10px 12px;border-radius:4px;margin-bottom:16px;font-size:0.85rem;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="register.php" id="registerForm">
                <!-- Account Type -->
                <div class="mb-field">
                    <label class="field-label" for="role">Account type</label>
                    <select id="role" name="role" class="auth-input" style="appearance:none;">
                        <option value="user" selected>Customer</option>
                        <option value="seller">Farmer seller</option>
                    </select>
                </div>

                <!-- Username -->
                <div class="mb-field">
                    <label class="field-label" for="username">Username</label>
                    <input id="username" name="name" class="auth-input" type="text" placeholder="" autocomplete="username" />
                </div>

                <!-- Email -->
                <div class="mb-field">
                    <label class="field-label" for="email">Email</label>
                    <input id="email" name="email" class="auth-input" type="email" placeholder="mail@example.com"
                        autocomplete="email" />
                </div>

                <div class="mb-field seller-only" id="sellerFields" hidden>
                    <label class="field-label" for="shop_name">Farm shop name</label>
                    <input id="shop_name" name="shop_name" class="auth-input" type="text" placeholder="Green Valley Farm" autocomplete="organization" />

                    <div style="height: 12px;"></div>

                    <label class="field-label" for="shop_slug">Shop URL slug</label>
                    <input id="shop_slug" name="shop_slug" class="auth-input" type="text" placeholder="green-valley-farm" autocomplete="off" />

                    <div style="height: 12px;"></div>

                    <label class="field-label" for="farm_location">Farm location</label>
                    <input id="farm_location" name="farm_location" class="auth-input" type="text" placeholder="Laguna, Philippines" autocomplete="street-address" />

                    <div style="height: 12px;"></div>

                    <label class="field-label" for="shop_bio">Shop bio</label>
                    <textarea id="shop_bio" name="shop_bio" class="auth-input" rows="3" placeholder="Describe your farm, crops, and delivery area."></textarea>
                </div>

                <!-- Password -->
                <div class="mb-field">
                    <label class="field-label" for="password">Password</label>
                    <input id="password" name="password" class="auth-input" type="password" placeholder="••••••••"
                        autocomplete="new-password" />
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="field-label" for="confirmPassword">Confirm password</label>
                    <input id="confirmPassword" name="confirm_password" class="auth-input" type="password" placeholder=""
                        autocomplete="new-password" />
                </div>

                <!-- Remember -->
                <div class="meta-row">
                    <label>
                        <input type="checkbox" id="remember" /> Remember me?
                    </label>
                </div>

                <p class="subtitle" style="margin-top:-6px;margin-bottom:18px;">
                    Sellers get a public shop page where customers can browse produce directly from your farm.
                </p>

                <!-- Register btn -->
                <button class="btn-primary-auth" type="submit" id="registerBtn">Register</button>

                </form>

                <p class="auth-footer">
                    Already have an account?
                    <a href="login.php" id="signInLink">Sign In</a>
                </p>
            </div>

            <span class="page-footer">R3 Farm</span>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const registerBtn = document.getElementById('registerBtn');
        const roleSelect = document.getElementById('role');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const shopNameInput = document.getElementById('shop_name');
        const shopSlugInput = document.getElementById('shop_slug');
        const farmLocationInput = document.getElementById('farm_location');
        const shopBioInput = document.getElementById('shop_bio');
        const sellerFields = document.getElementById('sellerFields');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        const strengthFill = document.getElementById('strengthFill');

        function syncSellerFields() {
            const isSeller = roleSelect.value === 'seller';
            sellerFields.hidden = !isSeller;

            [shopNameInput, shopSlugInput, farmLocationInput, shopBioInput].forEach(inp => {
                inp.required = isSeller && inp === shopNameInput;
            });
        }

        roleSelect.addEventListener('change', syncSellerFields);
        syncSellerFields();

        // password strength meter
        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;
            if (val.length >= 6) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            // ensure score is within bounds 0-4
            score = Math.max(0, Math.min(score, 4));
            const widths = ['0%', '28%', '52%', '76%', '100%'];
            const colors = ['transparent', '#e07070', '#e0b870', '#8ec99a', '#5aaa6e'];
            strengthFill.style.width = widths[score];
            strengthFill.style.background = colors[score];
        });

        // shake helper
        function shake(el) {
            el.animate([
                { transform: 'translateX(-5px)' }, { transform: 'translateX(5px)' },
                { transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' },
                { transform: 'translateX(0)' }
            ], { duration: 300, easing: 'ease' });
        }

        // clear error on type
        [usernameInput, emailInput, passwordInput, confirmInput, shopNameInput, shopSlugInput, farmLocationInput, shopBioInput].forEach(inp => {
            inp.addEventListener('input', () => inp.classList.remove('error'));
        });

        document.getElementById('registerForm').addEventListener('submit', (e) => {
            let valid = true;

            [usernameInput, emailInput, passwordInput, confirmInput].forEach(inp => {
                if (!inp.value.trim()) {
                    inp.classList.add('error');
                    shake(inp);
                    valid = false;
                }
            });

            if (passwordInput.value && confirmInput.value &&
                passwordInput.value !== confirmInput.value) {
                confirmInput.classList.add('error');
                shake(confirmInput);
                valid = false;
            }

            if (roleSelect.value === 'seller' && !shopNameInput.value.trim()) {
                shopNameInput.classList.add('error');
                shake(shopNameInput);
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            } else {
                registerBtn.textContent = 'Creating account...';
                registerBtn.disabled = true;
            }
        });
    </script>
</body>

</html>