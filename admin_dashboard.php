<?php
session_start();
require_once 'controllers/AuthController.php';
require_once 'controllers/ProductController.php';
require_once 'controllers/OrderController.php';

$auth = new AuthController();
if (!$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

$productController = new ProductController();
$orderController   = new OrderController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $productController->create();
                break;
            case 'update':
                $productController->update();
                break;
            case 'delete':
                $productController->delete();
                break;
            case 'update_order':
                $orderId = (int) ($_POST['order_id'] ?? 0);
                $newStatus     = $_POST['order_status']   ?? '';
                $newPayment    = $_POST['payment_status'] ?? '';
                $adminNotes    = trim($_POST['admin_notes'] ?? '');
                $allowedOrder  = ['pending','confirmed','processing','shipped','delivered','cancelled'];
                $allowedPay    = ['unpaid','paid','refunded','failed'];
                if ($orderId > 0 && in_array($newStatus, $allowedOrder, true) && in_array($newPayment, $allowedPay, true)) {
                   $adminId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 1; // Fallback to 1 (Admin User) if session key differ
                    $res = $orderController->updateStatus($orderId, $newStatus, $newPayment, $adminNotes, (int)$adminId);
                    if ($res['success']) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => "Order #{$orderId} updated successfully."];
                    } else {
                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update order: ' . $res['error']];
                    }
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid order update submitted.'];
                }
                header("Location: /Fresh Harvest/admin_dashboard.php#orders");
                exit();
        }
    }
}

$products = $productController->adminIndex();
$orders   = $orderController->adminIndex();

// Order summary counts
$orderStats = [
    'total'      => count($orders),
    'pending'    => 0,
    'confirmed'  => 0,
    'processing' => 0,
    'shipped'    => 0,
    'delivered'  => 0,
    'cancelled'  => 0,
    'revenue'    => 0.0,
];
foreach ($orders as $o) {
    $st = $o['order_status'];
    if (isset($orderStats[$st])) {
        $orderStats[$st]++;
    }
    if (!in_array($st, ['cancelled'], true) && $o['payment_status'] !== 'refunded') {
        $orderStats['revenue'] += (float) $o['total_amount'];
    }
}

// Helper for badge colors
function orderStatusBadge(string $s): string {
    $map = [
        'pending'    => 'bg-secondary',
        'confirmed'  => 'bg-info text-dark',
        'processing' => 'bg-primary',
        'shipped'    => 'bg-warning text-dark',
        'delivered'  => 'bg-success',
        'cancelled'  => 'bg-danger',
    ];
    return $map[$s] ?? 'bg-secondary';
}
function paymentStatusBadge(string $s): string {
    $map = [
        'unpaid'   => 'bg-secondary',
        'paid'     => 'bg-success',
        'refunded' => 'bg-info text-dark',
        'failed'   => 'bg-danger',
    ];
    return $map[$s] ?? 'bg-secondary';
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 bg-dark text-light">
                <div class="position-sticky top-0 vh-100 overflow-auto p-3">
                    <h4>Fresh Harvest Admin</h4>
                    <hr>
                    <div class="nav flex-column">
                        <a href="#products" class="text-light text-decoration-none py-1">Products</a>
                        <a href="#add" class="text-light text-decoration-none py-1">Add Product</a>
                        <a href="#orders" class="text-light text-decoration-none py-1">Orders</a>
                        <a href="/Fresh Harvest/index.php" class="text-light text-decoration-none py-1">View Site</a>
                        <a href="/Fresh Harvest/logout.php" class="text-light text-decoration-none py-1 text-danger">Logout</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Product Management</h2>

                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                        <?= $_SESSION['flash']['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <div class="card mb-4 shadow" id="add">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Add New Product</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" action="admin_dashboard.php">
                            <input type="hidden" name="action" value="create">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select">
                                        <option value="vegetables">Fresh Vegetables</option>
                                        <option value="fruits">Fresh Fruits</option>
                                        <option value="grains">Grains / Cereals</option>
                                        <option value="cash_crops">Cash Crops</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (₱)</label>
                                    <input type="number" min="0" step="0.01" name="price" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="quantity" value="0" min="0" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Unit</label>
                                    <select name="unit" class="form-select">
                                        <option value="">-- Select unit --</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                        <option value="liter">Liter</option>
                                        <option value="sack">Sack</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Harvest Date</label>
                                    <input type="date" name="harvest_date" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Product Image</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success">Add Product</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow" id="products">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h4 class="mb-0">All Products</h4>
                        <span class="badge bg-light text-dark" id="productCountBadge">
                            <?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Product search / filter -->
                        <form method="GET" action="admin_dashboard.php#products" class="row g-2 mb-3" id="productSearchForm">
                            <div class="col-md-4">
                                <input type="search" name="product_search"
                                    class="form-control form-control-sm"
                                    placeholder="Search by product name…"
                                    value="<?= htmlspecialchars($_GET['product_search'] ?? '') ?>"
                                    autocomplete="off">
                            </div>
                            <div class="col-md-3">
                                <select name="product_category" class="form-select form-select-sm">
                                    <?php $pc = $_GET['product_category'] ?? 'all'; ?>
                                    <option value="all"       <?= $pc==='all'?'selected':'' ?>>All categories</option>
                                    <option value="vegetables"<?= $pc==='vegetables'?'selected':'' ?>>Fresh Vegetables</option>
                                    <option value="fruits"    <?= $pc==='fruits'?'selected':'' ?>>Fresh Fruits</option>
                                    <option value="grains"    <?= $pc==='grains'?'selected':'' ?>>Grains / Cereals</option>
                                    <option value="cash_crops"<?= $pc==='cash_crops'?'selected':'' ?>>Cash Crops</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button class="btn btn-sm btn-success w-100" type="submit">Search</button>
                            </div>
                            <div class="col-md-2">
                                <?php $hasActiveSearch = ($_GET['product_search'] ?? '') !== '' || (($_GET['product_category'] ?? 'all') !== 'all'); ?>
                                <?php if ($hasActiveSearch): ?>
                                    <a href="admin_dashboard.php#products" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary w-100" type="button" disabled>Clear</button>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if ($hasActiveSearch): ?>
                            <div class="small text-muted mb-2">
                                <?php if (($_GET['product_search'] ?? '') !== ''): ?>
                                    Showing results for
                                    <strong>"<?= htmlspecialchars($_GET['product_search']) ?>"</strong>
                                <?php endif; ?>
                                <?php if (($_GET['product_category'] ?? 'all') !== 'all'): ?>
                                    <?= ($_GET['product_search'] ?? '') !== '' ? ' in ' : 'Showing ' ?>
                                    <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $_GET['product_category']))) ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                            <?php if (empty($products)): ?>
                            <div class="alert alert-warning mb-0" role="alert">
                                No products match your search.
                                <?php if ($hasActiveSearch): ?>
                                    <a href="admin_dashboard.php#products" class="alert-link">Clear the filter</a> to see all products.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Unit</th>
                                        <th>Harvest Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <?php $formId = "update_form_" . $product['id']; ?>
                                        <tr>
                                            <td><?= $product['id'] ?></td>
                                            
                                            <td>
                                                <?php if ($product['image_url']): ?>
                                                    <img src="<?= $product['image_url'] ?>" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                                                <?php endif; ?>
                                                <input type="file" name="image" form="<?= $formId ?>" class="form-control form-control-sm mt-1">
                                            </td>
                                            
                                            <td>
                                                <input type="text" name="name" form="<?= $formId ?>" value="<?= htmlspecialchars($product['name']) ?>"
                                                    class="form-control form-control-sm" required>
                                            </td>
                                            
                                            <td>
                                                <select name="category" form="<?= $formId ?>" class="form-select form-select-sm">
                                                    <option value="vegetables" <?= $product['category'] == 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
                                                    <option value="fruits" <?= $product['category'] == 'fruits' ? 'selected' : '' ?>>Fruits</option>
                                                    <option value="grains" <?= $product['category'] == 'grains' ? 'selected' : '' ?>>Grains</option>
                                                    <option value="cash_crops" <?= $product['category'] == 'cash_crops' ? 'selected' : '' ?>>Cash Crops</option>
                                                </select>
                                            </td>
                                            
                                            <td>
                                                <input type="number" min="0" step="0.01" name="price" form="<?= $formId ?>" value="<?= $product['current_price'] ?>"
                                                    class="form-control form-control-sm" style="width: 100px;" required>
                                            </td>
                                            
                                            <td>
                                                <input type="number" name="quantity" form="<?= $formId ?>" value="<?= $product['quantity'] ?>"
                                                    min="0" class="form-control form-control-sm" style="width: 100px;" required>
                                            </td>

                                            <td>
                                                <select name="unit" form="<?= $formId ?>" class="form-select form-select-sm" style="width: 110px;">
                                                    <option value="" <?= empty($product['unit']) ? 'selected' : '' ?>>--</option>
                                                    <option value="kg" <?= $product['unit'] == 'kg' ? 'selected' : '' ?>>kg</option>
                                                    <option value="pcs" <?= $product['unit'] == 'pcs' ? 'selected' : '' ?>>pcs</option>
                                                    <option value="liter" <?= $product['unit'] == 'liter' ? 'selected' : '' ?>>liter</option>
                                                    <option value="sack" <?= $product['unit'] == 'sack' ? 'selected' : '' ?>>sack</option>
                                                </select>
                                            </td>

                                            <td>
                                                <input type="date" name="harvest_date" form="<?= $formId ?>"
                                                    value="<?= htmlspecialchars($product['harvest_date'] ?? date('Y-m-d')) ?>"
                                                    class="form-control form-control-sm" style="width: 150px;">
                                            </td>

                                            <td>
                                                <form id="<?= $formId ?>" method="POST" enctype="multipart/form-data" action="admin_dashboard.php" style="display: inline-block;">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                                </form>
                                                
                                                <form method="POST" action="admin_dashboard.php"
                                                    style="display: inline-block; margin-left: 5px;"
                                                    onsubmit="return confirm('PERMANENT DELETE: <?= htmlspecialchars($product['name']) ?>?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow" id="orders">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Order Management</h4>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-secondary">Total: <?= $orderStats['total'] ?></span>
                            <span class="badge bg-info text-dark">Confirmed: <?= $orderStats['confirmed'] ?></span>
                            <span class="badge bg-primary">Processing: <?= $orderStats['processing'] ?></span>
                            <span class="badge bg-warning text-dark">Shipped: <?= $orderStats['shipped'] ?></span>
                            <span class="badge bg-success">Delivered: <?= $orderStats['delivered'] ?></span>
                            <span class="badge bg-danger">Cancelled: <?= $orderStats['cancelled'] ?></span>
                            <span class="badge bg-dark">Revenue: ₱<?= number_format($orderStats['revenue'], 2) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="admin_dashboard.php#orders" class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select name="order_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <?php $f = $_GET['order_filter'] ?? 'all'; ?>
                                    <option value="all"      <?= $f==='all'?'selected':'' ?>>All statuses</option>
                                    <option value="pending"    <?= $f==='pending'?'selected':'' ?>>Pending</option>
                                    <option value="confirmed"  <?= $f==='confirmed'?'selected':'' ?>>Confirmed</option>
                                    <option value="processing" <?= $f==='processing'?'selected':'' ?>>Processing</option>
                                    <option value="shipped"    <?= $f==='shipped'?'selected':'' ?>>Shipped</option>
                                    <option value="delivered"  <?= $f==='delivered'?'selected':'' ?>>Delivered</option>
                                    <option value="cancelled"  <?= $f==='cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="search" name="order_search" class="form-control form-control-sm"
                                    placeholder="Search order #, name, email…"
                                    value="<?= htmlspecialchars($_GET['order_search'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-dark w-100" type="submit">Apply</button>
                            </div>
                        </form>

                        <?php
                            $visibleOrders = array_filter($orders, function ($o) use ($f) {
                                if ($f === 'all') return true;
                                return $o['order_status'] === $f;
                            });
                            $q = strtolower(trim($_GET['order_search'] ?? ''));
                            if ($q !== '') {
                                $visibleOrders = array_filter($visibleOrders, function ($o) use ($q) {
                                    $hay = strtolower(($o['order_number'] ?? '') . ' ' .
                                        ($o['first_name'] ?? '') . ' ' .
                                        ($o['last_name']  ?? '') . ' ' .
                                        ($o['email']      ?? ''));
                                    return strpos($hay, $q) !== false;
                                });
                            }
                        ?>

                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark position-sticky top-0 z-1">
                                    <tr>
                                        <th>#</th>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Shipping Address</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Order Status</th>
                                        <th>Placed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($visibleOrders)): ?>
                                        <tr><td colspan="11" class="text-center text-muted py-4">No orders match the current filter.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($visibleOrders as $o): ?>
                                            <?php
                                                $fullName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                                                $addr = trim(implode(', ', array_filter([
                                                    $o['street_address'] ?? '',
                                                    $o['barangay']       ?? '',
                                                    $o['city']           ?? '',
                                                    $o['region']         ?? '',
                                                    $o['postal_code']    ?? '',
                                                ])));
                                                $detailed = $orderController->adminShow((int)$o['id']);
                                                $itemCount = is_array($detailed['items'] ?? null) ? count($detailed['items']) : 0;
                                            ?>
                                            <tr>
                                                <td><?= (int)$o['id'] ?></td>
                                                <td><code><?= htmlspecialchars($o['order_number']) ?></code></td>
                                                <td><?= htmlspecialchars($fullName ?: '—') ?></td>
                                                <td>
                                                    <small>
                                                        <?= htmlspecialchars($o['email'] ?? '—') ?><br>
                                                        <?= htmlspecialchars($o['phone_num'] ?? '') ?>
                                                    </small>
                                                </td>
                                                <td><small><?= htmlspecialchars($addr !== '' ? $addr : '—') ?></small></td>
                                                <td><span class="badge bg-secondary"><?= $itemCount ?></span></td>
                                                <td><strong>₱<?= number_format((float)$o['total_amount'], 2) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars(str_replace('_',' ', $o['payment_method'])) ?></small>
                                                    <?php if (!empty($o['payment_proof'])): ?>
                                                        <br><span class="badge bg-info text-dark mt-1" title="GCash payment proof attached">
                                                            <i class="bi bi-paperclip"></i> Proof
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge <?= paymentStatusBadge($o['payment_status']) ?>"><?= htmlspecialchars($o['payment_status']) ?></span></td>
                                                <td><span class="badge <?= orderStatusBadge($o['order_status']) ?>"><?= htmlspecialchars($o['order_status']) ?></span></td>
                                                <td><small><?= htmlspecialchars($o['created_at']) ?></small></td>
                                                <td>
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#orderModal<?= (int)$o['id'] ?>">
                                                        Manage
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($visibleOrders)): ?>
                            <?php foreach ($visibleOrders as $o): ?>
                                <?php
                                    $fullName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                                    $addr = trim(implode(', ', array_filter([
                                        $o['street_address'] ?? '',
                                        $o['barangay']       ?? '',
                                        $o['city']           ?? '',
                                        $o['region']         ?? '',
                                        $o['postal_code']    ?? '',
                                    ])));
                                    $detailed = $orderController->adminShow((int)$o['id']);
                                ?>
                               <div class="modal fade" id="orderModal<?= (int)$o['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                        <form method="POST" action="admin_dashboard.php#orders" class="modal-content">
                                            <input type="hidden" name="action" value="update_order">
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        Order <code><?= htmlspecialchars($o['order_number']) ?></code>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6 class="text-muted mb-1">Customer</h6>
                                                            <div><strong><?= htmlspecialchars($fullName ?: '—') ?></strong></div>
                                                            <div><small><?= htmlspecialchars($o['email'] ?? '') ?></small></div>
                                                            <div><small><?= htmlspecialchars($o['phone_num'] ?? '') ?></small></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="text-muted mb-1">Shipping Address</h6>
                                                            <div><small><?= htmlspecialchars($addr !== '' ? $addr : '—') ?></small></div>
                                                        </div>
                                                    </div>

                                                    <h6>Items</h6>
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Product</th>
                                                                <th class="text-end">Qty</th>
                                                                <th class="text-end">Unit</th>
                                                                <th class="text-end">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach (($detailed['items'] ?? []) as $it): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($it['product_name']) ?></td>
                                                                    <td class="text-end"><?= (int)$it['quantity'] ?></td>
                                                                    <td class="text-end">₱<?= number_format((float)$it['price_per_unit'], 2) ?></td>
                                                                    <td class="text-end">₱<?= number_format((float)$it['total_price'], 2) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end">₱<?= number_format((float)$o['subtotal'], 2) ?></th></tr>
                                                            <tr><th colspan="3" class="text-end">Delivery</th><th class="text-end">₱<?= number_format((float)$o['delivery_fee'], 2) ?></th></tr>
                                                            <tr><th colspan="3" class="text-end">Discount</th><th class="text-end">-₱<?= number_format((float)$o['discount_amount'], 2) ?></th></tr>
                                                            <tr><th colspan="3" class="text-end">Total</th><th class="text-end">₱<?= number_format((float)$o['total_amount'], 2) ?></th></tr>
                                                        </tfoot>
                                                    </table>

                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Order Status</label>
                                                            <select name="order_status" class="form-select form-select-sm">
                                                                <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                                                                    <option value="<?= $s ?>" <?= $o['order_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Payment Status</label>
                                                            <select name="payment_status" class="form-select form-select-sm">
                                                                <?php foreach (['unpaid','paid','refunded','failed'] as $p): ?>
                                                                    <option value="<?= $p ?>" <?= $o['payment_status']===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label">Admin Notes</label>
                                                            <textarea name="admin_notes" rows="2" class="form-control form-control-sm"
                                                                placeholder="Internal notes (visible only to admins)"><?= htmlspecialchars($o['admin_notes'] ?? '') ?></textarea>
                                                        </div>
                                                    </div>

                                                    <?php
                                                        // Only display the proof block when the order was paid
                                                        // via GCash AND a file was attached. We resolve the path
                                                        // against the project root and check it actually exists
                                                        // on disk so missing files don't show a broken icon.
                                                        $proofRel = $o['payment_proof'] ?? '';
                                                        $proofAbs = $proofRel !== ''
                                                            ? realpath(__DIR__ . DIRECTORY_SEPARATOR . $proofRel)
                                                            : false;
                                                        $hasProof = ($proofRel !== '' && $proofAbs !== false && is_file($proofAbs));
                                                    ?>
                                                    <?php if ($hasProof): ?>
                                                        <div class="mt-3">
                                                            <h6 class="mb-2">
                                                                <i class="bi bi-image me-1"></i>GCash payment proof
                                                            </h6>
                                                            <a href="<?= htmlspecialchars($proofRel) ?>" target="_blank" rel="noopener">
                                                                <img
                                                                    src="<?= htmlspecialchars($proofRel) ?>"
                                                                    alt="GCash payment proof for order <?= htmlspecialchars($o['order_number']) ?>"
                                                                    class="img-fluid rounded border"
                                                                    style="max-height:280px;cursor:zoom-in;background:#fafafa;" />
                                                            </a>
                                                            <div class="small text-muted mt-1">
                                                                <i class="bi bi-paperclip me-1"></i>
                                                                <?= htmlspecialchars(basename($proofRel)) ?>
                                                                <span class="ms-2">·</span>
                                                                <a href="<?= htmlspecialchars($proofRel) ?>" target="_blank" rel="noopener" class="ms-2">
                                                                    Open in new tab <i class="bi bi-box-arrow-up-right"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($o['payment_method'] === 'gcash'): ?>
                                                        <div class="alert alert-warning small mt-3 mb-0" role="alert">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            Customer selected GCash but no payment proof was uploaded.
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($detailed['history'])): ?>
                                                        <h6 class="mt-4">Status History</h6>
                                                        <ul class="list-group list-group-flush small">
                                                            <?php foreach ($detailed['history'] as $h): ?>
                                                                <li class="list-group-item d-flex justify-content-between">
                                                                    <span>
                                                                        <span class="badge <?= orderStatusBadge($h['status']) ?>"><?= htmlspecialchars($h['status']) ?></span>
                                                                        <?= htmlspecialchars($h['notes'] ?? '') ?>
                                                                    </span>
                                                                    <span class="text-muted">
                                                                        <?= htmlspecialchars($h['changed_by_name'] ?? 'System') ?>
                                                                        · <?= htmlspecialchars($h['changed_at']) ?>
                                                                    </span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-success"
                                                            onclick="return confirm('Save changes to order <?= htmlspecialchars($o['order_number']) ?>?');">
                                                        Save Changes
                                                    </button>
                                                </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    /**
     * Negative-input validator for every <input type="number"> on the page.
     *
     * Catches negatives entered via:
     *   - Typing "-" or "+" or "e" / "E"           (blocked on keydown)
     *   - Paste / drop                                 (stripped on input)
     *   - Spinner down arrow past 0                    (clamped on input)
     *   - Programmatic / devtools value                (re-checked on blur)
     *
     * Also blocks form submission if anything negative slips through
     * (e.g. JS disabled → server still rejects, but the alert helps the user).
     */
    (function () {
        'use strict';

        // Marks numeric inputs that must be >= 0. We mark BOTH the static
        // (Add Product) form AND the dynamically-generated per-product
        // Update forms by checking the field name(s) instead of using
        // a fixed ID list.
        const NEGATIVE_FIELD_NAMES = new Set(['price', 'quantity']);
        const NUMBER_INPUT_SELECTOR =
            'input[type="number"][name="' +
            Array.from(NEGATIVE_FIELD_NAMES).join('"], input[type="number"][name="') +
            '"]';

        function markInvalid(input, message) {
            input.classList.add('is-invalid');
            input.setCustomValidity(message || 'Value cannot be negative.');

            // Show / refresh the inline feedback. We keep one feedback
            // element per input to avoid stacking duplicates.
            let fb = input.parentElement.querySelector('.invalid-feedback.no-neg-fb');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'invalid-feedback no-neg-fb';
                input.insertAdjacentElement('afterend', fb);
            }
            fb.textContent = message || 'Value cannot be negative.';
        }

        function clearInvalid(input) {
            input.classList.remove('is-invalid');
            input.setCustomValidity('');
            const fb = input.parentElement.querySelector('.invalid-feedback.no-neg-fb');
            if (fb) fb.remove();
        }

        // Strip any character that would let a negative sneak in.
        //   "-" / "+"  → sign characters
        //   "e" / "E"  → exponent (lets users type "1e-5")
        function sanitizeValue(input) {
            const original = input.value;
            // Allow digits, a single "." for decimals, and up to one leading "0."
            const cleaned = original.replace(/[eE+\-]/g, '');
            if (cleaned !== original) {
                input.value = cleaned;
            }
        }

        function enforceNonNegative(input) {
            sanitizeValue(input);
            if (input.value === '' || input.value === '.' ) {
                return; // Let "required" / native validation handle emptiness
            }
            const num = Number(input.value);
            if (Number.isNaN(num)) {
                markInvalid(input, 'Please enter a valid number.');
                return false;
            }
            if (num < 0) {
                markInvalid(input, 'Value cannot be negative.');
                return false;
            }
            clearInvalid(input);
            return true;
        }

        function attach(input) {
            if (input.dataset.noNegBound === '1') return;
            input.dataset.noNegBound = '1';
            // Belt + braces: also force min=0 in case HTML5 attr is missing.
            input.setAttribute('min', '0');

            input.addEventListener('keydown', function (e) {
                if (e.key === '-' || e.key === '+' || e.key === 'e' || e.key === 'E') {
                    e.preventDefault();
                    markInvalid(input, 'Value cannot be negative.');
                }
            });

            input.addEventListener('input', function () {
                // Clear any previous error as soon as the user changes something
                if (input.classList.contains('is-invalid')) {
                    clearInvalid(input);
                }
                enforceNonNegative(input);
            });

            input.addEventListener('blur', function () {
                enforceNonNegative(input);
            });

            // Re-check on form submit (covers programmatic changes too)
            const form = input.closest('form');
            if (form && !form.dataset.noNegBound) {
                form.dataset.noNegBound = '1';
                form.addEventListener('submit', function (e) {
                    let ok = true;
                    form.querySelectorAll(NUMBER_INPUT_SELECTOR).forEach(function (i) {
                        if (!enforceNonNegative(i)) {
                            ok = false;
                        }
                    });
                    if (!ok) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Focus the first invalid field for the user
                        const firstBad = form.querySelector('.is-invalid');
                        if (firstBad) firstBad.focus();
                    }
                });
            }
        }

        function bindAll() {
            document.querySelectorAll(NUMBER_INPUT_SELECTOR).forEach(attach);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindAll);
        } else {
            bindAll();
        }
    })();
    </script>
</body>

</html>