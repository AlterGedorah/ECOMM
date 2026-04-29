<?php
session_start();
require_once 'controllers/AuthController.php';
require_once 'controllers/ProductController.php';

$auth = new AuthController();
if(!$auth->isAdmin()) {
    header("Location: login.php");
    exit();
}

$productController = new ProductController();

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
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
            <div class="col-md-3 bg-dark text-light vh-100 p-3">
                <h4>R3 Farm Admin</h4>
                <hr>
                <div class="nav flex-column">
                    <a href="#products" class="text-light">Products</a>
                    <a href="#add" class="text-light">Add Product</a>
                    <a href="index.php" class="text-light">View Site</a>
                    <a href="controllers/AuthController.php?action=logout" class="text-light">Logout</a>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 p-4">
                <h2>Product Management</h2>
                
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?= $_GET['success'] ?></div>
                <?php endif; ?>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?= $_GET['error'] ?></div>
                <?php endif; ?>
                
                <!-- Add Product Form -->
                <div class="card mb-4" id="add">
                    <div class="card-header bg-success text-white">
                        <h4>Add New Product</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Product Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Category</label>
                                    <select name="category" class="form-control">
                                        <option value="vegetables">Fresh Vegetables</option>
                                        <option value="fruits">Fresh Fruits</option>
                                        <option value="grains">Grains / Cereals</option>
                                        <option value="cash_crops">Cash Crops</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Min Price (₱)</label>
                                    <input type="number" step="0.01" name="price_min" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Max Price (₱)</label>
                                    <input type="number" step="0.01" name="price_max" class="form-control" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label>Product Image</label>
                                    <input type="file" name="image" class="form-control" accept="image/*" required>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success">Add Product</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products List -->
                <div class="card" id="products">
                    <div class="card-header bg-info text-white">
                        <h4>All Products</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price Range</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $product): ?>
                                    <tr>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <td><?= $product['id'] ?></td>
                                            <td>
                                                <?php if($product['image_url']): ?>
                                                    <img src="<?= $product['image_url'] ?>" width="50" height="50" style="object-fit: cover;">
                                                <?php endif; ?>
                                                <input type="file" name="image" class="form-control mt-1">
                                            </td>
                                            <td><input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" class="form-control"></td>
                                            <td>
                                                <select name="category" class="form-control">
                                                    <option value="vegetables" <?= $product['category'] == 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
                                                    <option value="fruits" <?= $product['category'] == 'fruits' ? 'selected' : '' ?>>Fruits</option>
                                                    <option value="grains" <?= $product['category'] == 'grains' ? 'selected' : '' ?>>Grains</option>
                                                    <option value="cash_crops" <?= $product['category'] == 'cash_crops' ? 'selected' : '' ?>>Cash Crops</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="price_min" value="<?= $product['price_min'] ?>" class="form-control mb-1" style="width: 100px; display: inline-block;">
                                                -
                                                <input type="number" step="0.01" name="price_max" value="<?= $product['price_max'] ?>" class="form-control mt-1" style="width: 100px; display: inline-block;">
                                            </td>
                                            <td>
                                                <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                        </form>
                                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this product?')">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>