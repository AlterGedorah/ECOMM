<?php
session_start();
require_once 'config/Database.php';

$search = trim($_GET['q'] ?? '');
$products = [];

if (!empty($search)) {
    $database = new Database();
    $conn = $database->getConnection();


    $keywords = str_getcsv($search, ' ');


    $sql = "SELECT p.*, u.name AS seller_name, u.shop_name, u.shop_slug FROM products p LEFT JOIN users u ON u.id = p.seller_id WHERE p.name LIKE ? OR p.category LIKE ?";
    $like = "%$search%";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if (strpos(strtolower($row['name']), strtolower($search)) !== false) {
            $products[] = $row;
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Search Results - R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'components/navbar.comp.php' ?>
    <div class="container mt-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Search Results for: "<?= htmlspecialchars($search) ?>"</h2>
            <a href="index.php" class="btn btn-outline-success">← Back to Shop</a>
        </div>

        <?php if (empty($search)): ?>
            <div class="alert alert-warning">Please enter a search term.</div>
        <?php elseif (empty($products)): ?>
            <div class="alert alert-info">
                No products found for "<?= htmlspecialchars($search) ?>". Try different keywords.
            </div>
        <?php else: ?>
            <p>Found <?= count($products) ?> product(s)</p>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-12 col-md-6 col-lg-3 mt-3">
                        <div class="card rounded-5 overflow-hidden h-100">
                            <div
                                style="height: 200px; background-image: url('<?= $product['image_url'] ?? 'placeholder.jpg' ?>'); background-size: cover; background-position: center;">
                            </div>
                            <div class="p-3">
                                <h5><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="text-success">₱<?= number_format($product['price'], 2) ?></p>
                                <?php if (!empty($product['seller_name'])): ?>
                                    <div class="small text-muted mb-2">From <a href="/ECOMM/shop.php?shop=<?= urlencode($product['shop_slug']) ?>" class="text-decoration-none"><?= htmlspecialchars($product['shop_name'] ?: $product['seller_name']) ?></a></div>
                                <?php endif; ?>
                                <!-- Stock indicator -->
                                <?php if ($product['quantity'] > 0): ?>
                                    <div class="text-success small mt-1">✓ In Stock (<?= $product['quantity'] ?> available)</div>
                                <?php endif; ?>

                                <?php if ($product['quantity'] > 0): ?>
                                    <button class="mt-3 p-2 rounded-5 btn btn-outline-success w-100 buy-btn" data-bs-toggle="modal"
                                        data-bs-target="#productModal" data-id="<?= $product['id'] ?>"
                                        data-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= $product['price'] ?>"
                                        data-quantity="<?= $product['quantity'] ?>"
                                        data-image="<?= $product['image_url'] ?? 'placeholder.jpg' ?>">
                                        BUY
                                    </button>
                                <?php else: ?>
                                    <small class="text-danger">Out of Stock</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0"
                style="border-radius: 20px; overflow: hidden; background-color: #f5f2ee;">
                <!-- Close button -->
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    style="z-index:10;"></button>

                <!-- Top section: image + purchase panel -->
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <!-- Product Image -->
                        <div class="col-md-6 d-flex align-items-center justify-content-center p-4"
                            style="background-color: #f5f2ee;">
                            <img src="" id="modalImage" class="img-fluid"
                                style="max-height: 280px; object-fit: contain; border-radius: 16px;">
                        </div>

                        <!-- Right Panel -->
                        <div class="col-md-6 p-4 d-flex flex-column justify-content-center">
                            <h2 id="modalName" class="fw-bold text-uppercase mb-3" style="letter-spacing: 1px;"></h2>

                            <!-- Purchase Box -->
                            <div class="border rounded-4 p-4 bg-white shadow-sm">
                                <!-- Price -->
                                <p class="fw-semibold mb-3" id="modalPrice" style="font-size: 1.1rem;"></p>

                                <!-- Stock info -->
                                <div id="modalStock" class="mb-3"></div>

                                <form method="POST" action="add_to_cart.php" id="addToCartForm">
                                    <input type="hidden" name="product_id" id="modalProductId">

                                    <!-- Quantity -->
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

                                    <!-- Purchase Button -->
                                    <button type="submit" id="modalAddToCartBtn"
                                        class="btn w-100 py-3 fw-bold rounded-pill text-white"
                                        style="background-color: #1a5c2a; font-size: 1rem; letter-spacing: 0.5px;">
                                        Purchase
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom section: Your preferences -->
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

                    <!-- Disclaimer -->
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
</body>
 <!-- JavaScript to populate modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate modal when opened
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(element => {
            element.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const price = this.getAttribute('data-price');
                const quantity = parseInt(this.getAttribute('data-quantity'));
                let image = this.getAttribute('data-image');

                // Fix image path - add ./ if needed or use default
                if (!image || image === '' || image === 'null') {
                    image = '/ECOMM/placeholder.jpg';
                }

                // Debug - check what image path is
                console.log('Loading image:', image);

                // Set modal values
                document.getElementById('modalProductId').value = id;
                document.getElementById('modalName').innerText = name;
                document.getElementById('modalPrice').innerHTML = `₱${parseFloat(price).toFixed(2)}`;

                // Handle image loading with error fallback
                const modalImg = document.getElementById('modalImage');
                modalImg.src = image;
                modalImg.onerror = function () {
                    console.warn('Image failed to load:', image);
                    this.src = '/ECOMM/placeholder.jpg';
                };

                // Set quantity input max based on stock
                const qtyInput = document.getElementById('modalQuantity');
                if (qtyInput) {
                    qtyInput.max = quantity;
                    qtyInput.value = 1;

                    // Remove old listener to avoid duplicates
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

                // Show stock status in modal
                const stockDiv = document.getElementById('modalStock');
                const addToCartBtn = document.getElementById('modalAddToCartBtn');

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
            });
        });
    </script>
</html>