<?php
session_start();

// Check if user is logged in (via cookies or session)
if (!isset($_COOKIE['user_id']) && !isset($_SESSION['user_id'])) {
    header("Location: /ECOMM/pages/login.php");
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
$_COOKIE['user_name'] = $user['name'];

// Set timezone for Philippines
date_default_timezone_set('Asia/Manila');

// Format dates
$member_since = date('F j, Y', strtotime($user['created_at']));
$member_time = date('g:i A', strtotime($user['created_at']));
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
    <style>
        :root {
            --green-dark:   #1e3d30;
            --green-mid:    #2c5443;
            --green-accent: #6c9e75;
            --bg-light:     #f9f8f6;
        }

        body { background-color: #f5f2ea }

        /* ── Sidebar ── */
        #sidebar {
            width: 280px;
            min-width: 280px;
            background-color: #1E3028;
            min-height: 100vh;
        }

        .avatar-circle {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%231e3d30"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>') center/70% no-repeat;
            flex-shrink: 0;
        }

        .nav-link-side {
            color: rgba(255,255,255,.75);
            padding: .7rem .75rem;
            border-radius: 6px;
            font-size: .95rem;
            transition: all .2s;
            cursor: pointer;
        }
        .nav-link-side:hover { color: #fff; padding-left: 1.1rem; }
        .nav-link-side.active { color: var(--green-accent); font-weight: 600; background: rgba(255,255,255,.07); }

        /* ── Content ── */
        .content-header h2 { color: var(--green-dark); }

        /* keep Bootstrap cards slightly softer */
        .card { border-radius: 12px; border-color: #e2e8f0; }

        /* inline-edit row */
        .input-underline {
            border: none; border-bottom: 1px solid #dee2e6;
            outline: none; background: transparent;
            font-size: .9rem; padding: 6px 0; flex: 1;
        }

        /* avatar upload zone */
        .upload-circle {
            width: 100px; height: 100px;
            border-radius: 50%; border: 1px solid #dee2e6;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23aaa"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>') center/50% no-repeat;
        }

        /* payment cards */
        .payment-tile {
            width: 200px; height: 120px;
            border: 1px solid #dee2e6; border-radius: 8px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            position: relative; cursor: pointer;
        }
        .payment-tile.selected { border-color: #0d6efd; }
        .payment-tile.add-tile { border-style: dashed; color: #777; font-size: .85rem; }

        .gcash-badge {
            background: #1775e1; color: #fff;
            font-weight: 700; letter-spacing: 1px;
            padding: 10px 28px; border-radius: 6px; font-size: .875rem;
        }

        /* purchase sub-tabs */
        .sub-tab-nav .nav-link {
            font-size: .82rem; color: #555; white-space: nowrap;
            border-radius: 0;
        }
        .sub-tab-nav .nav-link.active {
            color: var(--green-accent);
            border-bottom: 2px solid var(--green-accent);
            font-weight: 600;
            background: transparent;
        }

        /* btn overrides */
        .btn-farm       { background: var(--green-mid);  color: #fff; border: none; }
        .btn-farm:hover { background: var(--green-dark); color: #fff; }
        .btn-farm-dark       { background: var(--green-dark); color: #fff; border: none; }
        .btn-farm-dark:hover { background: #162d24; color: #fff; }
    </style>
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
                <div class="text-white opacity-50" style="font-size:.7rem;">Member since May 2026</div>
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

                        <!-- Username -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Username:</label>
                                <div class="col">
                                    <span class="small"><?= htmlspecialchars($_COOKIE['user_name'])?></span>
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Full Name:</label>
                            <div class="col d-flex align-items-center gap-2">
                                <input type="text" class="input-underline" value="Admin A. Surname">
                                <button class="btn btn-link btn-sm p-0 text-decoration-underline text-success">Change</button>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="row align-items-center mb-3">
                            <label class="col-4 col-sm-3 text-muted small">Email:</label>
                            <div class="col d-flex align-items-center gap-2">
                                <input type="email" class="input-underline" value="admin@gmail.com">
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
                <div class="mb-3">
                    <p class="fw-semibold mb-1">Admin A. Surname &nbsp;|&nbsp; <span class="text-muted fw-normal">(+63) 9952954998</span></p>
                    <p class="text-muted small mb-0">742 Evergreen Terrace, Springfield, OR 97477</p>
                </div>
                <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <button class="btn btn-link btn-sm text-success text-decoration-underline p-0">Edit</button>
                    <button class="btn btn-link btn-sm text-danger text-decoration-underline p-0">Delete</button>
                    <button class="btn btn-outline-secondary btn-sm" disabled>Set as default</button>
                    <button class="btn btn-farm btn-sm">Add Address</button>
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
</script>
</body>
</html>
