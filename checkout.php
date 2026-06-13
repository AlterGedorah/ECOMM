<?php
require_once __DIR__ . '/controllers/CheckoutController.php';

$controller = new CheckoutController();
$message    = null;
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
    $message     = $result['error'] ?? 'Failed to place order.';
    $messageType = 'danger';
}

// ── 2. Load checkout data for the view ─────────────────────────────────
$data  = $controller->prepareCheckoutPage();
$items = $data['items'];

// If cart is empty (and user just landed), push them back to the cart page.
if ($data['isEmpty'] && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Checkout — Fresh Harvest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="styles/checkout.css">
 <link rel="icon" href="images/icon.png">
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
    <div class="row g-3">

      <div class="col-lg-8">

        <!-- ── Section 1: Shipping Address ── -->
        <div class="card-section">
          <div class="section-head">
            <div class="section-num">1</div>
            <p class="section-title mb-0">Shipping address</p>
          </div>
          <div class="row g-3">
            <div class="col-sm-6">
              <label>First name</label>
              <input type="text" name="first_name" class="form-control" required
                     value="<?= htmlspecialchars(explode(' ', $data['user']['name'] ?? '')[0] ?? '') ?>" />
            </div>
            <div class="col-sm-6">
              <label>Last name</label>
              <input type="text" name="last_name" class="form-control" required
                     value="<?= htmlspecialchars(explode(' ', $data['user']['name'] ?? '')[1] ?? '') ?>" />
            </div>
            <div class="col-sm-6">
              <label>Phone number</label>
              <div class="input-icon-wrap">
                <i class="bi bi-phone"></i>
                <input type="tel" name="phone" class="form-control" placeholder="+63 9XX XXX XXXX" required />
              </div>
            </div>
            <div class="col-sm-6">
              <label>Province / Region</label>
              <select name="province" class="form-select" required>
                <option value="">Select province…</option>
                <option>Laguna</option>
                <option>Cavite</option>
                <option>Rizal</option>
                <option>Metro Manila</option>
                <option>Batangas</option>
              </select>
            </div>
            <div class="col-sm-6">
              <label>City / Municipality</label>
              <input type="text" name="city" class="form-control" placeholder="e.g. Calamba" required />
            </div>
            <div class="col-sm-6">
              <label>Barangay / Street</label>
              <input type="text" name="barangay" class="form-control" placeholder="e.g. Barangay Parian" required />
            </div>
            <div class="col-12">
              <label>Street address / House no.</label>
              <input type="text" name="street" class="form-control"
                     placeholder="House no., Building, Street name" required />
            </div>
            <div class="col-sm-4">
              <label>ZIP code</label>
              <input type="text" name="postal_code" class="form-control" placeholder="e.g. 4027" maxlength="6" required />
            </div>
            <div class="col-sm-8">
              <label>Delivery notes (optional)</label>
              <input type="text" name="landmark" class="form-control" placeholder="e.g. Leave at door, gate code #123" />
            </div>
          </div>

          <div class="divider mt-4"></div>
          <p class="section-title mb-2" style="font-size:.88rem;">Shipping method</p>

          <label class="ship-option chosen" id="ship-std">
            <input type="radio" name="shipping" value="standard" checked onchange="pickShipping(this,'ship-std','49')" />
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

        <!-- ── Section 2: Cart items ── -->
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
              <div class="cart-item" data-id="<?= (int)$item['id'] ?>" data-price="<?= (float)$item['price'] ?>">
                <div class="cart-thumb">
                  <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="">
                  <?php else: ?>
                    <i class="bi bi-basket"></i>
                  <?php endif; ?>
                </div>
                <div class="cart-info">
                  <p class="cart-name"><?= htmlspecialchars($item['name']) ?></p>
                  <p class="cart-variant">Stock: <?= (int)$item['stock'] ?> available</p>
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="qty-wrap">
                      <button type="button" class="qty-btn" onclick="changeQty(<?= (int)$item['id'] ?>, -1)">−</button>
                      <div class="qty-val" id="qty-<?= (int)$item['id'] ?>"><?= (int)$item['quantity'] ?></div>
                      <button type="button" class="qty-btn" onclick="changeQty(<?= (int)$item['id'] ?>, 1)">+</button>
                    </div>
                    <span class="cart-price" id="price-<?= (int)$item['id'] ?>">₱<?= number_format($item['subtotal'], 2) ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ── Section 3: Voucher ── -->
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

        <!-- ── Section 4: Payment ── -->
        <div class="card-section">
          <div class="section-head">
            <div class="section-num">4</div>
            <p class="section-title mb-0">Payment method</p>
          </div>

          <div class="pay-method active" id="pay-card">
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
                    <input type="text" class="form-control" maxlength="4" placeholder="1234" id="cn1" oninput="fwdCard('cn1','cn2')" />
                    <input type="text" class="form-control" maxlength="4" placeholder="5678" id="cn2" oninput="fwdCard('cn2','cn3')" />
                    <input type="text" class="form-control" maxlength="4" placeholder="9012" id="cn3" oninput="fwdCard('cn3','cn4')" />
                    <input type="text" class="form-control" maxlength="4" placeholder="3456" id="cn4" />
                  </div>
                </div>
                <div class="col-sm-6">
                  <label>Name on card</label>
                  <input type="text" class="form-control" placeholder="e.g. MARIA SANTOS" style="text-transform:uppercase;" />
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
              <div style="display:flex;align-items:center;gap:10px;background:#fff;border-radius:var(--radius-sm);padding:.85rem;border:1px solid var(--border);">
                <i class="bi bi-qr-code-scan" style="font-size:2rem;color:var(--forest);"></i>
                <div>
                  <p style="font-weight:600;font-size:.88rem;margin:0;">Scan QR or enter number</p>
                  <p style="font-size:.78rem;color:var(--text-muted);margin:0;">A payment request will be sent to your GCash app after confirming the order.</p>
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
              <div style="display:flex;align-items:center;gap:10px;background:#fff;border-radius:var(--radius-sm);padding:.85rem;border:1px solid var(--border);">
                <i class="bi bi-arrow-right-circle-fill" style="font-size:1.8rem;color:#00b14f;"></i>
                <div>
                  <p style="font-weight:600;font-size:.88rem;margin:0;">You'll be redirected to Maya</p>
                  <p style="font-size:.78rem;color:var(--text-muted);margin:0;">Complete your payment securely in the Maya app or website.</p>
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
              <p style="font-size:.84rem;color:var(--text-muted);margin:0;">After placing your order, upload your payment receipt from your profile. Orders are confirmed within 1–2 business hours.</p>
            </div>
          </div>

          <div class="pay-method" id="pay-cod">
            <label class="pay-label" onclick="selectPay('pay-cod')">
              <span class="pay-icon" style="color:#d4860a;"><i class="bi bi-cash-coin"></i></span>
              <div>
                <p class="pay-name mb-0">Cash on delivery</p>
                <p class="pay-sub mb-0">Pay when your order arrives</p>
              </div>
              <input class="pay-radio" type="radio" name="payMethod" value="cod" />
            </label>
            <div class="pay-panel">
              <div style="display:flex;align-items:flex-start;gap:10px;background:#fffcf0;border-radius:var(--radius-sm);padding:.85rem;border:1px solid #f0dfb0;">
                <i class="bi bi-exclamation-triangle" style="color:#d4860a;font-size:1.1rem;margin-top:1px;"></i>
                <div>
                  <p style="font-weight:600;font-size:.85rem;margin:0;color:#7a5100;">Please prepare exact change</p>
                  <p style="font-size:.78rem;color:#9a7020;margin:0;">Our rider may not carry change.</p>
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" name="notes" id="orderNotes" value="" />
          <input type="hidden" name="place_order" value="1" />
        </div>

      </div><!-- /col-lg-8 -->

      <div class="col-lg-4">
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
            <p class="secure-note"><i class="bi bi-shield-lock-fill text-success"></i> SSL encrypted · safe checkout</p>
          </div>
        </div>
      </div>

    </div><!-- /row -->
  </form>
</div>

<script>
/* ─── State ─── */
const prices  = <?= json_encode(array_column($items, 'price', 'id')) ?>;
const stocks  = <?= json_encode(array_column($items, 'stock', 'id')) ?>;
const names   = <?= json_encode(array_column($items, 'name', 'id')) ?>;
const qtys    = <?= json_encode(array_column($items, 'quantity', 'id')) ?>;

let shippingFee  = 49;
let activeVoucher = null;

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
  ['ship-std','ship-exp','ship-free'].forEach(i => document.getElementById(i).classList.remove('chosen'));
  document.getElementById(id).classList.add('chosen');
  shippingFee = parseInt(price);
  document.getElementById('sumShipping').textContent = price === '0' ? 'Free' : '₱' + price;
  recalc();
}

/* ─── Voucher ─── */
const validVouchers = {
  'VERDE20':  { type: 'pct',   val: 20,  label: '20% off' },
  'FREESHIP': { type: 'ship',  val: 0,   label: 'Free shipping' },
  'SAVE150':  { type: 'fixed', val: 150, label: '₱150 off' },
  'NEWLEAF':  { type: 'fixed', val: 75,  label: '₱75 off' },
};

function applyVoucher() {
  const code = document.getElementById('voucherInput').value.trim().toUpperCase();
  const msg  = document.getElementById('voucherMsg');
  const hid  = document.getElementById('voucherHidden');
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
    if (activeVoucher.type === 'pct')   disc = Math.round(sub * activeVoucher.val / 100);
    if (activeVoucher.type === 'fixed') disc = activeVoucher.val;
    if (activeVoucher.type === 'ship')  { shipCost = 0; document.getElementById('sumShipping').textContent = 'Free'; }
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
      <span style="color:var(--text-main);">₱${(prices[id]*qtys[id]).toLocaleString('en-PH',{minimumFractionDigits:2})}</span>
    </div>`).join('');
}

/* ─── Submit handler ─── */
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
  const required = ['first_name','last_name','phone','province','city','barangay','street','postal_code'];
  for (const f of required) {
    const el = this.elements[f];
    if (el && !el.value.trim()) {
      e.preventDefault();
      el.focus();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }
  }
  const btn = document.getElementById('placeOrderBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="border-color:#fff;border-right-color:transparent;"></span>Processing…';
});

/* ─── Init ─── */
renderSummaryItems();
recalc();
</script>
</body>
</html>
