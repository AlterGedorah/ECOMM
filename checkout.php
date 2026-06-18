<?php
require_once __DIR__ . '/controllers/CheckoutController.php';
require_once __DIR__ . '/controllers/AuthHelper.php';
session_start();
// Re-hydrate the session from auth cookies so checkout works for users
// who ticked "Remember me" or accepted cookies in a previous visit.
AuthHelper::hydrateSessionFromCookies();
$controller = new CheckoutController();
$message = null;
$messageType = null;


// ── 1. Process place-order submission FIRST (before rendering) ─────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  if (!$controller->isLoggedIn()) {
    header("Location: pages/login.php");
    exit();
  }
  $result = $controller->placeOrder();
  if ($result['success']) {
    header("Location: order_success.php");
    exit();
  }
  $message = $result['error'] ?? 'Failed to place order.';
  $messageType = 'danger';
}

// ── 2. Load checkout data for the view ─────────────────────────────────
$data = $controller->prepareCheckoutPage();
$items = $data['items'];

// If cart is empty (and user just landed), push them back to the cart page.
if ($data['isEmpty'] && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: cart.php");
  exit();
}

$database = new Database();
$conn = $database->getConnection();

$user_id = $_COOKIE['user_id'] ?? $_SESSION['user_id'] ?? 0;

// Fetch ALL addresses for the user, ordering so the default is first
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default_shipping DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_addresses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout — Fresh Harvest</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="styles/checkout.css">
  <link href="styles/cookie.css" rel="stylesheet">
  <link rel="icon" href="images/icon.png">
  <style>
    /* Clickable Address Cards */
    .address-card {
      border: 1px solid var(--border, #dee2e6);
      border-radius: 8px;
      padding: 1rem;
      cursor: pointer;
      transition: all 0.2s ease;
      background: #fff;
      display: block;
    }
    .address-card:hover {
      border-color: #a3c2b1;
    }
    .address-card.selected {
      border-color: #2f4a3a;
      background-color: #f6fdf9;
      box-shadow: 0 0 0 1px #2f4a3a;
    }
    .address-radio {
      display: none;
    }

    /* Field-level validation states */
    .field-error {
      border-color: #dc3545 !important;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, .12) !important;
    }
    .field-error-msg {
      color: #dc3545;
      font-size: .78rem;
      margin-top: 4px;
      display: none;
    }
    .field-error-msg.show {
      display: block;
    }
    .shake {
      animation: shakeField .32s ease both;
    }
    @keyframes shakeField {
      0%, 100% { transform: translateX(0); }
      20%      { transform: translateX(-5px); }
      40%      { transform: translateX(5px); }
      60%      { transform: translateX(-4px); }
      80%      { transform: translateX(4px); }
    }
    /* Top-of-form summary banner */
    #formErrorBanner {
      display: none;
      background: #fdecea;
      border: 1px solid #f5c2c7;
      color: #842029;
      border-radius: 8px;
      padding: .75rem 1rem;
      margin-bottom: 1rem;
      font-size: .9rem;
    }
    #formErrorBanner.show { display: flex; align-items: center; gap: 8px; }
  </style>
</head>

<body>

  <nav class="top-nav">
    <div class="container-fluid px-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
      <a href="cart.php" class="brand">
        <span class="leaf-icon"><i class="bi bi-arrow-left"></i></span>
        Back to cart
      </a>
      <div class="d-flex align-items-center gap-2" style="color:#fff;font-size:.85rem;">
        <i class="bi bi-shield-lock text-success"></i>
        <span class="d-none d-md-inline" style="color:var(--mint)">Secure checkout</span>
      </div>
    </div>
  </nav>

  <div class="checkout-wrap">
    <div class="mb-3">
      <h1 class="page-title">Checkout</h1>
      <p class="page-sub">Review your order and complete your purchase securely.</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-banner">
        <i class="bi bi-exclamation-triangle me-1"></i> <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <?php if (!$data['isLoggedIn']): ?>
      <div class="alert alert-warning alert-banner">
        <i class="bi bi-info-circle me-1"></i>
        Please <a href="pages/login.php" class="alert-link fw-bold">log in</a>
        or <a href="pages/register.php" class="alert-link fw-bold">register</a>
        to complete your order.
      </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm" novalidate>
      <!-- JS validation banner; shown when something is missing/invalid -->
      <div id="formErrorBanner" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span id="formErrorBannerText">Please fix the highlighted fields below.</span>
      </div>
      <div class="row g-3">

        <div class="col-lg-8">
          
          <div class="card-section">
            <div class="section-head">
              <div class="section-num">1</div>
              <p class="section-title mb-0">Shipping address</p>
            </div>
            
            <?php if (!empty($user_addresses)): ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($user_addresses as $index => $addr): ?>
                  <?php $is_default = ((int)$addr['is_default_shipping'] === 1 || $index === 0); ?>
                  
                  <label class="address-card <?= $is_default ? 'selected' : '' ?>" id="addr-card-<?= $addr['id'] ?>" onclick="selectAddress('<?= $addr['id'] ?>')">
                    <input type="radio" name="address_id" value="<?= (int) $addr['id'] ?>" class="address-radio" <?= $is_default ? 'checked' : '' ?> />
                    
                    <div class="d-flex justify-content-between align-items-start gap-3">
                      <div>
                        <div class="fw-semibold mb-1">
                          <?= htmlspecialchars($data['user']['name'] ?? 'Customer') ?>
                          <?php if ((int) $addr['is_default_shipping'] === 1): ?>
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
                      <i class="bi bi-check-circle-fill text-success <?= $is_default ? '' : 'd-none' ?>" id="addr-check-<?= $addr['id'] ?>"></i>
                    </div>
                  </label>
                  
                <?php endforeach; ?>
              </div>

            <?php else: ?>
              <div class="row g-3" id="manualAddressBlock">
                <div class="col-sm-6">
                  <label>First name</label>
                  <input type="text" name="first_name" data-field="first_name" class="form-control field-validated" required
                    value="<?= htmlspecialchars(explode(' ', $data['user']['name'] ?? '')[0] ?? '') ?>" />
                  <div class="field-error-msg" data-error-for="first_name">First name is required.</div>
                </div>
                <div class="col-sm-6">
                  <label>Last name</label>
                  <input type="text" name="last_name" data-field="last_name" class="form-control field-validated" required
                    value="<?= htmlspecialchars(explode(' ', $data['user']['name'] ?? '')[1] ?? '') ?>" />
                  <div class="field-error-msg" data-error-for="last_name">Last name is required.</div>
                </div>
                <div class="col-sm-6">
                  <label>Phone number</label>
                  <div class="input-icon-wrap">
                    <i class="bi bi-phone"></i>
                    <input type="tel" name="phone" id="phoneInput" data-field="phone" class="form-control field-validated" placeholder="+63 9XX XXX XXXX" required />
                  </div>
                  <div class="field-error-msg" id="phoneError" data-error-for="phone">Please enter a valid PH mobile number (+63 or 09).</div>
                </div>
                <div class="col-sm-6">
                  <label>Province / Region</label>
                  <select name="province" id="provinceSelect" data-field="province" class="form-select field-validated" required>
                    <option value="">Select province…</option>
                    <option value="Cavite">Cavite</option>
                    <option value="Laguna">Laguna</option>
                    <option value="Batangas">Batangas</option>
                    <option value="Rizal">Rizal</option>
                    <option value="Quezon">Quezon</option>
                  </select>
                  <div class="field-error-msg" data-error-for="province">Please choose a province.</div>
                </div>
                <div class="col-sm-6">
                  <label>City / Municipality</label>
                  <select name="city" id="citySelect" data-field="city" class="form-select field-validated" required disabled>
                    <option value="">Select province first…</option>
                  </select>
                  <div class="field-error-msg" data-error-for="city">City is required.</div>
                </div>
                <div class="col-sm-6">
                  <label>Barangay</label>
                  <select name="barangay" id="barangaySelect" data-field="barangay" class="form-select field-validated" required disabled>
                    <option value="">Select city first…</option>
                  </select>
                  <div class="field-error-msg" data-error-for="barangay">Barangay is required.</div>
                </div>
                <div class="col-12">
                  <label>Street address / House no.</label>
                  <input type="text" name="street" data-field="street" class="form-control field-validated" placeholder="House no., Building, Street name"
                    required />
                  <div class="field-error-msg" data-error-for="street">Street address is required.</div>
                </div>
                <div class="col-sm-4">
                  <label>ZIP code</label>
                  <input type="text" name="postal_code" data-field="postal_code" class="form-control field-validated" placeholder="e.g. 4027" maxlength="6"
                    required />
                  <div class="field-error-msg" data-error-for="postal_code">ZIP code must be 4 digits.</div>
                </div>
                <div class="col-sm-8">
                  <label>Delivery notes (optional)</label>
                  <input type="text" name="landmark" class="form-control"
                    placeholder="e.g. Leave at door, gate code #123" />
                </div>
              </div>
            <?php endif; ?>

            <div class="divider mt-4"></div>
            <p class="section-title mb-2" style="font-size:.88rem;">Shipping method</p>

            <label class="ship-option chosen" id="ship-std">
              <input type="radio" name="shipping" value="standard" checked
                onchange="pickShipping(this,'ship-std','49')" />
              <div>
                <div class="ship-name"><i class="bi bi-box-seam me-1 text-accent"></i>Standard delivery</div>
                <div class="ship-sub">3–5 business days</div>
              </div>
              <span class="ship-price">₱49</span>
            </label>
            <label class="ship-option" id="ship-exp">
              <input type="radio" name="shipping" value="express" onchange="pickShipping(this,'ship-exp','99')" />
              <div>
                <div class="ship-name"><i class="bi bi-lightning me-1 text-accent"></i>Express delivery</div>
                <div class="ship-sub">Next business day</div>
              </div>
              <span class="ship-price">₱99</span>
            </label>
            <label class="ship-option" id="ship-free">
              <input type="radio" name="shipping" value="free" onchange="pickShipping(this,'ship-free','0')" />
              <div>
                <div class="ship-name"><i class="bi bi-gift me-1 text-accent"></i>Free pickup (store)</div>
                <div class="ship-sub">Available in 2 hours</div>
              </div>
              <span class="ship-price text-success">Free</span>
            </label>
          </div>
          <div class="card-section">
            <div class="section-head">
              <div class="section-num">2</div>
              <p class="section-title mb-0">
                Your items
                <span class="total-items-badge" id="itemCountBadge">0 items</span>
              </p>
            </div>
            <div id="cartItems">
              <?php foreach ($items as $item): ?>
                <div class="cart-item" data-id="<?= (int) $item['id'] ?>" data-price="<?= (float) $item['price'] ?>">
                  <div class="cart-thumb">
                    <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                      <img src="<?= htmlspecialchars($item['image']) ?>" alt="">
                    <?php else: ?>
                      <i class="bi bi-basket"></i>
                    <?php endif; ?>
                  </div>
                  <div class="cart-info">
                    <p class="cart-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="cart-variant">Stock: <?= (int) $item['stock'] ?> available</p>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="qty-wrap">
                        <button type="button" class="qty-btn" onclick="changeQty(<?= (int) $item['id'] ?>, -1)">−</button>
                        <div class="qty-val" id="qty-<?= (int) $item['id'] ?>"><?= (int) $item['quantity'] ?></div>
                        <button type="button" class="qty-btn" onclick="changeQty(<?= (int) $item['id'] ?>, 1)">+</button>
                      </div>
                      <span class="cart-price"
                        id="price-<?= (int) $item['id'] ?>">₱<?= number_format($item['subtotal'], 2) ?></span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="card-section">
            <div class="section-head">
              <div class="section-num">3</div>
              <p class="section-title mb-0">Vouchers & discounts</p>
            </div>
            <div class="apply-row mb-1">
              <div class="input-icon-wrap" style="flex:1;">
                <i class="bi bi-tag"></i>
                <input type="text" class="form-control" id="voucherInput" placeholder="Enter promo code" />
                <input type="hidden" name="voucher" id="voucherHidden" value="" />
              </div>
              <button type="button" class="btn-apply" onclick="applyVoucher()">Apply</button>
            </div>
            <div id="voucherMsg" style="font-size:.8rem;min-height:1.2rem;margin-top:4px;"></div>

            <button type="button" class="vouchers-toggle" onclick="toggleVouchers(this)">
              <i class="bi bi-ticket-perforated"></i>
              <span>Show available vouchers</span>
              <i class="bi bi-chevron-down" id="voucherChevron"></i>
            </button>

            <div id="voucherList">
              <div class="voucher-card" data-code="VERDE20">
                <div>
                  <div class="v-code">VERDE20</div>
                  <div class="v-desc">20% off entire order</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="v-badge">−20%</span>
                  <i class="bi bi-check-circle-fill v-check"></i>
                </div>
              </div>
              <div class="voucher-card" data-code="FREESHIP">
                <div>
                  <div class="v-code">FREESHIP</div>
                  <div class="v-desc">Free standard shipping</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="v-badge">Free</span>
                  <i class="bi bi-check-circle-fill v-check"></i>
                </div>
              </div>
              <div class="voucher-card" data-code="SAVE150">
                <div>
                  <div class="v-code">SAVE150</div>
                  <div class="v-desc">₱150 off · Min. ₱1,500</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="v-badge">−₱150</span>
                  <i class="bi bi-check-circle-fill v-check"></i>
                </div>
              </div>
              <div class="voucher-card" data-code="NEWLEAF">
                <div>
                  <div class="v-code">NEWLEAF</div>
                  <div class="v-desc">₱75 off · First order only</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="v-badge">New</span>
                  <i class="bi bi-check-circle-fill v-check"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="card-section">
            <div class="section-head">
              <div class="section-num">4</div>
              <p class="section-title mb-0">Payment method</p>
            </div>
            
            <div class="pay-method active" id="pay-cod">
              <label class="pay-label" onclick="selectPay('pay-cod')">
                <span class="pay-icon" style="color:#d4860a;"><i class="bi bi-cash-coin"></i></span>
                <div>
                  <p class="pay-name mb-0">Cash on delivery</p>
                  <p class="pay-sub mb-0">Pay when your order arrives</p>
                </div>
                <input class="pay-radio" id="radio" type="radio" name="payMethod" value="cod" />
              </label>
              <div class="pay-panel">
                <div
                  style="display:flex;align-items:flex-start;gap:10px;background:#fffcf0;border-radius:var(--radius-sm);padding:.85rem;border:1px solid #f0dfb0;">
                  <i class="bi bi-exclamation-triangle" style="color:#d4860a;font-size:1.1rem;margin-top:1px;"></i>
                  <div>
                    <p style="font-weight:600;font-size:.85rem;margin:0;color:#7a5100;">Please prepare exact change</p>
                    <p style="font-size:.78rem;color:#9a7020;margin:0;">Our rider may not carry change.</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="pay-method" id="pay-card">
              <label class="pay-label" onclick="selectPay('pay-card')">
                <span class="pay-icon text-forest"><i class="bi bi-credit-card-2-front"></i></span>
                <div>
                  <p class="pay-name mb-0">Credit / Debit card</p>
                  <p class="pay-sub mb-0">Visa, Mastercard, JCB</p>
                </div>
                <input class="pay-radio" type="radio" name="payMethod" value="card" checked />
              </label>
              <div class="pay-panel">
                <div class="row g-2">
                  <div class="col-12">
                    <label>Card number</label>
                    <div class="card-num-group">
                      <input type="text" class="form-control" maxlength="4" placeholder="1234" id="cn1"
                        oninput="fwdCard('cn1','cn2')" />
                      <input type="text" class="form-control" maxlength="4" placeholder="5678" id="cn2"
                        oninput="fwdCard('cn2','cn3')" />
                      <input type="text" class="form-control" maxlength="4" placeholder="9012" id="cn3"
                        oninput="fwdCard('cn3','cn4')" />
                      <input type="text" class="form-control" maxlength="4" placeholder="3456" id="cn4" />
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <label>Name on card</label>
                    <input type="text" class="form-control" placeholder="e.g. MARIA SANTOS"
                      style="text-transform:uppercase;" />
                  </div>
                  <div class="col-sm-3">
                    <label>Expiry</label>
                    <input type="text" class="form-control" placeholder="MM/YY" maxlength="5" />
                  </div>
                  <div class="col-sm-3">
                    <label>CVV</label>
                    <div class="input-icon-wrap">
                      <i class="bi bi-lock"></i>
                      <input type="password" class="form-control" placeholder="•••" maxlength="4" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="pay-method" id="pay-gcash">
              <label class="pay-label" onclick="selectPay('pay-gcash')">
                <span class="pay-icon" style="color:#0066ff;"><i class="bi bi-phone-fill"></i></span>
                <div>
                  <p class="pay-name mb-0">GCash</p>
                  <p class="pay-sub mb-0">Pay via GCash e-wallet</p>
                </div>
                <input class="pay-radio" type="radio" name="payMethod" value="gcash" />
              </label>
              <div class="pay-panel">
                <div
                  style="display:flex;align-items:center;gap:10px;background:#fff;border-radius:var(--radius-sm);padding:.85rem;border:1px solid var(--border);">
                  <i class="bi bi-qr-code-scan" style="font-size:2rem;color:var(--forest);"></i>
                  <div>
                    <p style="font-weight:600;font-size:.88rem;margin:0;">Scan QR or enter number</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin:0;">A payment request will be sent to your
                      GCash app after confirming the order.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="pay-method" id="pay-maya">
              <label class="pay-label" onclick="selectPay('pay-maya')">
                <span class="pay-icon" style="color:#00b14f;"><i class="bi bi-wallet2"></i></span>
                <div>
                  <p class="pay-name mb-0">Maya</p>
                  <p class="pay-sub mb-0">Maya / PayMaya wallet</p>
                </div>
                <input class="pay-radio" type="radio" name="payMethod" value="maya" />
              </label>
              <div class="pay-panel">
                <div
                  style="display:flex;align-items:center;gap:10px;background:#fff;border-radius:var(--radius-sm);padding:.85rem;border:1px solid var(--border);">
                  <i class="bi bi-arrow-right-circle-fill" style="font-size:1.8rem;color:#00b14f;"></i>
                  <div>
                    <p style="font-weight:600;font-size:.88rem;margin:0;">You'll be redirected to Maya</p>
                    <p style="font-size:.78rem;color:var(--text-muted);margin:0;">Complete your payment securely in the
                      Maya app or website.</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="pay-method" id="pay-bank">
              <label class="pay-label" onclick="selectPay('pay-bank')">
                <span class="pay-icon text-forest"><i class="bi bi-bank"></i></span>
                <div>
                  <p class="pay-name mb-0">Bank transfer / InstaPay</p>
                  <p class="pay-sub mb-0">BDO, BPI, UnionBank & more</p>
                </div>
                <input class="pay-radio" type="radio" name="payMethod" value="bank" />
              </label>
              <div class="pay-panel">
                <p style="font-size:.84rem;color:var(--text-muted);margin:0;">After placing your order, upload your
                  payment receipt from your profile. Orders are confirmed within 1–2 business hours.</p>
              </div>
            </div>

            <input type="hidden" name="notes" id="orderNotes" value="" />
            <input type="hidden" name="place_order" value="1" />
          </div>

        </div><div class="col-lg-4">
          <div class="summary-card">
            <div class="summary-header d-flex align-items-center gap-2">
              <i class="bi bi-receipt"></i> Order summary
            </div>
            <div class="summary-body">
              <div id="summaryItems" style="margin-bottom:.75rem;"></div>
              <div class="divider"></div>

              <div class="sum-row"><span>Subtotal</span><span id="sumSubtotal">₱0.00</span></div>
              <div class="sum-row"><span>Shipping</span><span id="sumShipping">₱49</span></div>
              <div class="sum-row" id="discRow" style="display:none;">
                <span>Discount</span><span class="disc" id="sumDisc">−₱0.00</span>
              </div>
              <div class="sum-row total">
                <span>Total</span>
                <span id="sumTotal">₱0.00</span>
              </div>

              <button type="submit" class="place-btn" id="placeOrderBtn" <?= !$data['isLoggedIn'] ? 'disabled' : '' ?>>
                <i class="bi bi-bag-check-fill"></i>
                <?= $data['isLoggedIn'] ? 'Place order' : 'Login to place order' ?>
              </button>
            </div>
          </div>
        </div>

      </div></form>
  </div>

  <script>
    /* ─── State ─── */
    const prices = <?= json_encode(array_column($items, 'price', 'id')) ?>;
    const stocks = <?= json_encode(array_column($items, 'stock', 'id')) ?>;
    const names = <?= json_encode(array_column($items, 'name', 'id')) ?>;
    const qtys = <?= json_encode(array_column($items, 'quantity', 'id')) ?>;

    let shippingFee = 49;
    let activeVoucher = null;

    br = document.getElementById("radio");
    br.classList.add('active');
    br.checked = true;

    /* ─── Address Selection ─── */
    function selectAddress(id) {
      document.querySelectorAll('.address-card').forEach(card => {
        card.classList.remove('selected');
        const checkIcon = card.querySelector('.bi-check-circle-fill');
        if (checkIcon) checkIcon.classList.add('d-none');
      });
      
      const selectedCard = document.getElementById('addr-card-' + id);
      selectedCard.classList.add('selected');
      
      const selectedCheck = document.getElementById('addr-check-' + id);
      if (selectedCheck) selectedCheck.classList.remove('d-none');
    }

    /* ─── Quantity stepper ─── */
    function changeQty(id, delta) {
      const max = stocks[id] || 99;
      qtys[id] = Math.min(max, Math.max(1, qtys[id] + delta));
      document.getElementById('qty-' + id).textContent = qtys[id];
      const total = prices[id] * qtys[id];
      document.getElementById('price-' + id).textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
      recalc();
      renderSummaryItems();
    }

    /* ─── Shipping method ─── */
    function pickShipping(radio, id, price) {
      ['ship-std', 'ship-exp', 'ship-free'].forEach(i => document.getElementById(i).classList.remove('chosen'));
      document.getElementById(id).classList.add('chosen');
      shippingFee = parseInt(price);
      document.getElementById('sumShipping').textContent = price === '0' ? 'Free' : '₱' + price;
      recalc();
    }

    /* ─── Voucher ─── */
    const validVouchers = {
      'VERDE20': { type: 'pct', val: 20, label: '20% off' },
      'FREESHIP': { type: 'ship', val: 0, label: 'Free shipping' },
      'SAVE150': { type: 'fixed', val: 150, label: '₱150 off' },
      'NEWLEAF': { type: 'fixed', val: 75, label: '₱75 off' },
    };

    function applyVoucher() {
      const code = document.getElementById('voucherInput').value.trim().toUpperCase();
      const msg = document.getElementById('voucherMsg');
      const hid = document.getElementById('voucherHidden');
      if (validVouchers[code]) {
        activeVoucher = { code, ...validVouchers[code] };
        hid.value = code;
        msg.innerHTML = '<span style="color:var(--sage);font-weight:600;"><i class="bi bi-check-circle me-1"></i>Applied: ' + activeVoucher.label + '</span>';
        recalc();
      } else {
        activeVoucher = null;
        hid.value = '';
        msg.innerHTML = '<span style="color:var(--danger);"><i class="bi bi-x-circle me-1"></i>Invalid or expired code.</span>';
        recalc();
      }
    }

    function toggleVouchers(btn) {
      const list = document.getElementById('voucherList');
      const chev = document.getElementById('voucherChevron');
      list.classList.toggle('open');
      chev.className = list.classList.contains('open') ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
      btn.querySelector('span').textContent = list.classList.contains('open') ? 'Hide vouchers' : 'Show available vouchers';
    }

    document.querySelectorAll('.voucher-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.voucher-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        document.getElementById('voucherInput').value = card.dataset.code;
        applyVoucher();
      });
    });

    /* ─── Payment panels ─── */
    function selectPay(id) {
      document.querySelectorAll('.pay-method').forEach(m => {
        m.classList.remove('active');
        m.querySelector('input[type=radio]').checked = false;
      });
      const el = document.getElementById(id);
      el.classList.add('active');
      el.querySelector('input[type=radio]').checked = true;
    }

    /* ─── Card number auto-advance ─── */
    function fwdCard(from, to) {
      const el = document.getElementById(from);
      el.value = el.value.replace(/\D/g, '');
      if (el.value.length === 4) document.getElementById(to)?.focus();
    }

    /* ─── Totals ─── */
    function recalc() {
      const sub = Object.keys(qtys).reduce((a, id) => a + prices[id] * qtys[id], 0);
      let disc = 0;
      let shipCost = shippingFee;
      if (activeVoucher) {
        if (activeVoucher.type === 'pct') disc = Math.round(sub * activeVoucher.val / 100);
        if (activeVoucher.type === 'fixed') disc = activeVoucher.val;
        if (activeVoucher.type === 'ship') { shipCost = 0; document.getElementById('sumShipping').textContent = 'Free'; }
      }
      const total = sub - disc + shipCost;
      document.getElementById('sumSubtotal').textContent =
        '₱' + sub.toLocaleString('en-PH', { minimumFractionDigits: 2 });
      if (disc > 0) {
        document.getElementById('discRow').style.display = '';
        document.getElementById('sumDisc').textContent =
          '−₱' + disc.toLocaleString('en-PH', { minimumFractionDigits: 2 });
      } else {
        document.getElementById('discRow').style.display = 'none';
      }
      document.getElementById('sumTotal').textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
      updateItemCount();
    }

    function updateItemCount() {
      const total = Object.values(qtys).reduce((a, b) => a + b, 0);
      document.getElementById('itemCountBadge').textContent = total + (total === 1 ? ' item' : ' items');
    }

    function renderSummaryItems() {
      const wrap = document.getElementById('summaryItems');
      wrap.innerHTML = Object.keys(qtys).map(id => `
      <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:4px 0;color:var(--text-muted);">
        <span>${names[id]} <span style="color:var(--text-main);font-weight:600;">×${qtys[id]}</span></span>
        <span style="color:var(--text-main);">₱${(prices[id] * qtys[id]).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
      </div>`).join('');
    }

    /* ─── CALABARZON City → Barangay cascading dropdowns ─── */
    const calabarzonBarangays = {
      'Bacoor':         ['Alima', 'Aniban I', 'Aniban II', 'Aniban III', 'Aniban IV', 'Aniban V',
                         'Bayanan', 'Campo Santo', 'Daang Bukid', 'Digman', 'Dulong Bayan',
                         'Habay I', 'Habay II', 'Kaingin', 'Ligas I', 'Ligas II', 'Ligas III',
                         'Mabolo I', 'Mabolo II', 'Mabolo III', 'Maliksi I', 'Maliksi II',
                         'Maliksi III', 'Niog I', 'Niog II', 'Niog III', 'Panapaan I',
                         'Panapaan II', 'Panapaan III', 'Panapaan IV', 'Panapaan V',
                         'Panapaan VI', 'Panapaan VII', 'Panapaan VIII', 'Queens Row Central',
                         'Queens Row East', 'Queens Row West', 'Real I', 'Real II', 'Salinas I',
                         'Salinas II', 'San Nicolas I', 'San Nicolas II', 'San Nicolas III',
                         'Sineguelasan', 'Tabing Dagat', 'Talaba I', 'Talaba II', 'Talaba III',
                         'Talaba IV', 'Talaba V', 'Talaba VI', 'Talaba VII', 'Zapote I',
                         'Zapote II', 'Zapote III', 'Zapote IV', 'Zapote V'],
      'Cavite City':    ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5',
                         'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10',
                         'Barangay 11', 'Barangay 12', 'Barangay 13', 'Barangay 14',
                         'Barangay 15', 'Barangay 16', 'Barangay 17', 'Barangay 18',
                         'Barangay 19', 'Barangay 20', 'Barangay 21', 'Barangay 22',
                         'Barangay 23', 'Barangay 24', 'Barangay 25', 'Barangay 26',
                         'Barangay 27', 'Barangay 28', 'Barangay 29', 'Barangay 30',
                         'Barangay 31', 'Barangay 32', 'Barangay 33', 'Barangay 34',
                         'Barangay 35', 'Barangay 36', 'Barangay 37', 'Barangay 38',
                         'Barangay 39', 'Barangay 40', 'Barangay 41', 'Barangay 42',
                         'Barangay 43', 'Barangay 44', 'Barangay 45', 'Barangay 46',
                         'Barangay 47', 'Barangay 48', 'Barangay 49', 'Barangay 50',
                         'Barangay 51', 'Barangay 52', 'Barangay 53', 'Barangay 54',
                         'Barangay 55', 'Barangay 56', 'Barangay 57', 'Barangay 58',
                         'Barangay 59', 'Barangay 60', 'Barangay 61', 'Barangay 62',
                         'Barangay 63', 'Barangay 64', 'Barangay 65', 'Barangay 66',
                         'Barangay 67', 'Barangay 68', 'Barangay 69', 'Barangay 70',
                         'Barangay 71', 'Barangay 72', 'Barangay 73', 'Barangay 74',
                         'Barangay 75', 'Barangay 76', 'Barangay 77', 'Barangay 78',
                         'Barangay 79', 'Barangay 80', 'Barangay 81', 'Barangay 82'],
      'Dasmariñas':     ['Burol', 'Burol I', 'Burol II', 'Burol III', 'Datu Esmael',
                         'Emmanuel Bergado I', 'Emmanuel Bergado II', 'Fatima I',
                         'Fatima II', 'Fatima III', 'H-2', 'Langkaan I', 'Langkaan II',
                         'Luzviminda I', 'Luzviminda II', 'Paliparan I', 'Paliparan II',
                         'Paliparan III', 'Poblacion', 'Sabang', 'Salawag', 'Salitran I',
                         'Salitran II', 'Salitran III', 'Salitran IV', 'San Agustin I',
                         'San Agustin II', 'San Agustin III', 'San Andres I', 'San Andres II',
                         'San Antonio de Padua I', 'San Antonio de Padua II', 'San Dionisio',
                         'San Esteban', 'San Francisco I', 'San Francisco II', 'San Isidro I',
                         'San Isidro II', 'San Jose', 'San Juan', 'San Lorenzo Ruiz I',
                         'San Lorenzo Ruiz II', 'San Luis I', 'San Luis II', 'San Manuel I',
                         'San Manuel II', 'San Mateo', 'San Miguel I', 'San Miguel II',
                         'San Nicolas I', 'San Nicolas II', 'San Pedro', 'San Roque',
                         'San Simon', 'Santa Cristina I', 'Santa Cristina II',
                         'Santa Cruz I', 'Santa Cruz II', 'Santa Fe', 'Santa Lucia',
                         'Santa Maria', 'Santo Cristo', 'Santo Niño I', 'Santo Niño II',
                         'Victoria Reyes', 'Zone I', 'Zone II', 'Zone III', 'Zone IV'],
      'Imus':           ['Alapan I-A', 'Alapan I-B', 'Alapan I-C', 'Alapan II-A', 'Alapan II-B',
                         'Anabu I-A', 'Anabu I-B', 'Anabu I-C', 'Anabu I-D', 'Anabu I-E',
                         'Anabu I-F', 'Anabu I-G', 'Anabu II-A', 'Anabu II-B', 'Anabu II-C',
                         'Anabu II-D', 'Anabu II-E', 'Anabu II-F', 'Bagong Silang',
                         'Bayan Luma I', 'Bayan Luma II', 'Bayan Luma III', 'Bayan Luma IV',
                         'Bayan Luma V', 'Bayan Luma VI', 'Bayan Luma VII', 'Bayan Luma VIII',
                         'Bayan Luma IX', 'Bucandala I', 'Bucandala II', 'Bucandala III',
                         'Bucandala IV', 'Bucandala V', 'Buhay na Tubig', 'Carsadang Bago I',
                         'Carsadang Bago II', 'Magdalo', 'Maharlika', 'Malagasang I-A',
                         'Malagasang I-B', 'Malagasang I-C', 'Malagasang I-D', 'Malagasang I-E',
                         'Malagasang I-F', 'Malagasang I-G', 'Malagasang II-A',
                         'Malagasang II-B', 'Malagasang II-C', 'Malagasang II-D',
                         'Malagasang II-E', 'Malagasang II-F', 'Mariano Espeleta I',
                         'Mariano Espeleta II', 'Mariano Espeleta III', 'Medicion I-A',
                         'Medicion I-B', 'Medicion I-C', 'Medicion I-D', 'Medicion II-A',
                         'Medicion II-B', 'Medicion II-C', 'Medicion II-D', 'Medicion II-E',
                         'Medicion II-F', 'Pag-asa I', 'Pag-asa II', 'Pag-asa III',
                         'Palico I', 'Palico II', 'Palico III', 'Palico IV', 'Pasong Buaya I',
                         'Pasong Buaya II', 'Pinagbuklod', 'Poblacion I-A', 'Poblacion I-B',
                         'Poblacion I-C', 'Poblacion II-A', 'Poblacion II-B', 'Poblacion III-A',
                         'Poblacion III-B', 'Poblacion IV-A', 'Poblacion IV-B', 'Poblacion IV-C',
                         'Poblacion IV-D', 'Real I', 'Real II', 'Salitrero', 'Tanzang Luma I',
                         'Tanzang Luma II', 'Tanzang Luma III', 'Tanzang Luma IV',
                         'Tanzang Luma V', 'Tanzang Luma VI', 'Toclong I-A', 'Toclong I-B',
                         'Toclong I-C', 'Toclong II-A', 'Toclong II-B'],
      'General Trias':  ['Alingaro', 'Arnaldo', 'Bacao I', 'Bacao II', 'Bagumbayan',
                         'Biclatan', 'Buenavista I', 'Buenavista II', 'Buenavista III',
                         'Corregidor', 'Dulong Bayan', 'Governor Ferrer', 'Javalera',
                         'Manggahan', 'Navarro', 'Panungyanan', 'Pasong Camachile I',
                         'Pasong Camachile II', 'Pasong Kawayan I', 'Pasong Kawayan II',
                         'Pinagtipunan', 'Prinza', 'Sampalucan', 'San Francisco',
                         'San Gabriel', 'San Juan I', 'San Juan II', 'Santa Clara',
                         'Santiago', 'Tapia', 'Tejero', 'Vibora', 'San Roque'],
      'Tagaytay':       ['Asisan', 'Bagong Tubig', 'Calabuso', 'Dapdap', 'Francisco',
                         'Guinhawa', 'Iruhin East', 'Iruhin South', 'Iruhin West',
                         'Kaybagal Central', 'Kaybagal North', 'Kaybagal South',
                         'Mag-asawang Ilat', 'Maharlika East', 'Maharlika West',
                         'Maitim I', 'Maitim II', 'Mendez Crossing East',
                         'Mendez Crossing West', 'Neogan', 'Patutong Malaki North',
                         'Patutong Malaki South', 'Sambong', 'San Jose', 'Silang Junction North',
                         'Silang Junction South', 'Sungay North', 'Sungay South',
                         'Tolentino East', 'Tolentino West', 'Zambal'],
      'Trece Martires': ['Bagong Kalsada', 'Cabezas', 'Cabuco', 'Conchu', 'De Ocampo',
                         'Gregorio', 'Inocencio', 'Lallana', 'Luciano', 'Osorio',
                         'Poblacion 1', 'Poblacion 2', 'Poblacion 3', 'Poblacion 4',
                         'San Agustin', 'San Antonio', 'San Francisco', 'San Jose',
                         'San Juan', 'San Lorenzo', 'San Nicolas', 'San Roque',
                         'Santa Cruz'],
      'Biñan':          ['Biñan', 'Bungahan', 'Canlalay', 'Casile', 'De La Paz',
                         'Ganado', 'Langkiwa', 'Loma', 'Malaban', 'Malamig',
                         'Mamplasan', 'Platero', 'Poblacion', 'San Antonio',
                         'San Francisco', 'San Jose', 'San Vicente', 'Santo Domingo',
                         'Santo Tomas', 'Soro-soro', 'Timbao', 'Tubigan', 'Zapote'],
      'Calamba':        ['Bagong Kalsada', 'Banadero', 'Banlic', 'Barandal', 'Batino',
                         'Bubuyan', 'Bucal', 'Bunggo', 'Burol', 'Camaligan',
                         'Canlubang', 'Halang', 'Hornalan', 'Kay-Anlog', 'La Mesa',
                         'Laguerta', 'Lawa', 'Lecheria', 'Lingga', 'Looc',
                         'Mabato', 'Majada', 'Makiling', 'Mapagong', 'Masili',
                         'Mayapa', 'Milagrosa', 'Paciano Rizal', 'Palingon',
                         'Palo-Alto', 'Pansol', 'Parian', 'Prinza', 'Puting Lupa',
                         'Real', 'Saimsim', 'Sampiruhan', 'San Cristobal', 'San Jose',
                         'San Juan', 'Sirang Lupa', 'Sucol', 'Tulo', 'Turbina',
                         'Ulango', 'Uwisan'],
      'San Pablo':      ['Atisan', 'Bagong Bayan', 'Bagong Pook', 'Bautista',
                         'Concepcion', 'Del Remedio', 'Dolores', 'San Antonio 1',
                         'San Antonio 2', 'San Bartolome', 'San Buenaventura',
                         'San Crispin', 'San Cristobal', 'San Diego', 'San Francisco',
                         'San Gabriel', 'San Gregorio', 'San Ignacio', 'San Isidro',
                         'San Joaquin', 'San Jose', 'San Juan', 'San Lorenzo',
                         'San Lucas 1', 'San Lucas 2', 'San Marcos', 'San Mateo',
                         'San Miguel', 'San Nicolas', 'San Pedro', 'San Rafael',
                         'San Roque', 'San Vicente', 'Santa Ana', 'Santa Catalina',
                         'Santa Cruz', 'Santa Elena', 'Santa Felomina', 'Santa Isabel',
                         'Santa Maria', 'Santa Maria Magdalena', 'Santa Monica',
                         'Santa Veronica', 'Santiago I', 'Santiago II', 'Santisimo Rosario',
                         'Santo Angel', 'Santo Cristo', 'Santo Niño', 'Soledad'],
      'Santa Rosa':     ['Aplaya', 'Balibago', 'Caingin', 'Dila', 'Dita',
                         'Don Jose', 'Ibaba', 'Kanluran', 'Labas', 'Macabling',
                         'Malitlit', 'Malusak', 'Market Area', 'Pooc', 'Pulong Santa Cruz',
                         'Sinalhan', 'Tagapo'],
      'San Pedro':      ['Bagong Silang', 'Calendola', 'Chrysanthemum', 'Cuyab',
                         'Estrella', 'G.S.I.S.', 'Kalayaan', 'Kamagong', 'Kaytapos',
                         'Langgam', 'Laram', 'Magsaysay', 'Mahogany', 'Narra',
                         'Nueva', 'Pacita 1', 'Pacita 2', 'Poblacion', 'Riverside',
                         'Rosario', 'Sampaguita', 'San Antonio', 'San Lorenzo Ruiz',
                         'San Roque', 'San Vicente', 'Santo Niño', 'United Bayanihan',
                         'United Paraiso'],
      'San Jose':       ['Bagumbayan', 'Caloocan', 'Cerrudo', 'Danacbunga',
                         'Don Luis', 'Francisco', 'Galalan', 'Ibabang Butnong',
                         'Ilayang Butnong', 'Kagsiahan I', 'Kagsiahan II',
                         'Kinabuhayan', 'Lakawan', 'Liyang', 'Mabato',
                         'Mamatid', 'May-it', 'Pinagdanglayan', 'Poblacion',
                         'Puting Kahoy', 'San Pedro', 'Santa Catalina',
                         'Tagbakin', 'Talangan'],
      'Batangas City':  ['Alangilan', 'Balagtas', 'Balete', 'Banaba Center',
                         'Banaba Kanluran', 'Banaba Silangan', 'Barangay 1',
                         'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5',
                         'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9',
                         'Barangay 10', 'Barangay 11', 'Barangay 12', 'Barangay 13',
                         'Barangay 14', 'Barangay 15', 'Barangay 16', 'Barangay 17',
                         'Barangay 18', 'Barangay 19', 'Barangay 20', 'Barangay 21',
                         'Barangay 22', 'Barangay 23', 'Barangay 24', 'Bilogo',
                         'Bolbok', 'Bukal', 'Calicanto', 'Catandala', 'Concepcion',
                         'Conde Itaas', 'Conde Labac', 'Cuta', 'Dalig', 'Dela Paz',
                         'Dela Paz Pulot Aplaya', 'Dela Paz Pulot Itaas', 'Domoclay',
                         'Dumantay', 'Gulod', 'Haligue Kanluran', 'Haligue Silangan',
                         'Ilihan', 'Kumba', 'Kumintang Ibaba', 'Kumintang Ilaya',
                         'Libjo', 'Liponpon, Isla Verde', 'Maapas', 'Mabacong',
                         'Mahabang Dahilig', 'Mahabang Parang', 'Mahacot Silangan',
                         'Mahacot Kanluran', 'Malalim', 'Malibayo', 'Malitam',
                         'Maruclap', 'Mataasnakahoy', 'Paharang Kanluran',
                         'Paharang Silangan', 'Pallocan Kanluran', 'Pallocan Silangan',
                         'Patay', 'Pinamucan', 'Pinamucan Ibaba', 'Pinamucan Silangan',
                         'Sampaga', 'San Agapito', 'San Agustin', 'San Andres',
                         'San Antonio', 'San Bruno', 'San Celestino', 'San Fernando',
                         'San Francisco', 'San Isidro', 'San Joaquin', 'San Jose',
                         'San Jose Sico', 'San Juan', 'San Luis', 'San Manuel',
                         'San Marcos', 'San Miguel', 'San Pascual', 'San Pedro',
                         'San Rafael', 'San Roque', 'San Vicente', 'Santa Clara',
                         'Santa Cruz', 'Santa Maria', 'Santa Rita Aplaya',
                         'Santa Rita Karsada', 'Santo Domingo', 'Santo Niño',
                         'Santor', 'Sico', 'Sorosoro Ibaba', 'Sorosoro Ilaya',
                         'Sorosoro Karsada', 'Tabangao', 'Talahib Pandayan',
                         'Talahib Payapa', 'Talumpoc Kanluran', 'Talumpoc Silangan',
                         'Tinga', 'Tulo', 'Wawa'],
      'Lipa':           ['Adya', 'Anilao', 'Anilao-Labac', 'Antipolo del Norte',
                         'Antipolo del Sur', 'Bagong Pook', 'Balintawak', 'Banaybanay',
                         'Bolbok', 'Bugtong na Pulo', 'Bulacnin', 'Bulaklakan',
                         'Calamias', 'Cumba', 'Dagatan', 'Duhatan', 'Halang',
                         'Inosloban', 'Kaligayan', 'Katuukin', 'Lubac', 'Mahabang Parang',
                         'Malagonlong', 'Malitlit', 'Marauoy', 'Mataas na Lupa',
                         'Munting Pulo', 'Pagolingin Bata', 'Pagolingin East',
                         'Pagolingin West', 'Pangao', 'Pinagkawitan', 'Pinagtongulan',
                         'Plaridel', 'Poblacion Barangay 1', 'Poblacion Barangay 2',
                         'Poblacion Barangay 3', 'Poblacion Barangay 4',
                         'Poblacion Barangay 5', 'Poblacion Barangay 6',
                         'Poblacion Barangay 7', 'Poblacion Barangay 8',
                         'Poblacion Barangay 9', 'Poblacion Barangay 9-A',
                         'Poblacion Barangay 10', 'Poblacion Barangay 11',
                         'Poblacion Barangay 12', 'Poblacion Barangay 13',
                         'Poblacion Barangay 14', 'Pusil', 'Quezon', 'Rizal',
                         'Sampaguita', 'San Benito', 'San Carlos', 'San Celestino',
                         'San Francisco', 'San Guillermo', 'San Jose', 'San Juan',
                         'San Lucas', 'San Martin', 'San Miguel', 'San Pablo',
                         'San Pedro', 'San Rafael', 'San Salvador', 'San Sebastian',
                         'Santo Toribio', 'Sico', 'Talisay', 'Tambo', 'Tangob',
                         'Tanguay', 'Tibig', 'Tipacan'],
      'Tanauan':        ['Altura Bata', 'Altura Matanda', 'Altura-South',
                         'Ambulong', 'Bagbag', 'Bagumbayan', 'Balele',
                         'Banjo East', 'Banjo West', 'Bilog-bilog', 'Boot',
                         'Cale', 'Darasa', 'Gonzales', 'Hidalgo', 'Janopol',
                         'Janopol Oriental', 'Laurel', 'Luyos', 'Mabini',
                         'Malaking Pulo', 'Maria Paz', 'Maugat', 'Montalbo',
                         'Pag-asa', 'Pangao', 'Poblacion Barangay 1',
                         'Poblacion Barangay 2', 'Poblacion Barangay 3',
                         'Poblacion Barangay 4', 'Poblacion Barangay 5',
                         'Poblacion Barangay 6', 'Poblacion Barangay 7',
                         'Poblacion Barangay 8', 'Sala', 'Sambat', 'San Jose',
                         'Santol', 'Santor', 'Sulpoc', 'Suplang', 'Talaga',
                         'Tinurik', 'Trapiche', 'Ulango', 'Wawa'],
      'Sto. Tomas':     ['Poblacion I', 'Poblacion II', 'Poblacion III', 'Poblacion IV',
                         'San Agustin', 'San Antonio', 'San Bartolome', 'San Felix',
                         'San Fernando', 'San Francisco', 'San Isidro', 'San Joaquin',
                         'San Jose', 'San Juan', 'San Luis', 'San Miguel', 'San Pablo',
                         'San Pedro', 'San Rafael', 'San Roque', 'Santa Ana',
                         'Santa Maria', 'Santiago', 'Santo Niño', 'Taysan'],
      'Bauan':          ['Alagao', 'Aplaya', 'As-Is', 'Bagong Silang', 'Bagong Sikat',
                         'Balayong', 'Barangay 1', 'Barangay 2', 'Barangay 3',
                         'Barangay 4', 'Barangay 5', 'Barangay 6', 'Barangay 7',
                         'Barangay 8', 'Barangay 9', 'Barangay 10', 'Barangay 11',
                         'Barangay 12', 'Bolbok', 'Colvo', 'Cupang', 'Durungao',
                         'Gulibay', 'Inicbulan', 'Locloc', 'Magalang-Galang',
                         'Malindig', 'Manalupang', 'Manghinao', 'Maragondon',
                         'Matanlang', 'Madalunot', 'Pagasinan', 'Panhulan',
                         'Pila', 'Puting-Kawayan', 'San Andres', 'San Antonio',
                         'San Felipe', 'San Fernando', 'San Francisco', 'San Isidro',
                         'San Juan', 'San Luis', 'San Pablo', 'San Pedro',
                         'San Rafael', 'San Roque', 'San Teodoro', 'San Vicente',
                         'Santa Cruz', 'Santa Maria', 'Santa Rita', 'Santo Domingo',
                         'Sinisian', 'Talahiban I', 'Talahiban II', 'Talipapang',
                         'Tingloy'],
      'Nasugbu':        ['Aga', 'Balaytigui', 'Banilad', 'Barangay 1', 'Barangay 2',
                         'Barangay 3', 'Barangay 4', 'Barangay 5', 'Barangay 6',
                         'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10',
                         'Barangay 11', 'Barangay 12', 'Bilaran', 'Bucana',
                         'Bungahan', 'Calayo', 'Catandaan', 'Cogunan', 'Dayap',
                         'Halloran', 'Holvo', 'Kaylaway', 'Latag', 'Looc',
                         'Lumbangan', 'Malapad na Bato', 'Mataas na Pulo',
                         'Maugat', 'Papaya', 'Putat', 'Reparo', 'San Diego',
                         'San Pascual', 'Santa Clara', 'Talangan'],
      'Antipolo':       ['Bagong Nayon', 'Beverly Hills', 'Calawis', 'Cupang',
                         'Dalig', 'Dela Paz', 'Inarawan', 'Mambugan', 'Mayamot',
                         'Muntindilao', 'San Isidro', 'San Jose', 'San Juan',
                         'San Luis', 'San Roque', 'Santa Cruz'],
      'Angono':         ['Bagumbayan', 'Kalayaan', 'Poblacion Ibaba', 'Poblacion Itaas',
                         'San Isidro', 'San Pedro', 'San Roque', 'San Vicente'],
      'Cainta':         ['San Andres', 'San Isidro', 'San Juan', 'San Roque',
                         'Santa Rosa', 'Santo Domingo', 'Santo Niño'],
      'Rodriguez':      ['Balite', 'Burgos', 'Geronimo', 'Macabud', 'Manggahan',
                         'Mascap', 'Montalban', 'Puray', 'Rosario', 'San Isidro',
                         'San Jose', 'San Rafael'],
      'Taytay':         ['Bagumbayan', 'Dolores', 'Muzon', 'San Isidro', 'San Jose',
                         'San Juan', 'Santa Ana', 'Santa Maria'],
      'Teresa':         ['Bagumbayan', 'Calumpang', 'May-iba', 'San Gabriel',
                         'San Roque'],
      'Lucena':         ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4',
                         'Barangay 5', 'Barangay 6', 'Barangay 7', 'Barangay 8',
                         'Barangay 9', 'Barangay 10', 'Barangay 11', 'Barangay 12',
                         'Barangay 13', 'Barangay 14', 'Barangay 15', 'Barangay 16',
                         'Barangay 17', 'Barangay 18', 'Barangay 19', 'Barangay 20',
                         'Barangay 21', 'Barangay 22', 'Barangay 23', 'Barangay 24',
                         'Barangay 25', 'Barangay 26', 'Barangay 27', 'Barangay 28',
                         'Barangay 29', 'Barangay 30', 'Barangay 31', 'Barangay 32',
                         'Barangay 33', 'Cotta', 'Gulang-gulang', 'Ibabang Dupay',
                         'Ibabang Iyam', 'Ibabang Talim', 'Ilayang Dupay', 'Ilayang Iyam',
                         'Ilayang Talim', 'Isabang', 'Market View', 'Mayao Crossing',
                         'Mayao Kanluran', 'Mayao Parada', 'Mayao Silangan', 'Ransohan',
                         'Salinas', 'Talao-talao'],
      'Tayabas':        ['Alitao', 'Alsam Ibaba', 'Alsam Ilaya', 'Angeles Zone I',
                         'Angeles Zone II', 'Angeles Zone III', 'Angeles Zone IV',
                         'Baguio', 'Banot', 'Bukal', 'Calantas', 'Calumpang',
                         'Dapdap', 'Domoit', 'Gabut', 'Hagonghong', 'Ibabang Alsam',
                         'Ibabang Bukal', 'Ibabang Kinagunan', 'Ibabang Sisi',
                         'Ibabang Tayuman', 'Ilayang Alsam', 'Ilayang Bukal',
                         'Ilayang Kinagunan', 'Ilayang Sisi', 'Ilayang Tayuman',
                         'Lakawan', 'Lita', 'Malaoa', 'Masin', 'Mate', 'Mateuna',
                         'Mayewee', 'Nangka', 'Opias', 'Palale', 'Pangbayanan',
                         'Pang-il', 'Poblacion', 'Potol', 'San Diego', 'San Isidro',
                         'San Roque', 'Silang', 'Sta. Cruz', 'Tinulan'],
      'Sariaya':        ['Antipolo', 'Balubal', 'Bignay I', 'Bignay II',
                         'Bukal', 'Cagsiay I', 'Cagsiay II', 'Cagsiay III',
                         'Concepcion I', 'Concepcion II', 'Concepcion III',
                         'Concepcion IV', 'Concepcion V', 'Concepcion VI',
                         'Consuelo', 'Coo', 'Gatbo', 'Guadalupe', 'Huguan',
                         'Lalig', 'Lokong', 'Mamala I', 'Mamala II', 'Manggalayan',
                         'Manggalayan Bundok', 'Manggalayan Labak', 'Monte Cristo',
                         'P. Burgos', 'Pisa', 'Poblacion I', 'Poblacion II',
                         'Poblacion III', 'Poblacion IV', 'San Antonio',
                         'San Buenaventura', 'San Eugenio', 'San Fernando',
                         'San Francisco', 'San Isidro', 'San Jose', 'San Juan',
                         'San Luis I', 'San Luis II', 'San Pablo', 'San Pedro',
                         'San Roque', 'Santa Cruz', 'Santa Lucia', 'Santa Maria',
                         'Santor', 'Simbahan', 'Tumbaga I', 'Tumbaga II'],
      'Candelaria':     ['Bukal Norte', 'Bukal Sur', 'Calumpang', 'Dita',
                         'Gulod', 'Kinabuhayan', 'Langgam', 'Latangan',
                         'Mabini', 'Mahabang Parang', 'Malabanban Norte',
                         'Malabanban Sur', 'Masin Norte', 'Masin Sur',
                         'Pagsanjan', 'Pangil', 'Pinagbakahan', 'Poblacion',
                         'Puting Bato', 'Quezon', 'San Andres',
                         'San Gabriel', 'San Gregorio', 'San Ildefonso',
                         'San Isidro', 'San Juan', 'San Roque', 'Santa Catalina',
                         'Santa Maria', 'Santo Niño'],
      'Dolores':        ['Antonino', 'Bagong Anyo', 'Bayanihan', 'Buliran',
                         'Bungluran', 'Cabatuan', 'Calawis', 'Calumpang',
                         'Dongon', 'Gibanga', 'Kabulusan', 'Kagtalaba',
                         'Magsaysay', 'May-it', 'Nagsinamo', 'Palamabis',
                         'Palina', 'Poblacion', 'San Antonio', 'San Diego',
                         'San Isidro', 'San Mateo', 'Santo Tomas'],
      'Pagbilao':       ['Alupaye', 'Apasan', 'Baguio', 'Bantigue',
                         'Barangay 1', 'Barangay 2', 'Barangay 3',
                         'Barangay 4', 'Barangay 5', 'Bigo', 'Binahaan',
                         'Bukal', 'Ibabang Atisan', 'Ibabang Tayuman',
                         'Ilayang Atisan', 'Ilayang Tayuman', 'Kanluran Malicboy',
                         'Krustan', 'Kung ilang', 'Lagyo', 'Lakawan',
                         'Lokomotion', 'Luya-luya', 'Malicboy Silangan',
                         'Mapagong', 'Maygatasan', 'Pahagan', 'Panghibaan',
                         'Poblacion', 'Silang Malicboy', 'Talipan', 'Tukalan',
                         'Yuni']
    };

    // Default barangay list for any city not explicitly listed above.
    const defaultBarangays = ['Poblacion'];

    const citySelectEl    = document.getElementById('citySelect');
    const barangaySelectEl = document.getElementById('barangaySelect');
    const provinceSelectEl = document.getElementById('provinceSelect');

    function populateBarangays() {
      if (!citySelectEl || !barangaySelectEl) return;
      const city = citySelectEl.value;
      const list = calabarzonBarangays[city] || defaultBarangays;

      barangaySelectEl.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = city ? 'Select barangay…' : 'Select city first…';
      barangaySelectEl.appendChild(placeholder);

      list.forEach(name => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        barangaySelectEl.appendChild(opt);
      });

      barangaySelectEl.disabled = !city;
    }

    if (citySelectEl) {
      citySelectEl.addEventListener('change', populateBarangays);
      populateBarangays(); // initial state
    }

    /* Province → Cities (CALABARZON). Each key matches an <option> in
       the province dropdown; the value is the list of cities that will
       populate the city <select> when the user picks that province. */
    const calabarzonCitiesByProvince = {
      'Cavite': [
        'Bacoor', 'Cavite City', 'Dasmariñas', 'Imus',
        'General Trias', 'Tagaytay', 'Trece Martires'
      ],
      'Laguna': [
        'Biñan', 'Calamba', 'San Pablo', 'Santa Rosa',
        'San Pedro', 'San Jose'
      ],
      'Batangas': [
        'Batangas City', 'Lipa', 'Tanauan', 'Sto. Tomas',
        'Bauan', 'Nasugbu'
      ],
      'Rizal': [
        'Antipolo', 'Angono', 'Cainta', 'Rodriguez',
        'Taytay', 'Teresa'
      ],
      'Quezon': [
        'Lucena', 'Tayabas', 'Sariaya', 'Candelaria',
        'Dolores', 'Pagbilao'
      ]
    };

    function populateCities() {
      if (!provinceSelectEl || !citySelectEl) return;
      const province = provinceSelectEl.value;

      // Reset the city dropdown.
      citySelectEl.innerHTML = '';
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = province ? 'Select city…' : 'Select province first…';
      citySelectEl.appendChild(placeholder);

      if (province && calabarzonCitiesByProvince[province]) {
        calabarzonCitiesByProvince[province].forEach(name => {
          const opt = document.createElement('option');
          opt.value = name;
          opt.textContent = name;
          citySelectEl.appendChild(opt);
        });
        citySelectEl.disabled = false;
      } else {
        citySelectEl.disabled = true;
      }

      // Clear the city value and cascade-reset the barangay dropdown.
      citySelectEl.value = '';
      populateBarangays();
      // Clear stale validation state.
      if (typeof clearError === 'function') {
        clearError('city');
        clearError('barangay');
      }
    }

    if (provinceSelectEl) {
      provinceSelectEl.addEventListener('change', populateCities);
      populateCities(); // initial state
    }

    if (provinceSelectEl) {
      provinceSelectEl.addEventListener('change', populateCities);
      populateCities(); // initial state
    }

    /* ─── LIVE PHONE VALIDATION ─── */
    const phoneInput = document.getElementById('phoneInput');
    const phoneRegex = /^(?:\+63|0)9\d{9}$/;
    const zipRegex   = /^\d{4}$/;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    /* ── Validation helpers ── */
    const form         = document.getElementById('checkoutForm');
    const errorBanner  = document.getElementById('formErrorBanner');
    const errorBannerText = document.getElementById('formErrorBannerText');
    const placeBtn     = document.getElementById('placeOrderBtn');

    // Returns the first currently visible element with [data-field="X"]
    function fieldEl(name) {
      return form.querySelector('[data-field="' + name + '"]');
    }
    function errorEl(name) {
      return form.querySelector('[data-error-for="' + name + '"]');
    }
    function isVisible(el) {
      if (!el) return false;
      // The manual address block is hidden when the user already has
      // saved addresses; ignore its fields in that case.
      const block = document.getElementById('manualAddressBlock');
      if (block && block.contains(el) &&
          (block.offsetParent === null || getComputedStyle(block).display === 'none')) {
        return false;
      }
      return el.offsetParent !== null;
    }

    function setError(name, message) {
      const input  = fieldEl(name);
      const output = errorEl(name);
      if (input) {
        input.classList.add('field-error');
        // restart shake animation
        input.classList.remove('shake');
        void input.offsetWidth;
        input.classList.add('shake');
      }
      if (output) {
        if (message) {
          output.textContent = message;
          output.classList.add('show');
        } else {
          output.classList.remove('show');
        }
      }
    }

    function clearError(name) {
      const input  = fieldEl(name);
      const output = errorEl(name);
      if (input)  input.classList.remove('field-error', 'shake');
      if (output) output.classList.remove('show');
    }

    function showBanner(text) {
      if (!errorBanner) return;
      if (errorBannerText) errorBannerText.textContent = text;
      errorBanner.classList.add('show');
      errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function hideBanner() {
      if (errorBanner) errorBanner.classList.remove('show');
    }

    /* ── Live listeners (clear error as user types) ── */
    document.querySelectorAll('.field-validated').forEach(el => {
      const name = el.getAttribute('data-field');
      if (!name) return;
      el.addEventListener('input',  () => clearError(name));
      el.addEventListener('change', () => clearError(name));
    });

    // Extra live phone format helper
    if (phoneInput) {
      phoneInput.addEventListener('blur', function () {
        if (!isVisible(phoneInput)) return;
        const v = this.value.replace(/[\s-]/g, '');
        if (v !== '' && !phoneRegex.test(v)) {
          setError('phone', 'Please enter a valid PH mobile number (+63 9XX XXX XXXX or 09XX XXX XXXX).');
        } else {
          clearError('phone');
        }
      });
    }

    /* ── Full validation, called on submit ── */
    function validateCheckout() {
      hideBanner();
      let firstInvalid = null;
      let problems = 0;

      // 1. Address — either a saved radio is selected, or all manual fields are filled.
      const savedAddressChosen = form.querySelector('input[name="address_id"]:checked');
      const usingManual = !savedAddressChosen;

      if (usingManual) {
        const required = [
          'first_name', 'last_name', 'phone',
          'province', 'city', 'barangay', 'street', 'postal_code'
        ];
        required.forEach(name => {
          const el = fieldEl(name);
          if (!el) return;
          const v = (el.value || '').trim();
          if (v === '') {
            setError(name, name.replace('_', ' ') + ' is required.');
            problems++;
            if (!firstInvalid) firstInvalid = el;
            return;
          }
          if (name === 'phone' && !phoneRegex.test(v.replace(/[\s-]/g, ''))) {
            setError('phone', 'Please enter a valid PH mobile number (+63 9XX XXX XXXX or 09XX XXX XXXX).');
            problems++;
            if (!firstInvalid) firstInvalid = el;
            return;
          }
          if (name === 'postal_code' && !zipRegex.test(v)) {
            setError('postal_code', 'ZIP code must be exactly 4 digits.');
            problems++;
            if (!firstInvalid) firstInvalid = el;
            return;
          }
          clearError(name);
        });
      }

      // 2. Shipping method must be selected
      const shippingChosen = form.querySelector('input[name="shipping"]:checked');
      if (!shippingChosen) {
        showBanner('Please choose a shipping method.');
        problems++;
        if (!firstInvalid) firstInvalid = shippingChosen || form.querySelector('#ship-std');
      }

      // 3. Payment method must be selected
      const payChosen = form.querySelector('input[name="payMethod"]:checked');
      if (!payChosen) {
        showBanner('Please choose a payment method.');
        problems++;
        if (!firstInvalid) firstInvalid = payChosen || form.querySelector('#pay-cod');
        return problems === 0;
      }

      // 4. Card-specific validation
      if (payChosen.value === 'card') {
        const cn1 = document.getElementById('cn1');
        const cn2 = document.getElementById('cn2');
        const cn3 = document.getElementById('cn3');
        const cn4 = document.getElementById('cn4');
        const cardFields = [cn1, cn2, cn3, cn4];
        const allFilled = cardFields.every(c => c && c.value.replace(/\D/g, '').length === 4);
        if (!allFilled) {
          showBanner('Please enter all 16 digits of your card number.');
          problems++;
          if (!firstInvalid) firstInvalid = cn1;
        }
      }

      // 5. Cart must not be empty (defense in depth — the page also redirects)
      const totalItems = Object.values(qtys).reduce((a, b) => a + b, 0);
      if (totalItems <= 0) {
        showBanner('Your cart is empty.');
        problems++;
      }

      if (problems > 0) {
        if (!errorBanner.classList.contains('show') && firstInvalid) {
          showBanner('Please fix the highlighted fields below.');
        }
        if (firstInvalid) {
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          try { firstInvalid.focus({ preventScroll: true }); } catch (e) { /* old browsers */ }
        }
        return false;
      }
      return true;
    }

    /* ─── Submit handler ─── */
    form.addEventListener('submit', function (e) {
      // Always run our validator first and BLOCK the native submit on any error.
      if (!validateCheckout()) {
        e.preventDefault();
        e.stopPropagation();
        return false;
      }

      // Disable button + show spinner to prevent double submission.
      if (placeBtn) {
        placeBtn.disabled = true;
        placeBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2" ' +
          'style="border-color:#fff;border-right-color:transparent;"></span>Processing…';
      }
    });

    /* ─── Init ─── */
    renderSummaryItems();
    recalc();
  </script>

  <?php include __DIR__ . '/components/cookie/cookie-banner.php'; ?>
  <script src="scripts/cookie-consent.js"></script>
</body>

</html>