<?php
session_start();
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/models/User.php';

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$shopSlug = trim($_GET['shop'] ?? ($_COOKIE['user_shop_slug'] ?? ''));

$userModel = new User();
$seller = $shopSlug !== '' ? $userModel->findByShopSlug($shopSlug) : null;

if (!$seller) {
    header('Location: /ECOMM/index.php');
    exit();
}

$productController = new ProductController();
$products = $productController->shopProducts($seller['shop_slug']);
$isOwner = isset($_COOKIE['user_id']) && (int) $_COOKIE['user_id'] === (int) $seller['id'];
$publicShopUrl = '/ECOMM/shop.php?shop=' . urlencode($seller['shop_slug']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seller['shop_name']) ?> - R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f7f4ee 0%, #edf5ed 100%);
        }
        .shop-hero {
            border-radius: 32px;
            overflow: hidden;
            background: linear-gradient(135deg, #234632 0%, #4d7f4c 55%, #c0d9a3 100%);
            color: #fff;
            box-shadow: 0 24px 60px rgba(16, 40, 24, .16);
        }
        .shop-badge {
            display: inline-block;
            padding: 7px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.18);
            font-size: .85rem;
        }
        .product-card {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(16, 40, 24, .08);
        }
        .hero-photo {
            min-height: 100%;
            background: linear-gradient(180deg, rgba(0,0,0,.2), rgba(0,0,0,.2)), url('/ECOMM/images/crops.jpg') center/cover;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/components/navbar.comp.php'; ?>

    <div class="container py-4">
        <div class="shop-hero mb-4">
            <div class="row g-0">
                <div class="col-lg-7 p-4 p-lg-5 d-flex align-items-center">
                    <div>
                        <div class="shop-badge mb-3">Verified farm shop</div>
                        <h1 class="display-5 fw-semibold mb-3"><?= htmlspecialchars($seller['shop_name']) ?></h1>
                        <p class="lead mb-3" style="max-width: 720px;">Direct harvests from local farms, packed fresh and sold straight to your kitchen.</p>
                        <p class="mb-2">Location: <?= htmlspecialchars($seller['farm_location'] ?: 'Not set') ?></p>
                        <p class="mb-4" style="max-width: 720px;"><?= htmlspecialchars($seller['shop_bio'] ?: 'A growing farm storefront focused on fresh produce, fair pricing, and faster delivery.') ?></p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="/ECOMM/index.php" class="btn btn-light">Back to Marketplace</a>
                            <?php if ($isOwner): ?>
                                <a href="/ECOMM/seller_dashboard.php" class="btn btn-outline-light">Manage Shop</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 hero-photo d-none d-lg-block"></div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">Shop</div>
                        <div class="h4 mb-1"><?= htmlspecialchars($seller['shop_name']) ?></div>
                        <div class="text-muted">Farmer seller storefront</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">Products</div>
                        <div class="display-6 fw-bold mb-0"><?= count($products) ?></div>
                        <div class="text-muted">Fresh items available now</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-secondary small text-uppercase">Shop link</div>
                        <div class="small text-break fw-semibold"><?= htmlspecialchars($publicShopUrl) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0">Fresh from this farm</h2>
            <span class="text-muted"><?= count($products) ?> item(s)</span>
        </div>

        <div class="row g-4 mb-5">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info">This shop has not listed any products yet.</div>
                </div>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                    <div class="card product-card h-100">
                        <div style="height: 240px; background: url('<?= htmlspecialchars($product['image_url'] ?: '/ECOMM/placeholder.jpg') ?>') center/cover;"></div>
                        <div class="card-body p-4">
                            <div class="small text-uppercase text-secondary mb-1"><?= htmlspecialchars($product['category']) ?></div>
                            <h3 class="h5 mb-2"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="fw-semibold text-success mb-3">PHP <?= number_format((float) $product['price'], 2) ?></div>
                            <div class="small mb-3 <?= ((int) $product['quantity'] > 0) ? 'text-success' : 'text-danger' ?>">
                                <?= ((int) $product['quantity'] > 0) ? 'In stock: ' . (int) $product['quantity'] : 'Out of stock' ?>
                            </div>
                            <?php if ((int) $product['quantity'] > 0): ?>
                                <button class="btn btn-success w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#productModal" data-id="<?= (int) $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= htmlspecialchars($product['price']) ?>" data-quantity="<?= (int) $product['quantity'] ?>" data-image="<?= htmlspecialchars($product['image_url'] ?: '/ECOMM/placeholder.jpg') ?>">Buy now</button>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 rounded-pill" disabled>Sold out</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0" style="border-radius: 24px; overflow: hidden; background: #f5f2ee;">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index: 10;"></button>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-6 p-4 d-flex align-items-center justify-content-center" style="background: #efeae1;">
                            <img src="" id="modalImage" class="img-fluid" style="max-height: 280px; object-fit: contain; border-radius: 16px;">
                        </div>
                        <div class="col-md-6 p-4 d-flex flex-column justify-content-center">
                            <h2 id="modalName" class="fw-bold mb-3"></h2>
                            <div class="border rounded-4 p-4 bg-white shadow-sm">
                                <p class="fw-semibold mb-3" id="modalPrice" style="font-size: 1.1rem;"></p>
                                <div id="modalStock" class="mb-3"></div>
                                <form method="POST" action="/ECOMM/add_to_cart.php">
                                    <input type="hidden" name="product_id" id="modalProductId">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" id="modalQuantity" value="1" min="1" max="99" class="form-control">
                                    </div>
                                    <button type="submit" id="modalAddToCartBtn" class="btn btn-success w-100 rounded-pill py-3">Add to cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(element => {
            element.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const price = this.getAttribute('data-price');
                const quantity = parseInt(this.getAttribute('data-quantity'), 10);
                const image = this.getAttribute('data-image') || '/ECOMM/placeholder.jpg';

                document.getElementById('modalProductId').value = id;
                document.getElementById('modalName').innerText = name;
                document.getElementById('modalPrice').innerHTML = 'PHP ' + parseFloat(price).toFixed(2);
                document.getElementById('modalImage').src = image;

                const qtyInput = document.getElementById('modalQuantity');
                qtyInput.max = quantity;
                qtyInput.value = 1;

                const stockDiv = document.getElementById('modalStock');
                const addToCartBtn = document.getElementById('modalAddToCartBtn');

                if (quantity > 0) {
                    stockDiv.innerHTML = '<div class="text-success small">' + quantity + ' available</div>';
                    addToCartBtn.disabled = false;
                    addToCartBtn.classList.remove('btn-secondary');
                    addToCartBtn.classList.add('btn-success');
                } else {
                    stockDiv.innerHTML = '<div class="text-danger small">Out of stock</div>';
                    addToCartBtn.disabled = true;
                    addToCartBtn.classList.add('btn-secondary');
                }
            });
        });
    </script>
</body>
</html>
