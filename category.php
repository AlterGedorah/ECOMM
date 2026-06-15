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
    <title><?= $categories[$category] ?> - Fresh Harvest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
</head>

<body>
    <div class="Background">
    <style>
        body {
            margin-top:  4.3rem !important;
        }

        .nav-link {
            color: white !important;
            transition: opacity 0.3s ease;
        }

        .nav-link:hover {
            opacity: 0.8;
        }

        .Background {
            background-image: url("images/background.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            background-attachment: scroll;
            min-height: 100vh;
        }

        #modalAddToCartBtn {
            font-size: 1rem;
            letter-spacing: 0.5px;
            background: #FFFFFF;
            border: 1px solid #00680A;
            border-radius: 20px;
        }
    </style>

    <?php include('components/navbar.comp.php') ?>
    <!-- Main Content -->
    <div class="container py-4 my-4">
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
                <?php $productUnit = trim((string) ($product['unit'] ?? '')); ?>
                <div class="col-12 col-md-6 col-lg-3 mt-4">
                    <div class="card rounded-4 overflow-hidden h-100 shadow-sm">
                        <div
                            style="height: 250px; background-image: url('<?= $product['image_url'] ?? 'placeholder.jpg' ?>'); background-size: cover; background-position: center;">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="text-success fw-bold mb-1">₱<?= number_format($product['current_price'], 2) ?></p>
                            <div class="text-muted small mb-2">/
                                <?= htmlspecialchars($productUnit !== '' ? $productUnit : 'unit') ?>
                            </div>

                            <?php if ($product['quantity'] > 0): ?>
                                <div class="text-success small mb-2">✓ In Stock (<?= $product['quantity'] ?> available)</div>
                                <button class="btn btn-outline-success w-100 rounded-pill buy-btn" data-bs-toggle="modal"
                                    data-bs-target="#productModal" data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-price="<?= $product['current_price'] ?>" data-quantity="<?= $product['quantity'] ?>"
                                    data-unit="<?= htmlspecialchars($productUnit) ?>"
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

    <!-- Refined Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0"
                style="border-radius: 20px; overflow: hidden; background-color: #f5f2ee;">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    style="z-index:10;"></button>

                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-6 d-flex align-items-center justify-content-center p-4"
                            style="background-color: #f5f2ee;">
                            <img src="" id="modalImage" class="img-fluid"
                                style="max-height: 280px; object-fit: contain; border-radius: 16px;">
                        </div>

                        <div class="col-md-6 p-4 d-flex flex-column justify-content-center">
                            <h2 id="modalName" class="display-6 text-uppercase mb-3" style="letter-spacing: 1px;"></h2>

                            <div class="border rounded-4 p-4 bg-white shadow-sm">
                                <p class="fw-semibold mb-3" id="modalPrice" style="font-size: 1.1rem;"></p>
                                <p class="text-muted mb-3" id="modalUnit" style="font-size: 0.95rem;"></p>

                                <div id="modalStock" class="mb-3"></div>

                                <form method="POST" action="add_to_cart.php" id="addToCartForm">
                                    <input type="hidden" name="product_id" id="modalProductId">

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center border rounded-pill px-4 py-2 bg-white"
                                            style="cursor:pointer;">
                                            <label class="form-label mb-0 fw-medium" style="font-size:0.95rem;">Add
                                                quantity</label>
                                            <input type="number" name="quantity" id="modalQuantity" value="1" min="1"
                                                max="99" class="border-0 text-end fw-semibold"
                                                style="width: 60px; outline:none; background:transparent; font-size:0.95rem;">
                                        </div>
                                    </div>

                                    <button type="submit" id="modalAddToCartBtn"
                                        class="btn w-100 py-3 fw-bold rounded-pill text-black mb-2">
                                        Add to Cart
                                    </button>
                                </form>

                                <form method="POST" action="buy_now.php" id="buyNowForm">
                                    <input type="hidden" name="product_id" id="buyNowProductId">
                                    <input type="hidden" name="quantity" id="buyNowQuantity" value="1">
                                    <button type="submit" id="modalBuyNowBtn"
                                        class="btn w-100 py-3 fw-bold rounded-pill text-white"
                                        style="background-color: #1a5c2a; font-size: 1rem; letter-spacing: 0.5px;">
                                        Purchase
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="border-top px-4 py-4" style="background-color: #f5f2ee;">
                        <h5 class="fw-bold mb-3">Your preferences</h5>
                        <label class="form-label fw-semibold mb-2">Special Instruction</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="modalNote" class="form-control rounded-pill"
                                placeholder="I would like to..."
                                style="max-width: 360px; background: white; border: 1px solid #ccc;">
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-secondary rounded-pill px-4 py-1"
                                onclick="document.getElementById('modalNote').value=''">Cancel</button>
                            <button class="btn rounded-pill px-4 py-1 text-white fw-semibold"
                                style="background-color: #1a5c2a;">Save</button>
                        </div>
                    </div>

                    <div class="border-top px-4 py-3"
                        style="background-color: #f5f2ee; font-size: 0.75rem; color: #666;">
                        Product information or packaging displayed may not be current or complete. Always refer to the
                        physical product for the most accurate information.
                        <br><em>*Actual weight may vary based on seasonality and other factors. Estimated price is
                            approximate and provided only for reference.</em>
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate modal when opened
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(element => {
            element.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const price = this.getAttribute('data-price');
                const quantity = parseInt(this.getAttribute('data-quantity'));
                const unit = this.getAttribute('data-unit');
                let image = this.getAttribute('data-image');

                if (!image || image === '' || image === 'null') {
                    image = '/ECOMM/placeholder.jpg';
                }

                console.log('Loading image:', image);

                document.getElementById('modalProductId').value = id;
                document.getElementById('modalName').innerText = name;
                document.getElementById('modalPrice').innerHTML = `₱${parseFloat(price).toFixed(2)}`;
                document.getElementById('modalUnit').innerText = unit ? `Unit: ${unit}` : 'Unit: item';

                const modalImg = document.getElementById('modalImage');
                modalImg.src = image;
                modalImg.onerror = function () {
                    console.warn('Image failed to load:', image);
                    this.src = '/ECOMM/placeholder.jpg';
                };

                const qtyInput = document.getElementById('modalQuantity');
                if (qtyInput) {
                    qtyInput.max = quantity;
                    qtyInput.value = 1;

                    const newQtyInput = qtyInput.cloneNode(true);
                    qtyInput.parentNode.replaceChild(newQtyInput, qtyInput);

                    newQtyInput.addEventListener('change', function () {
                        if (this.value > quantity) {
                            this.value = quantity;
                            alert(`Only ${quantity} items available!`);
                        }
                        if (this.value < 1) {
                            this.value = 1;
                        }
                    });
                }

                const stockDiv = document.getElementById('modalStock');
                const addToCartBtn = document.getElementById('modalAddToCartBtn');
                const buyNowProductId = document.getElementById('buyNowProductId');
                const buyNowQuantity = document.getElementById('buyNowQuantity');

                if (stockDiv && addToCartBtn) {
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
                }

                if (buyNowProductId) buyNowProductId.value = id;
                if (buyNowQuantity) buyNowQuantity.value = document.getElementById('modalQuantity')?.value || 1;
            });
        });

        document.getElementById('modalQuantity')?.addEventListener('change', function () {
            const buyNowQuantity = document.getElementById('buyNowQuantity');
            if (buyNowQuantity) {
                buyNowQuantity.value = this.value;
            }
        });
    </script>
</body>

</html>