<?php
session_start();
require_once 'config/Database.php';

$search = trim($_GET['q'] ?? '');
$products = [];

if (!empty($search)) {
    $database = new Database();
    $conn = $database->getConnection();
    
    
    $keywords = str_getcsv($search, ' ');
    
    
    $sql = "SELECT * FROM products WHERE name LIKE ? OR category LIKE ?";
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
    <title>Search Results - Fresh Harvest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
</head>
<body>
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
                        <div style="height: 200px; background-image: url('<?= $product['image_url'] ?? 'placeholder.jpg' ?>'); background-size: cover; background-position: center;">
                        </div>
                        <div class="p-3">
                            <h5><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="text-success">₱<?= number_format($product['price'], 2) ?></p>
                            <?php if ($product['quantity'] > 0): ?>
                                <small class="text-muted">In Stock: <?= $product['quantity'] ?></small>
                                <a href="index.php" class="btn btn-sm btn-outline-success mt-2 w-100">View Product</a>
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
</body>
</html>