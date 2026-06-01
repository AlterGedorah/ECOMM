<?php
session_start();
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/models/User.php';

if (!isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    header('Location: /ECOMM/pages/login.php');
    exit();
}

$currentUserId = (int) ($_COOKIE['user_id'] ?? $_SESSION['user_id']);
$currentRole = $_COOKIE['user_role'] ?? $_SESSION['user_role'] ?? null;

if ($currentRole !== 'seller') {
    header('Location: /ECOMM/index.php');
    exit();
}

$userModel = new User();
$seller = $userModel->findById($currentUserId);

if (!$seller) {
    header('Location: /ECOMM/pages/login.php');
    exit();
}

if (empty($seller['shop_slug'])) {
    $seller['shop_slug'] = preg_replace('/[^a-z0-9]+/', '-', strtolower($seller['shop_name'] ?: $seller['name']));
    $seller['shop_slug'] = trim($seller['shop_slug'], '-');
}

$productController = new ProductController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_shop') {
    $userModel->id = $currentUserId;
    $userModel->shop_name = trim($_POST['shop_name'] ?? '');
    $userModel->shop_slug = trim($_POST['shop_slug'] ?? '');
    $userModel->farm_location = trim($_POST['farm_location'] ?? '');
    $userModel->shop_bio = trim($_POST['shop_bio'] ?? '');

    if ($userModel->updateShopProfile()) {
        setcookie('user_shop_name', $userModel->shop_name, time() + 60 * 60 * 24 * 7, '/');
        setcookie('user_shop_slug', $userModel->shop_slug, time() + 60 * 60 * 24 * 7, '/');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Shop profile updated.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Unable to update shop profile.'];
    }

    header('Location: /ECOMM/seller_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create', 'update', 'delete'], true)) {
    if ($_POST['action'] === 'create') {
        $productController->create();
    } elseif ($_POST['action'] === 'update') {
        $productController->update();
    } elseif ($_POST['action'] === 'delete') {
        $productController->delete();
    }
}

$products = $productController->sellerProducts($currentUserId);
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$publicShopUrl = '/ECOMM/shop.php?shop=' . urlencode($seller['shop_slug']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f4f0e8 0%, #eef5ee 100%);
        }
        .dashboard-hero {
            background: linear-gradient(135deg, #224230 0%, #3f6a43 55%, #86b36b 100%);
            color: #fff;
            border-radius: 28px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(16, 40, 24, .18);
        }
        .panel-card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(16, 40, 24, .08);
        }
        .soft-badge {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.18);
            color: #fff;
            border-radius: 999px;
            padding: 6px 12px;
            display: inline-block;
        }
        .shop-link {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.comp.php'; ?>
    <div class="container py-4">
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="dashboard-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                <div>
                    <div class="soft-badge mb-3">Farmer Seller Dashboard</div>
                    <h1 class="display-6 mb-2"><?= htmlspecialchars($seller['shop_name'] ?: $seller['name'] . ' Farm Shop') ?></h1>
                    <p class="mb-2 opacity-75"><?= htmlspecialchars($seller['shop_bio'] ?: 'Manage your farm storefront, publish fresh produce, and share your shop with customers.') ?></p>
                    <p class="mb-0 opacity-75">Location: <?= htmlspecialchars($seller['farm_location'] ?: 'Not set') ?></p>
                </div>
                <div class="bg-white text-dark rounded-4 p-3" style="min-width: 280px;">
                    <div class="small text-uppercase text-secondary">Public shop link</div>
                    <div class="shop-link fw-semibold mb-3"><?= htmlspecialchars($publicShopUrl) ?></div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= htmlspecialchars($publicShopUrl) ?>" class="btn btn-success">Open Shop</a>
                        <a href="/ECOMM/index.php" class="btn btn-outline-secondary">Marketplace</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card panel-card h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">Products</div>
                        <div class="display-6 fw-bold"><?= count($products) ?></div>
                        <div class="text-muted">Items listed in your farm shop</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card panel-card h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">Shop slug</div>
                        <div class="h4 mb-0"><?= htmlspecialchars($seller['shop_slug']) ?></div>
                        <div class="text-muted">Used for your public shop URL</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card panel-card h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">Account type</div>
                        <div class="h4 mb-0">Seller</div>
                        <div class="text-muted">Direct-to-customer produce storefront</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card panel-card mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h2 class="h4 mb-1">Shop Profile</h2>
                <p class="text-muted mb-0">Edit the information customers see on your shop page.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="save_shop">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shop name</label>
                            <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($seller['shop_name'] ?: '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shop slug</label>
                            <input type="text" name="shop_slug" class="form-control" value="<?= htmlspecialchars($seller['shop_slug'] ?: '') ?>" placeholder="green-valley-farm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Farm location</label>
                            <input type="text" name="farm_location" class="form-control" value="<?= htmlspecialchars($seller['farm_location'] ?: '') ?>" placeholder="Laguna, Philippines">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Shop bio</label>
                            <textarea name="shop_bio" class="form-control" rows="4" placeholder="Tell customers what you grow, how you pack orders, and where you deliver."><?= htmlspecialchars($seller['shop_bio'] ?: '') ?></textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-dark">Save shop profile</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card panel-card mb-4">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h2 class="h4 mb-1">Add Produce</h2>
                <p class="text-muted mb-0">List fresh crops for your customers.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product name</label>
                            <input type="text" name="name" class="form-control" placeholder="Organic Tomatoes" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="vegetables">Fresh Vegetables</option>
                                <option value="fruits">Fresh Fruits</option>
                                <option value="grains">Grains / Cereals</option>
                                <option value="cash_crops">Cash Crops</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (PHP)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stock</label>
                            <input type="number" name="quantity" min="0" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success">Publish product</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card panel-card">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h2 class="h4 mb-1">Your Products</h2>
                <p class="text-muted mb-0">Update inventory and keep stock accurate.</p>
            </div>
            <div class="card-body p-4">
                <?php if (empty($products)): ?>
                    <div class="alert alert-info mb-0">You have not listed any products yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                            <td style="width: 120px;">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid rounded" style="width: 96px; height: 96px; object-fit: cover;">
                                                <?php endif; ?>
                                                <input type="file" name="image" class="form-control form-control-sm mt-2">
                                            </td>
                                            <td>
                                                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($product['name']) ?>" required>
                                            </td>
                                            <td>
                                                <select name="category" class="form-select form-select-sm">
                                                    <option value="vegetables" <?= $product['category'] === 'vegetables' ? 'selected' : '' ?>>Fresh Vegetables</option>
                                                    <option value="fruits" <?= $product['category'] === 'fruits' ? 'selected' : '' ?>>Fresh Fruits</option>
                                                    <option value="grains" <?= $product['category'] === 'grains' ? 'selected' : '' ?>>Grains / Cereals</option>
                                                    <option value="cash_crops" <?= $product['category'] === 'cash_crops' ? 'selected' : '' ?>>Cash Crops</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?= htmlspecialchars($product['price']) ?>" required>
                                            </td>
                                            <td>
                                                <input type="number" name="quantity" class="form-control form-control-sm" value="<?= (int) $product['quantity'] ?>" min="0" required>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-sm btn-outline-dark">Update</button>
                                        </form>
                                                    <form method="POST" onsubmit="return confirm('Delete this product?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
