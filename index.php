<?php
session_start();
require_once 'controllers/ProductController.php';

$productController = new ProductController();
$data = $productController->index();
$categories = $data['categories'];
$products = $data['products'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar (same as your template) -->
    <div class="p-3 shadow-lg px-5 text-light" style="background-color: #385144;">
        <div class="d-flex flex-row justify-content-between">
            <div class="d-flex flex-row align-items-center">
                <div class="me-5"><!-- SVG logo --></div>
                <div class="me-5"><a href="index.php" class="text-light text-decoration-none">Home</a></div>
                <div class="me-5">Product</div>
                <div class="me-5">Services</div>
            </div>
            <div class="d-flex flex-row align-items-center">
                <input class="form-control rounded-5 p-2" placeholder="Search...">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="ms-3">Welcome, <?= $_SESSION['user_name'] ?></div>
                    <div class="ms-3"><a href="controllers/AuthController.php?action=logout" class="text-light">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="ms-3"><a href="login.php" class="text-light">Login</a></div>
                    <div class="ms-3"><a href="register.php" class="text-light">Register</a></div>
                <?php endif; ?>
                <div class="ms-3">
                    <a href="cart.php" class="text-light text-decoration-none">
                        Cart (<span class="cart-count"><?= count($_SESSION['cart'] ?? []) ?></span>)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero section (same) -->
    <div class="container mt-5 text-light" style="background-color: #385144; height: 478px;">
        <img src="images/Poster.png" alt="Poster" class="img-fluid h-100 w-100" style="object-fit: cover;">
    </div>

    <!-- Dynamic product sections with MODAL -->
    <?php foreach ($categories as $key => $label): ?>
        <div class="m-5">
            <div class="h3"><?= $label ?></div>
            <div class="row">
                <?php foreach ($products[$key] as $product): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 mt-3">
                        <div class="card rounded-5 overflow-hidden h-100">
                            <div style="height: 300px; background-image: url('<?= $product['image_url'] ?? 'placeholder.jpg' ?>'); background-size: cover; background-position: center; cursor: pointer;"
                                data-bs-toggle="modal" data-bs-target="#productModal" data-id="<?= $product['id'] ?>"
                                data-name="<?= htmlspecialchars($product['name']) ?>"
                                data-price-min="<?= $product['price_min'] ?>" data-price-max="<?= $product['price_max'] ?>"
                                data-image="<?= $product['image_url'] ?? 'placeholder.jpg' ?>">
                            </div>
                            <div class="p-4" style="background-color: #F6F3F0;">
                                <div class="h4"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="text-secondary">₱<?= number_format($product['price_min'], 2) ?> -
                                    ₱<?= number_format($product['price_max'], 2) ?></div>
                                <button class="mt-3 p-2 rounded-5 btn btn-outline-success w-100 buy-btn" data-bs-toggle="modal"
                                    data-bs-target="#productModal" data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-price-min="<?= $product['price_min'] ?>" data-price-max="<?= $product['price_max'] ?>"
                                    data-image="<?= $product['image_url'] ?? 'placeholder.jpg' ?>">
                                    BUY
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Product Modal -->
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
                            <p class="mb-2">Fresh from local farms. Quality guaranteed.</p>

                            <form method="POST" action="add_to_cart.php" id="addToCartForm">
                                <input type="hidden" name="product_id" id="modalProductId">
                                <div class="mb-3">
                                    <label class="form-label">Quantity:</label>
                                    <input type="number" name="quantity" value="1" min="1" max="99" class="form-control"
                                        style="width: 100px;">
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-2 rounded-5">
                                    Add to Cart
                                </button>
                            </form>

                            <button class="btn btn-outline-secondary w-100 mt-2 rounded-5" data-bs-dismiss="modal">
                                Continue Shopping
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
        // Populate modal when opened
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(element => {
            element.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const priceMin = this.getAttribute('data-price-min');
                const priceMax = this.getAttribute('data-price-max');
                const image = this.getAttribute('data-image');

                document.getElementById('modalProductId').value = id;
                document.getElementById('modalName').innerText = name;
                document.getElementById('modalPrice').innerHTML = `₱${parseFloat(priceMin).toFixed(2)} - ₱${parseFloat(priceMax).toFixed(2)}`;
                document.getElementById('modalImage').src = image;
            });
        });

        // AJAX form submission (optional - updates cart count without reload)
        document.getElementById('addToCartForm')?.addEventListener('submit', function (e) {
            e.preventDefault();

            fetch(this.action + '&ajax=1', {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count in navbar
                        const cartBadge = document.querySelector('.cart-count');
                        if (cartBadge) cartBadge.innerText = data.cart_count;

                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();

                        // Show toast
                        alert('Added to cart!');
                    }
                })
                .catch(error => {
                    // Fallback to normal form submit
                    this.submit();
                });
        });
    </script>

</html>