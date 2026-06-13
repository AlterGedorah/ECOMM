<?php
session_start();

// Check if user is logged in (via cookies or session)
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
    // invalid user id in session/cookie
    header('Location: pages/logout.php');
    exit();
}

// Expose user name to other pages via cookie (set properly)
if (!empty($user['name'])) {
    setcookie('user_name', $user['name'], time() + 60*60*24*30, '/');
}

// Handle profile update
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'update_profile')) {
    $newName  = trim($_POST['name']  ?? '');
    $newEmail = trim($_POST['email'] ?? '');

    if ($newName === '' || $newEmail === '') {
        $error = 'Name and email are required.';
    } else {
        // check email uniqueness (other than current user)
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $chk->bind_param('si', $newEmail, $user_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'Email already in use by another account.';
            $chk->close();
        } else {
            $chk->close();
        $up = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $up->bind_param('ssi', $newName, $newEmail, $user_id);
        if ($up->execute()) {
            $success = 'Profile updated successfully.';
            // refresh $user and cookie
            $user['name']  = $newName;
            $user['email'] = $newEmail;
            setcookie('user_name', $user['name'], time() + 60*60*24*30, '/');
        } else {
            $error = 'Could not update profile. ' . $up->error;
        }
        $up->close();
        }
    }
}

// Handle add address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'add_address')) {
    $street   = trim($_POST['street'] ?? '');
    $region   = trim($_POST['region'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $postal   = trim($_POST['postal_code'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');

    if ($street === '' || $region === '' || $city === '' || $barangay === '' || $postal === '') {
        $error = 'Street, region, city, barangay, and postal code are required.';
    } else {
        // if user has no address, mark as default
        $isDefault = empty($address) ? 1 : 0;
        $ins = $conn->prepare("INSERT INTO addresses (user_id, address_type, address_label, street_address, region, city, barangay, postal_code, landmark, is_default_shipping) VALUES (?, 'shipping', 'Home', ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param('issssssi', $user_id, $street, $region, $city, $barangay, $postal, $landmark, $isDefault);
        if ($ins->execute()) {
            $success = 'Address added.';
            $ins->close();
            // reload addresses
            $addrStmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC, id DESC, created_at DESC");
            $addrStmt->bind_param('i', $user_id);
            $addrStmt->execute();
            $result = $addrStmt->get_result();
            $addresses = [];
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }
            $address = $addresses[0] ?? null;
            $addrStmt->close();
        } else {
            $error = 'Could not add address: ' . $ins->error;
            $ins->close();
        }
    }
}

// Handle set default address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'set_default_address')) {
    $addressId = (int)($_POST['address_id'] ?? 0);
    if ($addressId > 0) {
        $conn->begin_transaction();
        try {
            $reset = $conn->prepare("UPDATE addresses SET is_default_shipping = 0 WHERE user_id = ?");
            $reset->bind_param('i', $user_id);
            $reset->execute();
            $reset->close();

            $setDefault = $conn->prepare("UPDATE addresses SET is_default_shipping = 1 WHERE id = ? AND user_id = ?");
            $setDefault->bind_param('ii', $addressId, $user_id);
            if (!$setDefault->execute()) {
                throw new Exception($setDefault->error);
            }
            $setDefault->close();

            $conn->commit();
            $success = 'Default address updated.';

            $addrStmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC, id DESC, created_at DESC");
            $addrStmt->bind_param('i', $user_id);
            $addrStmt->execute();
            $result = $addrStmt->get_result();
            $addresses = [];
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }
            $address = $addresses[0] ?? null;
            $addrStmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Could not update default address.';
        }
    }
}

// Set timezone for Philippines
date_default_timezone_set('Asia/Manila');

// Format dates
$member_since = date('F j, Y', strtotime($user['created_at']));
$member_time = date('g:i A', strtotime($user['created_at']));

// Load all shipping addresses for the user
$addresses = [];
$addrStmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC, id DESC, created_at DESC");
if ($addrStmt) {
    $addrStmt->bind_param('i', $user_id);
    $addrStmt->execute();
    $result = $addrStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    $addrStmt->close();
}
$address = $addresses[0] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="images/icon.png">
    <link rel="stylesheet" href="styles/profile.css">
</head>
<body>

<div class="d-flex">

    <!-- ══════════════ SIDEBAR ══════════════ -->
    <aside id="sidebar" class="p-4 d-flex flex-column gap-3">

        <!-- Back -->
        <a href="index.php" class="text-white text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>

        <!-- User info -->
        <div class="d-flex align-items-center gap-3 pb-3 border-bottom border-white border-opacity-25">
            <div class="avatar-circle"></div>
            <div>
                <div class="fw-semibold text-white"></div>
                <div class="text-white opacity-50" style="font-size:.7rem;">Member since <?= htmlspecialchars($member_since) ?> at <?= htmlspecialchars($member_time) ?></div>
                <a href="#" class="text-white opacity-75 text-decoration-underline" style="font-size:.7rem;">Edit Profile</a>
            </div>
        </div>

        <!-- Nav -->
        <nav>
            <p class="text-white fs-6 fw-medium mb-2">My account</p>
            <ul class="list-unstyled mb-0">
                <li class="nav-link-side active" data-target="profile-panel">
                    <i class="bi bi-person me-2"></i>Profile
                </li>
                <li class="nav-link-side" data-target="payment-panel">
                    <i class="bi bi-credit-card me-2"></i>Payment
                </li>
                <li class="nav-link-side" data-target="purchase-panel">
                    <i class="bi bi-bag me-2"></i>My Purchase
                </li>
            </ul>
        </nav>

    </aside>

    <!-- ══════════════ MAIN CONTENT ══════════════ -->
    <main class="flex-grow-1 p-5">

        <div class="content-header mb-4">
            <h2 class="fw-bold mb-1">My profile</h2>
            <p class="text-muted mb-0">Welcome back user! Manage and protect your account.</p>
        </div>

        <!-- ── PROFILE PANEL ── -->
        <section id="profile-panel" class="content-panel">

            <!-- Account card -->
            <div class="card mb-4 p-4">
                <div class="row g-4">

                    <!-- Form fields -->
                    <div class="col-lg-8">
                        <h5 class="fw-medium mb-4">Account</h5>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="post">
                        <input type="hidden" name="action" value="update_profile">

                        <!-- Username (email) -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Username:</label>
                            <div class="col">
                                <span class="small"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Full Name:</label>
                            <div class="col d-flex align-items-center gap-2">
                                <input type="text" name="name" class="input-underline" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-underline text-success">Save</button>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Email:</label>
                            <div class="col d-flex align-items-center gap-2">
                                <input type="email" name="email" class="input-underline" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                <button class="btn btn-link btn-sm p-0 text-decoration-underline text-success">Change</button>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Phone:</label>
                            <div class="col d-flex align-items-center gap-2">
                                <input type="text" class="input-underline" value="*********98">
                                <button class="btn btn-link btn-sm p-0 text-decoration-underline text-success">Change</button>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Gender:</label>
                            <div class="col">
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male">
                                        <label class="form-check-label small" for="genderMale">Male</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female" checked>
                                        <label class="form-check-label small" for="genderFemale">Female</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderOther" value="other">
                                        <label class="form-check-label small" for="genderOther">Other</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>

                    <!-- Avatar upload -->
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
                <h5 class="fw-medium mb-3">Address</h5>

                <?php if (empty($addresses)): ?>
                    <p class="text-muted small mb-3">No address on file.</p>
                <?php else: ?>
                    <div class="d-grid gap-3 mb-3">
                        <?php foreach ($addresses as $addr): ?>
                            <div class="border rounded p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-semibold mb-1">
                                            <?= htmlspecialchars($user['name'] ?? '') ?>
                                            <?php if ((int)$addr['is_default_shipping'] === 1): ?>
                                                <span class="badge bg-success ms-2">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars(trim(($addr['street_address'] ?? '') . ', ' . ($addr['barangay'] ?? '') . ', ' . ($addr['city'] ?? '') . ', ' . ($addr['region'] ?? '') . ', ' . ($addr['postal_code'] ?? ''), ', ')) ?>
                                        </div>
                                        <?php if (!empty($addr['landmark'])): ?>
                                            <div class="text-muted small">Landmark: <?= htmlspecialchars($addr['landmark']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                                        <form method="post" class="m-0">
                                            <input type="hidden" name="action" value="set_default_address">
                                            <input type="hidden" name="address_id" value="<?= (int)$addr['id'] ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" <?= ((int)$addr['is_default_shipping'] === 1) ? 'disabled' : '' ?>>Set as default</button>
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
                                    <option value="">Select region</option>
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
                                <button type="button" id="cancelAddAddress" class="btn btn-outline-secondary btn-sm">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </section>

        <!-- ── PAYMENT PANEL ── -->
        <section id="payment-panel" class="content-panel d-none">
            <div class="card p-4">
                <h5 class="fw-medium mb-4">Payment Methods</h5>
                <div class="d-flex gap-3 flex-wrap">
                    <div class="payment-tile selected">
                        <div class="gcash-badge">GCASH</div>
                        <button class="btn btn-dark btn-sm position-absolute bottom-0 mb-1" style="font-size:.7rem; padding:2px 10px;">Remove</button>
                    </div>
                    <div class="payment-tile add-tile">
                        <i class="bi bi-plus-circle fs-4 mb-1"></i>
                        <span>Add Payment Method</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── PURCHASE PANEL ── -->
        <section id="purchase-panel" class="content-panel d-none">

            <!-- Sub-tabs -->
            <div class="card mb-0 rounded-bottom-0 border-bottom-0">
                <ul class="nav sub-tab-nav flex-nowrap overflow-auto border-bottom px-2">
                    <li class="nav-item"><button class="nav-link active py-3 px-3 border-0 bg-transparent sub-tab">ALL</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">To Pay</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">To Ship</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">To Receive</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">Completed</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">Cancelled</button></li>
                    <li class="nav-item"><button class="nav-link py-3 px-3 border-0 bg-transparent sub-tab">Return/Refund</button></li>
                </ul>
            </div>

            <!-- Order card -->
            <div class="card rounded-top-0 p-4">
                <div class="border rounded mb-4" style="height:80px;"></div>
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-farm btn-sm px-4">Buy Again</button>
                    <button class="btn btn-farm-dark btn-sm px-4">Contact Seller</button>
                </div>
            </div>

        </section>

    </main>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Main sidebar navigation ──
    document.querySelectorAll('.nav-link-side').forEach(item => {
        item.addEventListener('click', () => {
            // update active state
            document.querySelectorAll('.nav-link-side').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // show target panel
            document.querySelectorAll('.content-panel').forEach(p => p.classList.add('d-none'));
            const target = document.getElementById(item.dataset.target);
            if (target) target.classList.remove('d-none');
        });
    });

    // ── Purchase sub-tabs ──
    document.querySelectorAll('.sub-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
        });
    });

    // Add address form toggle
    const showBtn = document.getElementById('showAddAddress');
    const cancelBtn = document.getElementById('cancelAddAddress');
    const formDiv = document.getElementById('addAddressForm');
    if (showBtn && formDiv) {
        showBtn.addEventListener('click', () => formDiv.classList.remove('d-none'));
    }
    if (cancelBtn && formDiv) {
        cancelBtn.addEventListener('click', () => formDiv.classList.add('d-none'));
    }

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
