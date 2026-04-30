<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /ECOMM/login.php");
    exit();
}

require_once 'config/Database.php';

$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Set timezone for Philippines
date_default_timezone_set('Asia/Manila');

// Format dates
$member_since = date('F j, Y', strtotime($user['created_at']));
$member_time = date('g:i A', strtotime($user['created_at']));

// Get order count (if orders table exists)
$order_count = 0;
$order_total = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
if ($order_result->num_rows > 0) {
    $order_data = $order_result->fetch_assoc();
    $order_count = $order_data['count'] ?? 0;
    $order_total = $order_data['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - R3 Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                <span class="text-white display-4"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                            </div>
                        </div>
                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted"><?= ucfirst($user['role']) ?></p>
                        <hr>
                        <p class="small text-muted mb-0">
                            Member since <?= $member_since ?> at <?= $member_time ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Account Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Full Name:</strong>
                            </div>
                            <div class="col-md-8">
                                <?= htmlspecialchars($user['name']) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Email Address:</strong>
                            </div>
                            <div class="col-md-8">
                                <?= $user['email'] ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Account Type:</strong>
                            </div>
                            <div class="col-md-8">
                                <span class="badge bg-info"><?= ucfirst($user['role']) ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Orders Placed:</strong>
                            </div>  
                            <div class="col-md-8">
                                <?= $order_count ?> order(s)
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong>Total Spent:</strong>
                            </div>
                            <div class="col-md-8">
                                ₱<?= number_format($order_total, 2) ?>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-success">← Back to Shop</a>
                            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>