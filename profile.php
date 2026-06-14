<?php
session_start();

function buildFullName(?string $firstName, ?string $middleName, ?string $lastName): string
{
    $parts = [];

    $firstName = trim((string) $firstName);
    $middleName = trim((string) $middleName);
    $lastName = trim((string) $lastName);

    if ($firstName !== '') {
        $parts[] = $firstName;
    }

    if ($middleName !== '') {
        $parts[] = $middleName;
    }

    if ($lastName !== '') {
        $parts[] = $lastName;
    }

    return trim(implode(' ', $parts));
}

if (!isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

require_once 'config/Database.php';

$database = new Database();
$conn = $database->getConnection();

$user_id = $_COOKIE['user_id'] ?? $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: pages/logout.php");
    exit();
}

$displayName = buildFullName($user['first_name'] ?? '', $user['middle_name'] ?? '', $user['last_name'] ?? '');
if ($displayName === '') {
    $displayName = trim((string) ($user['name'] ?? ''));
}

if ($displayName !== '') {
    setcookie('user_name', $displayName, time() + (60 * 60 * 24 * 30), "/");
} else {
    setcookie('user_name', '', time() - 3600, "/");
}

if (!empty($user['phone_num'])) {
    setcookie('user_phone_num', (string) $user['phone_num'], time() + (60 * 60 * 24 * 30), "/");
} else {
    setcookie('user_phone_num', '', time() - 3600, "/");
}

$success = "";
$error = "";

function loadAddresses($conn, $user_id)
{
    $addresses = [];
    $addrStmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC, id DESC, created_at DESC");
    if ($addrStmt) {
        $addrStmt->bind_param("i", $user_id);
        $addrStmt->execute();
        $result = $addrStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $addresses[] = $row;
        }
        $addrStmt->close();
    }
    return $addresses;
}

$addresses = loadAddresses($conn, $user_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'update_profile')) {
    $newFirstName = trim($_POST['first_name'] ?? '');
    $newMiddleName = trim($_POST['middle_name'] ?? '');
    $newLastName = trim($_POST['last_name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newPhone = trim($_POST['phone_num'] ?? '');

    if ($newFirstName === '' || $newLastName === '' || $newEmail === '') {
        $error = "First name, last name, and email are required.";
    } elseif ($newPhone !== '' && !preg_match('/^[0-9]+$/', $newPhone)) {
        $error = "Phone number must contain digits only.";
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $chk->bind_param("si", $newEmail, $user_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = "Email already in use by another account.";
            $chk->close();
        } else {
            $chk->close();
            $newDisplayName = buildFullName($newFirstName, $newMiddleName, $newLastName);

            if ($newPhone === '') {
                $up = $conn->prepare("UPDATE users SET name = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, phone_num = NULL WHERE id = ?");
                $up->bind_param("sssssi", $newDisplayName, $newFirstName, $newMiddleName, $newLastName, $newEmail, $user_id);
            } else {
                $phoneValue = (int) $newPhone;
                $up = $conn->prepare("UPDATE users SET name = ?, first_name = ?, middle_name = ?, last_name = ?, email = ?, phone_num = ? WHERE id = ?");
                $up->bind_param("sssssii", $newDisplayName, $newFirstName, $newMiddleName, $newLastName, $newEmail, $phoneValue, $user_id);
            }

            if ($up->execute()) {
                $success = "Profile updated successfully.";
                $user['name'] = $newDisplayName;
                $user['first_name'] = $newFirstName;
                $user['middle_name'] = $newMiddleName;
                $user['last_name'] = $newLastName;
                $user['email'] = $newEmail;
                $user['phone_num'] = $newPhone === '' ? null : (int) $newPhone;
                $displayName = $newDisplayName;
                setcookie('user_name', $displayName, time() + (60 * 60 * 24 * 30), "/");
                if ($user['phone_num'] !== null) {
                    setcookie('user_phone_num', (string) $user['phone_num'], time() + (60 * 60 * 24 * 30), "/");
                } else {
                    setcookie('user_phone_num', '', time() - 3600, "/");
                }
            } else {
                $error = "Could not update profile. " . $up->error;
            }
            $up->close();
        }
    }
}

// Handle add address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'add_address')) {
    $street = trim($_POST['street'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');

    if ($street === '' || $region === '' || $city === '' || $barangay === '' || $postal === '') {
        $error = "Street, region, city, barangay, and postal code are required.";
    } else {
        $isDefault = empty($addresses) ? 1 : 0;
        $ins = $conn->prepare("INSERT INTO addresses (user_id, address_type, address_label, street_address, region, city, barangay, postal_code, landmark, is_default_shipping) VALUES (?, 'shipping', 'Home', ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("issssssi", $user_id, $street, $region, $city, $barangay, $postal, $landmark, $isDefault);
        if ($ins->execute()) {
            $success = "Address added.";
            $ins->close();
            $addresses = loadAddresses($conn, $user_id);
        } else {
            $error = "Could not add address: " . $ins->error;
            $ins->close();
        }
    }
}

// Handle set default address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'set_default_address')) {
    $addressId = (int) ($_POST['address_id'] ?? 0);
    if ($addressId > 0) {
        $conn->begin_transaction();
        try {
            $reset = $conn->prepare("UPDATE addresses SET is_default_shipping = 0 WHERE user_id = ?");
            $reset->bind_param("i", $user_id);
            $reset->execute();
            $reset->close();

            $setDefault = $conn->prepare("UPDATE addresses SET is_default_shipping = 1 WHERE id = ? AND user_id = ?");
            $setDefault->bind_param("ii", $addressId, $user_id);
            if (!$setDefault->execute()) {
                throw new Exception($setDefault->error);
            }
            $setDefault->close();

            $conn->commit();
            $success = "Default address updated.";
            $addresses = loadAddresses($conn, $user_id);
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Could not update default address.";
        }
    }
}

// Handle delete address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'delete_address')) {
    $addressId = (int) ($_POST['address_id'] ?? 0);
    if ($addressId > 0) {
        $wasDefault = false;
        foreach ($addresses as $a) {
            if ((int) $a['id'] === $addressId && (int) $a['is_default_shipping'] === 1) {
                $wasDefault = true;
                break;
            }
        }

        $del = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $del->bind_param("ii", $addressId, $user_id);
        if ($del->execute()) {
            $success = "Address deleted.";
            $del->close();
            $addresses = loadAddresses($conn, $user_id);

            if ($wasDefault && !empty($addresses)) {
                $newDefaultId = (int) $addresses[0]['id'];
                $upDef = $conn->prepare("UPDATE addresses SET is_default_shipping = 1 WHERE id = ?");
                $upDef->bind_param("i", $newDefaultId);
                $upDef->execute();
                $upDef->close();
                $addresses = loadAddresses($conn, $user_id);
            }
        } else {
            $error = "Could not delete address.";
            $del->close();
        }
    }
}

date_default_timezone_set('Asia/Manila');
$member_since = date("F j, Y", strtotime($user['created_at']));
$member_time = date("g:i A", strtotime($user['created_at']));
$address = $addresses[0] ?? null;
$profilePhone = isset($user['phone_num']) && $user['phone_num'] !== null ? (string) $user['phone_num'] : '';

// Fetch all orders with their items for the current user
$ordersByTab = [
    'all' => [],
    'to_pay' => [],
    'to_ship' => [],
    'to_receive' => [],
    'completed' => [],
    'cancelled' => [],
    'refund' => [],
];

$orderStmt = $conn->prepare("
    SELECT o.*, a.street_address, a.barangay, a.city, a.region, a.postal_code, a.landmark
    FROM orders o
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
");
if ($orderStmt) {
    $orderStmt->bind_param("i", $user_id);
    $orderStmt->execute();
    $ordersRes = $orderStmt->get_result();
    while ($order = $ordersRes->fetch_assoc()) {
        $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items = [];
        if ($itemsStmt) {
            $itemsStmt->bind_param("i", $order['id']);
            $itemsStmt->execute();
            $itemsRes = $itemsStmt->get_result();
            while ($item = $itemsRes->fetch_assoc()) {
                $items[] = $item;
            }
            $itemsStmt->close();
        }
        $order['items'] = $items;

        $ordersByTab['all'][] = $order;

        $status = $order['order_status'];
        $payment = $order['payment_status'];

        // To Pay: unpaid and not cancelled
        if ($payment === 'unpaid' && $status !== 'cancelled') {
            $ordersByTab['to_pay'][] = $order;
        }
        // To Ship: pending/confirmed/processing and paid (or unpaid still preparing)
        if (in_array($status, ['pending', 'confirmed', 'processing'], true) && $status !== 'cancelled') {
            $ordersByTab['to_ship'][] = $order;
        }
        // To Receive: shipped
        if ($status === 'shipped') {
            $ordersByTab['to_receive'][] = $order;
        }
        // Completed: delivered
        if ($status === 'delivered') {
            $ordersByTab['completed'][] = $order;
        }
        // Cancelled
        if ($status === 'cancelled') {
            $ordersByTab['cancelled'][] = $order;
        }
        // Return/Refund: payment refunded
        if ($payment === 'refunded') {
            $ordersByTab['refund'][] = $order;
        }
    }
    $orderStmt->close();
}

function renderOrdersList(array $orders): string
{
    if (empty($orders)) {
        return '<div class="text-center text-muted py-5 small">No orders in this section.</div>';
    }

    $html = '<div class="d-flex flex-column gap-3">';
    foreach ($orders as $order) {
        $status = $order['order_status'];
        $payment = $order['payment_status'];
        $statusLabel = ucfirst($status);
        $paymentLabel = ucfirst($payment);
        $statusClass = 'bg-secondary';
        if ($status === 'pending') {
            $statusClass = 'bg-warning text-dark';
        } elseif ($status === 'confirmed' || $status === 'processing') {
            $statusClass = 'bg-info text-dark';
        } elseif ($status === 'shipped') {
            $statusClass = 'bg-primary';
        } elseif ($status === 'delivered') {
            $statusClass = 'bg-success';
        } elseif ($status === 'cancelled') {
            $statusClass = 'bg-danger';
        }
        if ($payment === 'paid') {
            $paymentClass = 'bg-success';
        } elseif ($payment === 'unpaid') {
            $paymentClass = 'bg-warning text-dark';
        } elseif ($payment === 'refunded') {
            $paymentClass = 'bg-secondary';
        } else {
            $paymentClass = 'bg-danger';
        }

        $createdAt = !empty($order['created_at']) ? date('M j, Y g:i A', strtotime($order['created_at'])) : '';

        $html .= '<div class="border rounded p-3 bg-white order-card">';
        $html .= '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 pb-2 border-bottom">';
        $html .= '<div class="small text-muted">Order <span class="fw-semibold text-dark">' . htmlspecialchars($order['order_number']) . '</span></div>';
        $html .= '<div class="d-flex gap-2 align-items-center small">';
        $html .= '<span class="text-muted">' . htmlspecialchars($createdAt) . '</span>';
        $html .= '<span class="badge ' . $statusClass . '">' . htmlspecialchars($statusLabel) . '</span>';
        $html .= '<span class="badge ' . $paymentClass . '">' . htmlspecialchars($paymentLabel) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // Items
        foreach ($order['items'] as $item) {
            $html .= '<div class="d-flex justify-content-between align-items-center py-2">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="order-thumb"></div>';
            $html .= '<div>';
            $html .= '<div class="fw-semibold small">' . htmlspecialchars($item['product_name']) . '</div>';
            $html .= '<div class="text-muted" style="font-size:.75rem;">Qty: ' . (int) $item['quantity'] . ' × ₱' . number_format((float) $item['price_per_unit'], 2) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="small fw-semibold">₱' . number_format((float) $item['total_price'], 2) . '</div>';
            $html .= '</div>';
        }

        // Footer
        $html .= '<div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">';
        $html .= '<div class="small text-muted">Payment: ' . htmlspecialchars(ucwords(str_replace('_', ' ', (string) $order['payment_method']))) . '</div>';
        $html .= '<div class="d-flex align-items-center gap-2">';
        $html .= '<span class="text-muted small">Total:</span>';
        $html .= '<span class="fw-bold">₱' . number_format((float) $order['total_amount'], 2) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
    <link rel="stylesheet" href="styles/profile.css">
</head>

<body>

    <div class="d-flex">

        <!-- SIDEBAR -->
        <aside id="sidebar" class="p-4 d-flex flex-column gap-3">
            <a href="index.php" class="text-white text-decoration-none small">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            <div class="d-flex align-items-center gap-3 pb-3 border-bottom border-white border-opacity-25">
                <div class="avatar-circle"></div>
                <div>
                    <div class="fw-semibold text-white"><?= htmlspecialchars($displayName) ?></div>
                    <div class="text-white opacity-50" style="font-size:.7rem;">Member since
                        <?= htmlspecialchars($member_since) ?></div>
                    <a href="#" id="sidebarEditProfile" class="text-white opacity-75 text-decoration-underline"
                        style="font-size:.7rem;">Edit Profile</a>
                </div>
            </div>
            <nav>
                <p class="text-white fs-6 fw-medium mb-2">My account</p>
                <ul class="list-unstyled mb-0">
                    <li class="nav-link-side active" data-target="profile-panel"><i
                            class="bi bi-person me-2"></i>Profile</li>
                    <li class="nav-link-side" data-target="payment-panel"><i class="bi bi-credit-card me-2"></i>Payment
                    </li>
                    <li class="nav-link-side" data-target="purchase-panel"><i class="bi bi-bag me-2"></i>My Purchase
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-grow-1 p-5">
            <div class="content-header mb-4">
                <h2 class="fw-bold mb-1">My profile</h2>
                <p class="text-muted mb-0">Manage and protect your account.</p>
            </div>

            <!-- PROFILE PANEL -->
            <section id="profile-panel" class="content-panel">
                <div class="card mb-4 p-4">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-medium mb-0">Account</h5>
                                <button type="button" id="unlockProfileBtn" class="btn btn-sm btn-outline-success"><i
                                        class="bi bi-pencil-square me-1"></i>Edit Profile</button>
                            </div>

                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                            <form method="post" id="profileForm">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="row align-items-center mb-3">
                                    <label class="col-4 col-sm-3 text-muted small">Full Name:</label>
                                    <div class="col"><span class="small"><?= htmlspecialchars($displayName ?: 'N/A') ?></span></div>
                                </div>

                                <div class="row align-items-center mb-3">
                                    <label class="col-4 col-sm-3 text-muted small">First Name:</label>
                                    <div class="col">
                                        <input type="text" name="first_name" id="profileFirstName"
                                            class="input-underline form-control form-control-sm"
                                            value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" disabled required>
                                    </div>
                                </div>

                                <div class="row align-items-center mb-3">
                                    <label class="col-4 col-sm-3 text-muted small">Middle Name:</label>
                                    <div class="col">
                                        <input type="text" name="middle_name" id="profileMiddleName"
                                            class="input-underline form-control form-control-sm"
                                            value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>" disabled>
                                    </div>
                                </div>

                                <div class="row align-items-center mb-3">
                                    <label class="col-4 col-sm-3 text-muted small">Last Name:</label>
                                    <div class="col">
                                        <input type="text" name="last_name" id="profileLastName"
                                            class="input-underline form-control form-control-sm"
                                            value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" disabled required>
                                    </div>
                                </div>

                                <div class="row align-items-center mb-3">
                                    <label class="col-4 col-sm-3 text-muted small">Email:</label>
                                    <div class="col">
                                        <input type="email" name="email" id="profileEmail"
                                            class="input-underline form-control form-control-sm"
                                            value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled required>
                                    </div>
                                </div>

                                <div class="row align-items-center mb-3">
                                    <label class="col-4 col-sm-3 text-muted small">Phone:</label>
                                    <div class="col">
                                        <input type="text" id="profilePhone" name="phone_num"
                                            class="input-underline form-control form-control-sm"
                                            value="<?= htmlspecialchars($profilePhone) ?>" disabled>
                                    </div>
                                </div>

                                <div class="row mt-4 id-form-actions d-none" id="profileFormActions">
                                    <div class="col-sm-9 offset-sm-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-success btn-sm px-4">Save Changes</button>
                                        <button type="button" id="cancelProfileBtn"
                                            class="btn btn-outline-secondary btn-sm">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="col-lg-4 d-flex flex-column align-items-center justify-content-center border-start">
                            <div class="upload-circle mb-3"></div>
                            <button class="btn btn-outline-secondary btn-sm">Choose Image</button>
                            <p class="text-muted mt-2 text-center" style="font-size:.7rem; line-height:1.5;">
                                File size: maximum 1 MB<br>File extension: .JPEG, .PNG
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Address card -->
                <div class="card p-4">
                    <h5 class="fw-medium mb-3">Address Management</h5>

                    <?php if (empty($addresses)): ?>
                        <p class="text-muted small mb-3">No address on file.</p>
                    <?php else: ?>
                        <div class="d-grid gap-3 mb-3">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="border rounded p-3 bg-white">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                        <div class="d-flex align-items-start gap-3">
                                            <!-- Radio option implementation -->
                                            <div class="pt-1">
                                                <form method="post" class="m-0 auto-submit-form">
                                                    <input type="hidden" name="action" value="set_default_address">
                                                    <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                                                    <input type="radio" name="is_default_shipping"
                                                        class="form-check-input address-radio-click"
                                                        value="<?= (int) $addr['id'] ?>" <?= (int) $addr['is_default_shipping'] === 1 ? 'checked' : '' ?>>
                                                </form>
                                            </div>
                                            <div>
                                                <div class="fw-semibold mb-1">
                                                    <?= htmlspecialchars($user['name'] ?? '') ?>
                                                    <?php if ((int) $addr['is_default_shipping'] === 1): ?>
                                                        <span class="badge bg-success ms-2">Default</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <?= htmlspecialchars(trim(($addr['street_address'] ?? '') . ', ' . ($addr['barangay'] ?? '') . ', ' . ($addr['city'] ?? '') . ', ' . ($addr['region'] ?? '') . ', ' . ($addr['postal_code'] ?? ''), ', ')) ?>
                                                </div>
                                                <?php if (!empty($addr['landmark'])): ?>
                                                    <div class="text-muted small">Landmark:
                                                        <?= htmlspecialchars($addr['landmark']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 justify-content-end align-items-center">
                                            <form method="post" class="m-0"
                                                onsubmit="return confirm('Are you sure you want to delete this address?');">
                                                <input type="hidden" name="action" value="delete_address">
                                                <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                        <button class="btn btn-farm btn-sm" id="showAddAddress" type="button">Add Address</button>
                    </div>

                    <div id="addAddressForm" class="mt-3 d-none">
                        <form method="post">
                            <input type="hidden" name="action" value="add_address">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input name="street" class="form-control" placeholder="Street address" required>
                                </div>
                                <div class="col-md-3">
                                    <select name="region" id="regionSelect" class="form-select" required>
                                        <option value="">Select Province</option>
                                        <option value="Cavite">Cavite</option>
                                        <option value="Laguna">Laguna</option>
                                        <option value="Batangas">Batangas</option>
                                        <option value="Rizal">Rizal</option>
                                        <option value="Quezon">Quezon</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="city" id="citySelect" class="form-select" required>
                                        <option value="">Select city/municipality</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <input name="barangay" class="form-control" placeholder="Barangay" required>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <input name="postal_code" class="form-control" placeholder="Postal code" required>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <input name="landmark" class="form-control" placeholder="Landmark (optional)">
                                </div>
                                <div class="col-12 mt-2 d-flex gap-2 justify-content-end">
                                    <button type="submit" class="btn btn-farm btn-sm">Save address</button>
                                    <button type="button" id="cancelAddAddress"
                                        class="btn btn-outline-secondary btn-sm">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- PAYMENT PANEL -->
            <section id="payment-panel" class="content-panel d-none">
                <div class="card p-4">
                    <h5 class="fw-medium mb-4">Payment Methods</h5>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="payment-tile selected">
                            <div class="gcash-badge">GCASH</div>
                            <button class="btn btn-dark btn-sm position-absolute bottom-0 mb-1"
                                style="font-size:.7rem; padding:2px 10px;">Remove</button>
                        </div>
                        <div class="payment-tile add-tile">
                            <i class="bi bi-plus-circle fs-4 mb-1"></i>
                            <span>Add Payment Method</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PURCHASE PANEL -->
            <section id="purchase-panel" class="content-panel d-none">
                <div class="card mb-0 rounded-bottom-0 border-bottom-0">
                    <ul class="nav sub-tab-nav flex-nowrap overflow-auto border-bottom px-2" id="purchaseTabs">
                        <li class="nav-item"><button data-tab="all"
                                class="nav-link active py-3 px-3 border-0 bg-transparent sub-tab">ALL</button></li>
                        <li class="nav-item"><button data-tab="to_pay"
                                class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">To Pay</button></li>
                        <li class="nav-item"><button data-tab="to_ship"
                                class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">To Ship</button></li>
                        <li class="nav-item"><button data-tab="to_receive"
                                class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">To Receive</button></li>
                        <li class="nav-item"><button data-tab="completed"
                                class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">Completed</button></li>
                        <li class="nav-item"><button data-tab="cancelled"
                                class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">Cancelled</button></li>
                        <li class="nav-item"><button data-tab="refund"
                                class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">Return/Refund</button></li>
                    </ul>
                </div>
                <div class="card rounded-top-0 p-4">
                    <div id="purchaseTabContent">
                        <div class="purchase-tab-pane" data-pane="all"><?= renderOrdersList($ordersByTab['all']) ?></div>
                        <div class="purchase-tab-pane d-none" data-pane="to_pay"><?= renderOrdersList($ordersByTab['to_pay']) ?></div>
                        <div class="purchase-tab-pane d-none" data-pane="to_ship"><?= renderOrdersList($ordersByTab['to_ship']) ?></div>
                        <div class="purchase-tab-pane d-none" data-pane="to_receive"><?= renderOrdersList($ordersByTab['to_receive']) ?></div>
                        <div class="purchase-tab-pane d-none" data-pane="completed"><?= renderOrdersList($ordersByTab['completed']) ?></div>
                        <div class="purchase-tab-pane d-none" data-pane="cancelled"><?= renderOrdersList($ordersByTab['cancelled']) ?></div>
                        <div class="purchase-tab-pane d-none" data-pane="refund"><?= renderOrdersList($ordersByTab['refund']) ?></div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.nav-link-side').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.nav-link-side').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                document.querySelectorAll('.content-panel').forEach(p => p.classList.add('d-none'));
                const target = document.getElementById(item.dataset.target);
                if (target) target.classList.remove('d-none');
            });
        });

        const unlockBtn = document.getElementById('unlockProfileBtn');
        const cancelProfBtn = document.getElementById('cancelProfileBtn');
        const sideEditLink = document.getElementById('sidebarEditProfile');
        const firstNameInput = document.getElementById('profileFirstName');
        const middleNameInput = document.getElementById('profileMiddleName');
        const lastNameInput = document.getElementById('profileLastName');
        const emailInput = document.getElementById('profileEmail');
        const phoneInput = document.getElementById('profilePhone');
        const formActions = document.getElementById('profileFormActions');

        function toggleProfileEdit(enable) {
            firstNameInput.disabled = !enable;
            if (middleNameInput) middleNameInput.disabled = !enable;
            lastNameInput.disabled = !enable;
            emailInput.disabled = !enable;
            if (phoneInput) phoneInput.disabled = !enable;

            if (enable) {
                formActions.classList.remove('d-none');
                unlockBtn.classList.add('d-none');
            } else {
                formActions.classList.add('d-none');
                unlockBtn.classList.remove('d-none');
            }
        }

        if (unlockBtn) unlockBtn.addEventListener('click', () => toggleProfileEdit(true));
        if (cancelProfBtn) cancelProfBtn.addEventListener('click', () => toggleProfileEdit(false));
        if (sideEditLink) {
            sideEditLink.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelector('[data-target="profile-panel"]').click();
                toggleProfileEdit(true);
            });
        }

        // Auto submit form when radio button changes
        document.querySelectorAll('.address-radio-click').forEach(radio => {
            radio.addEventListener('change', function () {
                this.closest('form').submit();
            });
        });

        document.querySelectorAll('.sub-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const target = tab.dataset.tab;
                document.querySelectorAll('.purchase-tab-pane').forEach(pane => {
                    if (pane.dataset.pane === target) {
                        pane.classList.remove('d-none');
                    } else {
                        pane.classList.add('d-none');
                    }
                });
            });
        });

        const showBtn = document.getElementById('showAddAddress');
        const cancelBtn = document.getElementById('cancelAddAddress');
        const formDiv = document.getElementById('addAddressForm');
        if (showBtn && formDiv) showBtn.addEventListener('click', () => formDiv.classList.remove('d-none'));
        if (cancelBtn && formDiv) cancelBtn.addEventListener('click', () => formDiv.classList.add('d-none'));

        const cityMap = {
            Cavite: ['Bacoor', 'Cavite City', 'Dasmariñas', 'Imus', 'General Trias', 'Tagaytay', 'Trece Martires'],
            Laguna: ['Biñan', 'Calamba', 'San Pablo', 'Santa Rosa', 'San Pedro', 'San Jose'],
            Batangas: ['Batangas City', 'Lipa', 'Tanauan', 'Sto. Tomas', 'Bauan', 'Nasugbu'],
            Rizal: ['Antipolo', 'Angono', 'Cainta', 'Rodriguez', 'Taytay', 'Teresa'],
            Quezon: ['Lucena', 'Tayabas', 'Sariaya', 'Candelaria', 'Dolores', 'Pagbilao']
        };

        const regionSelect = document.getElementById('regionSelect');
        const citySelect = document.getElementById('citySelect');
        if (regionSelect && citySelect) {
            regionSelect.addEventListener('change', () => {
                const cities = cityMap[regionSelect.value] || [];
                citySelect.innerHTML = '<option value="">Select city/municipality</option>';
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            });
        }
    </script>
</body>

</html>