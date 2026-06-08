<?php

require_once 'controllers/AuthController.php';
require_once 'controllers/ProductController.php';

$auth = new AuthController();
if (!$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

$productController = new ProductController();

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
        }
    }
}

$products = $productController->adminIndex();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-light min-vh-100 p-3">
                <h4>R3 Farm Admin</h4>
                <hr>
                <div class="nav flex-column">
                    <a href="#products" class="text-light text-decoration-none py-1">Products</a>
                    <a href="#add" class="text-light text-decoration-none py-1">Add Product</a>
                    <a href="/Fresh Harvest/index.php" class="text-light text-decoration-none py-1">View Site</a>
                    <a href="/Fresh Harvest/logout.php" class="text-light text-decoration-none py-1 text-danger">Logout</a>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Product Management</h2>

                <!-- Flash messages - clean URL! -->
                <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                        <?= $_SESSION['flash']['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <!-- Add Product Form -->
                <div class="card mb-4 shadow" id="add">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Add New Product</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" action="/Fresh Harvest/admin_dashboard.php">
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
                                    <input type="number" step="0.01" name="price" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="quantity" value="0" min="0" class="form-control" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Product Image</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success">➕ Add Product</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products List -->
                <div class="card shadow" id="products">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">All Products</h4>
                    </div>
                    <div class="card-body">
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <form method="POST" enctype="multipart/form-data" action="/Fresh Harvest/admin_dashboard.php">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                
                                                <td><?= $product['id'] ?></td>
                                                
                                                <td>
                                                    <?php if ($product['image_url']): ?>
                                                        <img src="<?= $product['image_url'] ?>" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                                                    <?php endif; ?>
                                                    <input type="file" name="image" class="form-control form-control-sm mt-1">
                                                </td>
                                                
                                                <td>
                                                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>"
                                                        class="form-control form-control-sm" required>
                                                </td>
                                                
                                                <td>
                                                    <select name="category" class="form-select form-select-sm">
                                                        <option value="vegetables" <?= $product['category'] == 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
                                                        <option value="fruits" <?= $product['category'] == 'fruits' ? 'selected' : '' ?>>Fruits</option>
                                                        <option value="grains" <?= $product['category'] == 'grains' ? 'selected' : '' ?>>Grains</option>
                                                        <option value="cash_crops" <?= $product['category'] == 'cash_crops' ? 'selected' : '' ?>>Cash Crops</option>
                                                    </select>
                                                </td>
                                                
                                                <td>
                                                    <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>"
                                                        class="form-control form-control-sm" style="width: 100px;" required>
                                                </td>
                                                
                                                <td>
                                                    <input type="number" name="quantity" value="<?= $product['quantity'] ?>"
                                                        min="0" class="form-control form-control-sm" style="width: 100px;" required>
                                                </td>
                                                
                                                <td>
                                                    <button type="submit" class="btn btn-warning btn-sm">✏️ Update</button>
                                            </form>
                                            
                                            <form method="POST" action="/Fresh Harvest/admin_dashboard.php"
                                                style="display: inline-block; margin-left: 5px;"
                                                onsubmit="return confirm('⚠️ PERMANENT DELETE: <?= htmlspecialchars($product['name']) ?>?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                            </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>