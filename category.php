<?php
session_start();
require_once 'models/Product.php';
require_once 'controllers/ProductController.php';

// Get category from URL
$category = $_GET['cat'] ?? 'vegetables';

// Define categories
$categories = [
    'vegetables' => 'Fresh Vegetables',
    'fruits' => 'Fresh Fruits',
    'grains' => 'Grains / Cereals',
    'cash_crops' => 'Cash Crops'
];

// Check if category exists
if (!isset($categories[$category])) {
    header("Location: index.php");
    exit();
}

// Get all products in this category (no limit)
$product = new Product();
$products = $product->getByCategory($category, 1000); // Get all products

// Also get cart count for navbar
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $categories[$category] ?> - R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <style>
        body {
            padding-top: 80px;
        }

        .nav-link {
            color: white !important;
            transition: opacity 0.3s ease;
        }

        .nav-link:hover {
            opacity: 0.8;
        }
    </style>

    <!-- Navbar (copy from your index.php) -->
    <nav class="fixed-top p-3 shadow-lg px-4" style="background-color: #385144;">
        <div class="container-fluid">
            <div class="row w-100 align-items-center">
                <!-- Logo and nav links -->
                <div class="col-auto d-flex align-items-center">
                    <div class="me-4">
                        <a href="index.php">
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
                    </div>
                    <div class="ms-2"><a href="index.php" class="nav-link text-white">Home</a></div>
                    <div class="ms-4"><a href="products.php" class="nav-link text-white">Products</a></div>
                    <div class="ms-4"><a href="services.php" class="nav-link text-white">Services</a></div>
                    <div class="ms-4"><a href="about.php" class="nav-link text-white">About</a></div>
                </div>

                <!-- Right side -->
                <div class="col d-flex justify-content-end align-items-center">
                    <form class="d-flex me-5" action="search.php" method="GET">
                        <div class="position-relative">
                            <input class="form-control rounded-5 py-2 ps-4" type="search" name="q"
                                placeholder="Search products..." style="width: 250px;">
                            <button class="btn position-absolute top-0 end-0 h-100" type="submit"
                                style="background: none; border: none; padding-right:15px;">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M15.813 14.8711L11.8369 10.8951C12.9205 9.56987 13.4532 7.87893 13.325 6.17197C13.1967 4.46502 12.4173 2.87267 11.1479 1.72427C9.87853 0.575873 8.21631 -0.0406994 6.50509 0.00208668C4.79386 0.0448727 3.16454 0.743743 1.95414 1.95414C0.743743 3.16454 0.0448727 4.79386 0.00208668 6.50509C-0.0406994 8.21631 0.575873 9.87853 1.72427 11.1479C2.87267 12.4173 4.46502 13.1967 6.17197 13.325C7.87893 13.4532 9.56987 12.9205 10.8951 11.8369L14.8711 15.813C14.9968 15.9344 15.165 16.0015 15.3397 16C15.5143 15.9985 15.6814 15.9284 15.8049 15.8049C15.9284 15.6814 15.9985 15.5143 16 15.3397C16.0015 15.165 15.9344 14.9968 15.813 14.8711ZM6.68251 12.0115C5.62855 12.0115 4.59825 11.6989 3.72191 11.1134C2.84556 10.5278 2.16254 9.69556 1.7592 8.72182C1.35587 7.74808 1.25034 6.6766 1.45595 5.64289C1.66157 4.60917 2.16911 3.65964 2.91437 2.91437C3.65964 2.16911 4.60917 1.66157 5.64289 1.45595C6.6766 1.25034 7.74808 1.35587 8.72182 1.7592C9.69556 2.16254 10.5278 2.84556 11.1134 3.72191C11.6989 4.59825 12.0115 5.62855 12.0115 6.68251C12.0099 8.09535 11.4479 9.44987 10.4489 10.4489C9.44987 11.4479 8.09535 12.0099 6.68251 12.0115Z"
                                        fill="black" />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <div class="dropdown me-4">
                        <a href="#" class="nav-link text-white" data-bs-toggle="dropdown">
                            <svg width="15" height="22" viewBox="0 0 15 22" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M7.5 10C8.48891 10 9.4556 9.70675 10.2778 9.15735C11.1001 8.60794 11.741 7.82705 12.1194 6.91342C12.4978 5.99979 12.5969 4.99445 12.4039 4.02455C12.211 3.05465 11.7348 2.16373 11.0355 1.46447C10.3363 0.765206 9.44536 0.289002 8.47545 0.0960758C7.50555 -0.0968502 6.50021 0.00216642 5.58658 0.380605C4.67295 0.759043 3.89206 1.3999 3.34265 2.22215C2.79324 3.0444 2.5 4.01109 2.5 5C2.50132 6.32568 3.02853 7.59668 3.96593 8.53407C4.90332 9.47147 6.17432 9.99868 7.5 10ZM7.5 1.66667C8.15927 1.66667 8.80373 1.86217 9.3519 2.22844C9.90006 2.59471 10.3273 3.1153 10.5796 3.72439C10.8319 4.33348 10.8979 5.0037 10.7693 5.6503C10.6407 6.29691 10.3232 6.89085 9.85702 7.35702C9.39085 7.8232 8.7969 8.14067 8.1503 8.26929C7.5037 8.3979 6.83347 8.33189 6.22439 8.0796C5.6153 7.82731 5.09471 7.40006 4.72843 6.8519C4.36216 6.30374 4.16667 5.65927 4.16667 5C4.16667 4.11595 4.51786 3.2681 5.14298 2.64298C5.7681 2.01786 6.61594 1.66667 7.5 1.66667Z"
                                    fill="white" />
                                <path
                                    d="M7.5 11.5C5.51155 11.5026 3.60518 12.4517 2.19914 14.139C0.79309 15.8262 0.00220571 18.1139 0 20.5C0 20.7652 0.0877973 21.0196 0.244078 21.2071C0.400358 21.3946 0.61232 21.5 0.833333 21.5C1.05435 21.5 1.26631 21.3946 1.42259 21.2071C1.57887 21.0196 1.66667 20.7652 1.66667 20.5C1.66667 18.6435 2.28125 16.863 3.37521 15.5503C4.46917 14.2375 5.9529 13.5 7.5 13.5C9.0471 13.5 10.5308 14.2375 11.6248 15.5503C12.7188 16.863 13.3333 18.6435 13.3333 20.5C13.3333 20.7652 13.4211 21.0196 13.5774 21.2071C13.7337 21.3946 13.9457 21.5 14.1667 21.5C14.3877 21.5 14.5996 21.3946 14.7559 21.2071C14.9122 21.0196 15 20.7652 15 20.5C14.9978 18.1139 14.2069 15.8262 12.8009 14.139C11.3948 12.4517 9.48845 11.5026 7.5 11.5Z"
                                    fill="white" />
                            </svg>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                                <?php if (($_SESSION['user_role'] ?? null) === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin_dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="login.php">Login</a></li>
                                <li><a class="dropdown-item" href="register.php">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <a href="cart.php" class="nav-link text-white position-relative">
                        <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0.20962 0.0139972C0.28627 0.00868227 0.37345 0.0049274 0.450054 0.0048859C1.68482 0.00421451 2.92079 -0.00818783 4.15529 0.00932437C4.2203 0.0126813 4.47565 0.0410211 4.50494 0.0895905C4.58905 0.2291 4.58309 0.574774 4.58204 0.724959C4.57377 1.89592 4.59374 3.06893 4.57657 4.23927C6.18304 4.26551 7.83209 4.24689 9.44349 4.24691L18.2123 4.24657L21.8656 4.24586C22.4856 4.24579 23.1069 4.24256 23.7269 4.25013C23.8432 4.25157 24.0576 4.30592 24.0562 4.45533C24.0549 4.58529 24.01 4.7347 23.9651 4.85716C23.6658 5.5871 23.3587 6.31747 23.0551 7.04532L21.2691 11.3298L19.9396 14.5226C19.808 14.8386 19.2907 16.1546 19.1373 16.3331C18.8112 16.4172 18.1816 16.389 17.8186 16.3889L16.088 16.3887L9.0244 16.3882L6.04266 16.3884C5.65436 16.3885 4.92933 16.4153 4.58112 16.3822L4.58121 18.2886C4.58129 18.6164 4.59098 18.9998 4.56752 19.3228L14.8284 19.3267L18.725 19.3242C19.3598 19.324 20.033 19.313 20.6678 19.3408C20.7422 19.3441 20.8205 19.3742 20.8753 19.4269C20.9304 19.4806 20.9595 19.5556 20.9551 19.6324C20.9352 19.9461 20.5383 19.9162 20.3174 19.9165C20.0159 19.9168 19.7145 19.9164 19.4132 19.9061C19.5016 19.9837 19.5862 20.0655 19.6667 20.1513C20.1233 20.6463 20.3641 21.3026 20.3359 21.9754C20.3082 22.6563 20.0083 23.2975 19.5037 23.7553C18.9965 24.2148 18.3656 24.4514 17.6812 24.4162C16.9995 24.3835 16.3593 24.079 15.9035 23.571C14.9207 22.4732 15.0812 20.8778 16.1725 19.9159L11.2714 19.9105C10.6714 19.9106 9.54247 19.9473 8.98771 19.9003C9.65362 20.5182 9.98236 21.2103 9.89645 22.1325C9.83365 22.7959 9.50946 23.407 8.9954 23.831C8.48002 24.2656 7.81063 24.4731 7.13981 24.406C6.45709 24.343 5.82872 24.0069 5.39706 23.4743C4.97179 22.9539 4.77247 22.2849 4.84357 21.6167C4.90831 20.9529 5.23563 20.3425 5.75272 19.9213C5.42199 19.9046 4.23629 19.9721 4.06395 19.8168C3.98519 19.5366 4.01117 18.3532 4.01119 18.005L4.01193 14.8136L4.01268 0.591771C2.88541 0.568372 1.75112 0.601097 0.623184 0.588065C0.456177 0.586133 0.106226 0.610836 0.0292511 0.434954C-0.00864438 0.348568 -0.00976256 0.250479 0.0261505 0.16325C0.0641095 0.0718464 0.124022 0.0509748 0.20962 0.0139972ZM4.57106 15.803L14.0774 15.8045C15.6234 15.8045 17.2384 15.7809 18.7802 15.8127C19.198 14.7727 19.6788 13.5989 20.1273 12.5724L22.3106 7.33138L22.9849 5.71654C23.0827 5.48226 23.2772 5.0472 23.3437 4.81769C22.9501 4.84562 22.3637 4.82999 21.9567 4.83043L19.4312 4.83104L11.6204 4.83063L6.77145 4.83048L5.441 4.83353C5.17543 4.83473 4.85355 4.85433 4.59493 4.81388C4.56532 5.06524 4.5765 5.53563 4.57714 5.79737L4.58073 7.43182L4.58004 13.0329L4.5806 14.7932C4.58082 15.0976 4.59325 15.5123 4.57106 15.803ZM7.6996 23.8127C8.76512 23.6318 9.48451 22.6244 9.30963 21.5578C9.13478 20.4913 8.13131 19.7663 7.06385 19.9353C5.98793 20.1055 5.25595 21.1187 5.43221 22.1936C5.60846 23.2686 6.62565 23.9949 7.6996 23.8127ZM18.0999 23.8155C19.1714 23.6474 19.9038 22.6426 19.7358 21.5711C19.5677 20.4996 18.563 19.7671 17.4914 19.9351C16.4198 20.103 15.6873 21.1079 15.8553 22.1796C16.0234 23.2511 17.0283 23.9836 18.0999 23.8155Z"
                                fill="white" />
                        </svg>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $cart_count ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="display-5"><?= $categories[$category] ?></h1>
                <p class="text-muted">Showing <?= count($products) ?> products</p>
            </div>
            <a href="index.php" class="btn btn-outline-success rounded-pill px-4">
                ← Back to Home
            </a>
        </div>

        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-12 col-md-6 col-lg-3 mt-4">
                    <div class="card rounded-4 overflow-hidden h-100 shadow-sm">
                        <div
                            style="height: 250px; background-image: url('<?= $product['image_url'] ?? 'placeholder.jpg' ?>'); background-size: cover; background-position: center;">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="text-success fw-bold">₱<?= number_format($product['price'], 2) ?></p>

                            <?php if ($product['quantity'] > 0): ?>
                                <div class="text-success small mb-2">✓ In Stock (<?= $product['quantity'] ?> available)</div>
                                <button class="btn btn-outline-success w-100 rounded-pill buy-btn" data-bs-toggle="modal"
                                    data-bs-target="#productModal" data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= $product['price'] ?>"
                                    data-quantity="<?= $product['quantity'] ?>"
                                    data-image="<?= $product['image_url'] ?? 'placeholder.jpg' ?>">
                                    Buy
                                </button>
                            <?php else: ?>
                                <div class="text-danger small mb-2">✗ Out of Stock</div>
                                <button class="btn btn-secondary w-100 rounded-pill" disabled>OUT OF STOCK</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Include the same modal from index.php -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <img src="" id="modalImage" class="img-fluid rounded-start"
                                style="height: 100%; width: 100%; object-fit: cover;">
                        </div>
                        <div class="col-md-6 p-4">
                            <h3 id="modalName" class="mb-2"></h3>
                            <p class="text-secondary mb-3" id="modalPrice"></p>
                            <div id="modalStock" class="mb-2"></div>
                            <p class="mb-2">Fresh from local farms. Quality guaranteed.</p>

                            <form method="POST" action="add_to_cart.php" id="addToCartForm">
                                <input type="hidden" name="product_id" id="modalProductId">
                                <div class="mb-3">
                                    <label class="form-label">Quantity:</label>
                                    <input type="number" name="quantity" id="modalQuantity" value="1" min="1" max="99"
                                        class="form-control" style="width: 100px;">
                                </div>
                                <button type="submit" id="modalAddToCartBtn"
                                    class="btn btn-success w-100 py-2 rounded-5">Add to Cart</button>
                            </form>
                            <button class="btn btn-outline-secondary w-100 mt-2 rounded-5"
                                data-bs-dismiss="modal">Continue Shopping</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <div class="container-fluid border py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col">
                    <h5 class="mb-3">About Us</h5>
                    <p class="text-secondary">We provide farm-fresh crops directly from local farmers.</p>
                </div>

                <div class="col">
                    <h5 class="mb-3">Customer Service</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">FAQ'S</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Shipping Policy</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Returns & Exchanges</a>
                        </li>
                    </ul>
                </div>

                <div class="col">
                    <h5 class="mb-3">Our Company</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Careers</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Blogs</a></li>
                    </ul>
                </div>

                <div class="col">
                    <h5 class="mb-3">Contact Us</h5>
                    <p class="text-secondary mb-1">support@r3farm.com</p>
                    <p class="text-secondary">123 Farm St, Manila</p>
                </div>

                <div class="col">
                    <h5 class="mb-3">Follow Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Facebook</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Instagram</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Twitter</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-5 d-flex flex-row py-3">
        <div class="ms-4">Privacy Policy</div>
        <div class="ms-5">Terms</div>
        <div class="ms-5">Conditions</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal population script (same as index.php)
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(element => {
            element.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const price = this.getAttribute('data-price');
                const quantity = parseInt(this.getAttribute('data-quantity'));
                const image = this.getAttribute('data-image');

                document.getElementById('modalProductId').value = id;
                document.getElementById('modalName').innerText = name;
                document.getElementById('modalPrice').innerHTML = `₱${parseFloat(price).toFixed(2)}`;
                document.getElementById('modalImage').src = image;

                const qtyInput = document.getElementById('modalQuantity');
                qtyInput.max = quantity;
                qtyInput.value = 1;

                const stockDiv = document.getElementById('modalStock');
                const addToCartBtn = document.getElementById('modalAddToCartBtn');

                if (quantity > 0) {
                    if (quantity < 10) {
                        stockDiv.innerHTML = `<div class="text-warning small">⚠️ Only ${quantity} left in stock!</div>`;
                    } else {
                        stockDiv.innerHTML = `<div class="text-success small">✓ ${quantity} items available</div>`;
                    }
                    addToCartBtn.disabled = false;
                    addToCartBtn.classList.remove('btn-secondary');
                    addToCartBtn.classList.add('btn-success');
                } else {
                    stockDiv.innerHTML = `<div class="text-danger small">✗ Out of stock!</div>`;
                    addToCartBtn.disabled = true;
                    addToCartBtn.classList.remove('btn-success');
                    addToCartBtn.classList.add('btn-secondary');
                }
            });
        });
    </script>
</body>

</html>