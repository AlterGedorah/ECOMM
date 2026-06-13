<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Checkout — Verdana Market</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
<style>
  :root {
    --forest:   #2F4A3A;
    --forest-dk:#1E3028;
    --sage:     #4D7A5E;
    --mint:     #A8C5B0;
    --cream:    #F5F2EA;
    --cream-dk: #EAE6DC;
    --off-white:#FAFAF7;
    --text-main:#1C2B22;
    --text-muted:#6B7E72;
    --accent:   #6BAA7A;
    --border:   #D6DDD8;
    --danger:   #C94040;
    --radius-sm:6px;
    --radius:   10px;
    --radius-lg:14px;
  }

  *, *::before, *::after { box-sizing: border-box; }

  body {
    background: var(--cream);
    color: var(--text-main);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    font-size: 15px;
    line-height: 1.6;
    min-height: 100vh;
  }

  /* ── Top Nav ── */
  .top-nav {
    background: var(--forest-dk);
    padding: 0.75rem 0;
    position: sticky;
    top: 0;
    z-index: 100;
  }
  .top-nav .brand {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.15rem; font-weight: 600; color: #fff; text-decoration: none;
  }
  .top-nav .brand .leaf-icon {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--accent);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1rem;
  }
  .top-nav .steps {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.78rem; color: var(--mint);
  }
  .top-nav .steps .step { display: flex; align-items: center; gap: 5px; }
  .top-nav .steps .step.active { color: #fff; }
  .top-nav .steps .step .dot {
    width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid currentColor;
    display: flex; align-items: center; justify-content: center; font-size: 0.72rem;
  }
  .top-nav .steps .step.active .dot {
    background: var(--accent); border-color: var(--accent); color: #fff;
  }
  .top-nav .steps .divider { width: 28px; height: 1px; background: var(--sage); }

  /* ── Main wrapper ── */
  .checkout-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem 4rem;
  }
  .page-title { font-size: 1.5rem; font-weight: 700; color: var(--forest-dk); margin-bottom: 0.15rem; }
  .page-sub { font-size: 0.85rem; color: var(--text-muted); }

  /* ── Card / Section ── */
  .card-section {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.25rem;
  }
  .section-head {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--cream-dk);
  }
  .section-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--forest); color: #fff;
    font-size: 0.78rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .section-title { font-size: 1rem; font-weight: 600; color: var(--forest-dk); margin: 0; }

  /* ── Form elements ── */
  label { font-size: 0.82rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 5px; display: block; }
  .form-control, .form-select {
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    color: var(--text-main);
    background: var(--off-white);
    padding: 0.55rem 0.85rem;
    transition: border-color 0.15s;
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--sage);
    box-shadow: 0 0 0 3px rgba(77,122,94,0.15);
    background: #fff;
    outline: none;
  }
  .form-control::placeholder { color: #aab7ae; }

  /* ── Btn primary ── */
  .btn-primary-forest {
    background: var(--forest);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    padding: 0.6rem 1.4rem;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
  }
  .btn-primary-forest:hover { background: var(--sage); }

  /* ── Cart items ── */
  .cart-item {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid var(--cream-dk);
  }
  .cart-item:last-child { border-bottom: none; padding-bottom: 0; }
  .cart-thumb {
    width: 72px; height: 72px; border-radius: var(--radius-sm);
    object-fit: cover; flex-shrink: 0;
    background: var(--cream-dk);
    display: flex; align-items: center; justify-content: center;
    color: var(--mint); font-size: 1.6rem;
  }
  .cart-info { flex: 1; min-width: 0; }
  .cart-name { font-size: 0.88rem; font-weight: 600; color: var(--text-main); margin-bottom: 2px; }
  .cart-variant { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 8px; }
  .cart-price { font-size: 0.95rem; font-weight: 700; color: var(--forest); }

  /* Quantity stepper */
  .qty-wrap { display: flex; align-items: center; gap: 0; border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; width: fit-content; }
  .qty-btn {
    width: 30px; height: 30px; border: none; background: var(--cream-dk); color: var(--text-main);
    cursor: pointer; font-size: 1rem; font-weight: 600;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.12s;
    flex-shrink: 0;
  }
  .qty-btn:hover { background: var(--mint); }
  .qty-val { width: 36px; text-align: center; font-size: 0.88rem; font-weight: 600; border: none; background: #fff; padding: 0; line-height: 30px; color: var(--text-main); pointer-events: none; }

  /* ── Voucher ── */
  .voucher-card {
    background: var(--cream); border: 1px dashed var(--mint);
    border-radius: var(--radius-sm); padding: 0.75rem 1rem;
    cursor: pointer; transition: border-color 0.15s, background 0.15s;
    display: flex; align-items: center; justify-content: space-between;
  }
  .voucher-card:hover { border-color: var(--sage); background: #fff; }
  .voucher-card.selected { border-color: var(--sage); background: #fff; }
  .voucher-card.selected .v-check { display: block !important; }
  .v-code { font-size: 0.82rem; font-weight: 700; color: var(--forest); font-family: monospace; letter-spacing: 0.05em; }
  .v-desc { font-size: 0.78rem; color: var(--text-muted); }
  .v-badge { background: var(--forest); color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; font-weight: 600; }
  .v-check { display: none; color: var(--accent); font-size: 1.1rem; }

  /* ── Payment methods ── */
  .pay-method {
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    margin-bottom: 0.6rem;
    overflow: hidden;
    transition: border-color 0.15s;
  }
  .pay-method.active { border-color: var(--sage); }
  .pay-label {
    display: flex; align-items: center; gap: 12px;
    padding: 0.85rem 1rem; cursor: pointer;
    background: var(--off-white);
  }
  .pay-method.active .pay-label { background: #fff; }
  .pay-icon { font-size: 1.25rem; width: 28px; text-align: center; flex-shrink: 0; }
  .pay-name { font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
  .pay-sub { font-size: 0.75rem; color: var(--text-muted); }
  .pay-radio { margin-left: auto; accent-color: var(--forest); width: 17px; height: 17px; }
  .pay-panel {
    display: none;
    padding: 1rem;
    background: var(--cream);
    border-top: 1px solid var(--border);
    animation: slideDown 0.18s ease;
  }
  .pay-method.active .pay-panel { display: block; }
  @keyframes slideDown { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

  /* Card number input group */
  .card-num-group { display: flex; gap: 6px; }
  .card-num-group input { width: 100%; text-align: center; letter-spacing: 0.2em; font-family: monospace; }

  /* ── Sidebar / Order summary ── */
  .summary-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    position: sticky;
    top: 76px;
  }
  .summary-header {
    background: var(--forest);
    color: #fff;
    padding: 1rem 1.25rem;
    font-size: 0.95rem;
    font-weight: 600;
  }
  .summary-body { padding: 1.25rem; }
  .sum-row {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.88rem; color: var(--text-muted);
    padding: 0.35rem 0;
  }
  .sum-row.total {
    font-size: 1.05rem; font-weight: 700;
    color: var(--text-main);
    border-top: 1.5px solid var(--cream-dk);
    margin-top: 0.5rem;
    padding-top: 0.75rem;
  }
  .sum-row .disc { color: var(--danger); font-weight: 600; }
  .place-btn {
    width: 100%;
    background: var(--forest);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    padding: 0.85rem;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: 0.02em;
    margin-top: 1rem;
    transition: background 0.15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .place-btn:hover { background: var(--sage); }
  .secure-note {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    font-size: 0.75rem; color: var(--text-muted); margin-top: 0.65rem;
  }

  /* ── Misc ── */
  .badge-first {
    background: var(--cream-dk); color: var(--forest);
    font-size: 0.72rem; font-weight: 700;
    padding: 2px 8px; border-radius: 20px;
    border: 1px solid var(--mint);
  }
  .text-accent { color: var(--accent); }
  .text-forest { color: var(--forest); }
  .divider { height: 1px; background: var(--cream-dk); margin: 0.75rem 0; }
  .input-icon-wrap { position: relative; }
  .input-icon-wrap .bi { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--mint); }
  .input-icon-wrap input { padding-left: 2rem; }

  .apply-row { display: flex; gap: 8px; }
  .apply-row input { flex: 1; }
  .apply-row .btn-apply {
    background: var(--forest); color: #fff; border: none;
    border-radius: var(--radius-sm); padding: 0 1rem;
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
    white-space: nowrap; transition: background 0.12s;
  }
  .apply-row .btn-apply:hover { background: var(--sage); }

  .vouchers-toggle {
    background: none; border: none;
    color: var(--sage); font-size: 0.82rem; font-weight: 600;
    padding: 0; cursor: pointer; display: flex; align-items: center; gap: 4px;
    margin-top: 0.5rem;
  }
  #voucherList { display: none; }
  #voucherList.open { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 0.75rem; }

  @media (max-width: 768px) {
    #voucherList.open { grid-template-columns: 1fr; }
    .summary-card { position: static; margin-top: 1.25rem; }
    .card-num-group { flex-wrap: wrap; }
  }
  @media (max-width: 480px) {
    .top-nav .steps { display: none; }
    .checkout-wrap { padding: 1rem 0.75rem 3rem; }
  }

  /* Address "first time" state */
  .first-time-state {
    border: 2px dashed var(--mint);
    border-radius: var(--radius);
    padding: 1.5rem;
    text-align: center;
    background: var(--cream);
    margin-bottom: 1rem;
  }
  .first-time-state .fti { font-size: 2rem; color: var(--mint); margin-bottom: 0.5rem; }
  .first-time-state h6 { font-weight: 700; color: var(--forest); margin-bottom: 0.25rem; }
  .first-time-state p { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.75rem; }
  #addressForm { display: none; }
  #addressForm.visible { display: block; }
  #firstTimeState.hidden { display: none; }

  .total-items-badge {
    background: var(--forest);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    border-radius: 20px;
    padding: 1px 8px;
    margin-left: 8px;
  }

  /* Shipping option pills */
  .ship-option {
    display: flex; align-items: center; gap: 10px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 0.65rem 0.9rem;
    cursor: pointer;
    background: var(--off-white);
    transition: border-color 0.15s;
    margin-bottom: 0.5rem;
  }
  .ship-option input[type=radio] { accent-color: var(--forest); }
  .ship-option.chosen { border-color: var(--sage); background: #fff; }
  .ship-name { font-size: 0.85rem; font-weight: 600; color: var(--text-main); }
  .ship-sub { font-size: 0.75rem; color: var(--text-muted); }
  .ship-price { margin-left: auto; font-size: 0.88rem; font-weight: 700; color: var(--forest); }
</style>
</head>
<body>

<!-- ── Top navigation bar ── -->
<nav class="top-nav">
  <div class="container-fluid px-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <a href="index.php"><button class="btn text-white" style="background-color: 1E3028;">Back</button></a>

    <div class="d-flex align-items-center gap-2" style="color:#fff;font-size:.85rem;">
      <i class="bi bi-shield-lock text-success"></i>
      <span class="d-none d-md-inline" style="color:var(--mint)">Secure checkout</span>
    </div>
  </div>
</nav>

<!-- ── Main checkout container ── -->
<div class="checkout-wrap">
  <div class="mb-3">
    <h1 class="page-title">Checkout</h1>
    <p class="page-sub">Review your order and complete your purchase securely.</p>
  </div>

  <div class="row g-3">

    <!-- ════════════════════════════════════════════
         LEFT COLUMN — Address · Cart · Voucher · Payment
    ════════════════════════════════════════════ -->
    <div class="col-lg-8">

      <!-- ── Section 1: Shipping Address ── -->
      <div class="card-section">
        <div class="section-head">
          <div class="section-num">1</div>
          <div>
            <p class="section-title mb-0">Shipping address <span class="badge-first ms-1">First-time setup</span></p>
          </div>
          <button class="btn-primary-forest ms-auto d-none" id="editAddressBtn" style="padding:.35rem .9rem;font-size:.8rem;" onclick="toggleAddressForm()">
            <i class="bi bi-pencil me-1"></i>Edit
          </button>
        </div>

        <!-- First-time empty state -->
        <div class="first-time-state" id="firstTimeState">
          <div class="fti"><i class="bi bi-map"></i></div>
          <h6>No saved address found</h6>
          <p>Looks like this is your first order. Add a shipping address to continue.</p>
          <button class="btn-primary-forest" onclick="showAddressForm()">
            <i class="bi bi-plus-circle me-1"></i>Add new address
          </button>
        </div>

        <!-- Address form (hidden until user clicks "Add") -->
        <div id="addressForm">
          <div class="row g-3">
            <div class="col-sm-6">
              <label>First name</label>
              <input type="text" class="form-control" placeholder="e.g. Maria" />
            </div>
            <div class="col-sm-6">
              <label>Last name</label>
              <input type="text" class="form-control" placeholder="e.g. Santos" />
            </div>
            <div class="col-sm-6">
              <label>Phone number</label>
              <div class="input-icon-wrap">
                <i class="bi bi-phone"></i>
                <input type="tel" class="form-control" placeholder="+63 9XX XXX XXXX" />
              </div>
            </div>
            <div class="col-sm-6">
              <label>Province / Region</label>
              <select class="form-select">
                <option value="">Select province…</option>
                <option>Laguna</option>
                <option>Cavite</option>
                <option>Rizal</option>
                <option>Metro Manila</option>
                <option>Batangas</option>
              </select>
            </div>

            <!-- City dropdown — drives the street/barangay dropdown -->
            <div class="col-sm-6">
              <label>City / Municipality</label>
              <select class="form-select" id="citySelect" onchange="filterStreets()">
                <option value="">Select city…</option>
                <option value="calamba">Calamba</option>
                <option value="sta-rosa">Santa Rosa</option>
                <option value="binan">Biñan</option>
                <option value="makati">Makati</option>
                <option value="quezon-city">Quezon City</option>
              </select>
            </div>

            <!-- Street / Barangay — conditionally populated -->
            <div class="col-sm-6">
              <label>Barangay / Street</label>
              <select class="form-select" id="streetSelect">
                <option value="">Select city first…</option>
              </select>
            </div>

            <div class="col-12">
              <label>Street address / House no.</label>
              <input type="text" class="form-control" placeholder="House no., Building, Street name" />
            </div>
            <div class="col-sm-4">
              <label>ZIP code</label>
              <input type="text" class="form-control" placeholder="e.g. 4027" maxlength="6" />
            </div>
            <div class="col-sm-8">
              <label>Delivery notes (optional)</label>
              <input type="text" class="form-control" placeholder="e.g. Leave at door, gate code #123" />
            </div>
          </div>

          <!-- Shipping method -->
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

          <div class="d-flex justify-content-end mt-3 gap-2">
            <button class="btn-primary-forest" style="background:var(--cream-dk);color:var(--text-main);" onclick="cancelAddressForm()">Cancel</button>
            <button class="btn-primary-forest" onclick="saveAddress()"><i class="bi bi-check2 me-1"></i>Save address</button>
          </div>
        </div>

        <!-- Saved address display (shown after save) -->
        <div id="savedAddressDisplay" style="display:none;">
          <div style="display:flex;align-items:flex-start;gap:12px;background:var(--cream);border-radius:var(--radius-sm);padding:1rem;">
            <i class="bi bi-geo-alt-fill text-forest" style="font-size:1.2rem;margin-top:2px;"></i>
            <div style="flex:1;">
              <p style="margin:0;font-weight:700;font-size:.9rem;" id="savedName">—</p>
              <p style="margin:0;font-size:.82rem;color:var(--text-muted);" id="savedAddr">—</p>
            </div>
            <button class="btn-primary-forest" style="padding:.3rem .8rem;font-size:.78rem;" onclick="toggleAddressForm()">
              <i class="bi bi-pencil me-1"></i>Edit
            </button>
          </div>
        </div>
      </div>

      <!-- ── Section 2: Cart items ── -->
      <div class="card-section">
        <div class="section-head">
          <div class="section-num">2</div>
          <p class="section-title mb-0">Your items <span class="total-items-badge" id="itemCountBadge">3 items</span></p>
        </div>

        <div id="cartItems">
          <!-- Item 1 -->
          <div class="cart-item" data-id="1" data-price="850">
            <div class="cart-thumb"><i class="bi bi-bag-heart"></i></div>
            <div class="cart-info">
              <p class="cart-name">Bamboo Tote Bag — Sage Edition</p>
              <p class="cart-variant">Color: Forest Green &nbsp;·&nbsp; Size: Medium</p>
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="qty-wrap">
                  <button class="qty-btn" aria-label="Decrease quantity" onclick="changeQty(1, -1)">−</button>
                  <div class="qty-val" id="qty-1">1</div>
                  <button class="qty-btn" aria-label="Increase quantity" onclick="changeQty(1, 1)">+</button>
                </div>
                <span class="cart-price" id="price-1">₱850.00</span>
              </div>
            </div>
          </div>

          <!-- Item 2 -->
          <div class="cart-item" data-id="2" data-price="1250">
            <div class="cart-thumb"><i class="bi bi-cup-hot"></i></div>
            <div class="cart-info">
              <p class="cart-name">Ceramic Pour-Over Set — Mist White</p>
              <p class="cart-variant">Variant: With stand &nbsp;·&nbsp; Qty per pack: 1</p>
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="qty-wrap">
                  <button class="qty-btn" aria-label="Decrease quantity" onclick="changeQty(2, -1)">−</button>
                  <div class="qty-val" id="qty-2">2</div>
                  <button class="qty-btn" aria-label="Increase quantity" onclick="changeQty(2, 1)">+</button>
                </div>
                <span class="cart-price" id="price-2">₱2,500.00</span>
              </div>
            </div>
          </div>

          <!-- Item 3 -->
          <div class="cart-item" data-id="3" data-price="390">
            <div class="cart-thumb"><i class="bi bi-flower2"></i></div>
            <div class="cart-info">
              <p class="cart-name">Pressed Wildflower Journal — A5</p>
              <p class="cart-variant">Cover: Linen Olive &nbsp;·&nbsp; Pages: Dotted</p>
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="qty-wrap">
                  <button class="qty-btn" aria-label="Decrease quantity" onclick="changeQty(3, -1)">−</button>
                  <div class="qty-val" id="qty-3">1</div>
                  <button class="qty-btn" aria-label="Increase quantity" onclick="changeQty(3, 1)">+</button>
                </div>
                <span class="cart-price" id="price-3">₱390.00</span>
              </div>
            </div>
          </div>
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
          </div>
          <button class="btn-apply" onclick="applyVoucher()">Apply</button>
        </div>
        <div id="voucherMsg" style="font-size:.8rem;min-height:1.2rem;margin-top:4px;"></div>

        <button class="vouchers-toggle" onclick="toggleVouchers(this)">
          <i class="bi bi-ticket-perforated"></i> Show available vouchers
          <i class="bi bi-chevron-down" id="voucherChevron"></i>
        </button>

        <div id="voucherList">
          <div class="voucher-card" onclick="selectVoucher(this, 'VERDE20', '20% off your order')">
            <div>
              <div class="v-code">VERDE20</div>
              <div class="v-desc">20% off entire order</div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="v-badge">−20%</span>
              <i class="bi bi-check-circle-fill v-check"></i>
            </div>
          </div>
          <div class="voucher-card" onclick="selectVoucher(this, 'FREESHIP', 'Free shipping on this order')">
            <div>
              <div class="v-code">FREESHIP</div>
              <div class="v-desc">Free standard shipping</div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="v-badge">Free</span>
              <i class="bi bi-check-circle-fill v-check"></i>
            </div>
          </div>
          <div class="voucher-card" onclick="selectVoucher(this, 'SAVE150', '₱150 off orders ≥ ₱1,500')">
            <div>
              <div class="v-code">SAVE150</div>
              <div class="v-desc">₱150 off · Min. ₱1,500</div>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
              <span class="v-badge">−₱150</span>
              <i class="bi bi-check-circle-fill v-check"></i>
            </div>
          </div>
          <div class="voucher-card" onclick="selectVoucher(this, 'NEWLEAF', '₱75 off for first-time buyers')">
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

        <!-- Credit / Debit Card -->
        <div class="pay-method active" id="pay-card">
          <div class="pay-label" onclick="selectPay('pay-card')">
            <span class="pay-icon text-forest"><i class="bi bi-credit-card-2-front"></i></span>
            <div>
              <p class="pay-name mb-0">Credit / Debit card</p>
              <p class="pay-sub mb-0">Visa, Mastercard, JCB</p>
            </div>
            <input class="pay-radio" type="radio" name="payMethod" value="card" checked />
          </div>
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
              <div class="col-12">
                <div class="form-check mt-1">
                  <input class="form-check-input" type="checkbox" id="saveCard" style="accent-color:var(--forest);" />
                  <label class="form-check-label" for="saveCard" style="text-transform:none;font-size:.82rem;font-weight:400;color:var(--text-muted);letter-spacing:0;">
                    Save card securely for future purchases
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- GCash -->
        <div class="pay-method" id="pay-gcash">
          <div class="pay-label" onclick="selectPay('pay-gcash')">
            <span class="pay-icon" style="color:#0066ff;"><i class="bi bi-phone-fill"></i></span>
            <div>
              <p class="pay-name mb-0">GCash</p>
              <p class="pay-sub mb-0">Pay via GCash e-wallet</p>
            </div>
            <input class="pay-radio" type="radio" name="payMethod" value="gcash" />
          </div>
          <div class="pay-panel">
            <div style="display:flex;align-items:center;gap:10px;background:#fff;border-radius:var(--radius-sm);padding:.85rem;border:1px solid var(--border);">
              <i class="bi bi-qr-code-scan" style="font-size:2rem;color:var(--forest);"></i>
              <div>
                <p style="font-weight:600;font-size:.88rem;margin:0;">Scan QR or enter number</p>
                <p style="font-size:.78rem;color:var(--text-muted);margin:0;">A payment request will be sent to your GCash app after confirming the order.</p>
              </div>
            </div>
            <div class="mt-2">
              <label>GCash mobile number</label>
              <div class="input-icon-wrap">
                <i class="bi bi-phone"></i>
                <input type="tel" class="form-control" placeholder="09XX XXX XXXX" />
              </div>
            </div>
          </div>
        </div>

        <!-- Maya -->
        <div class="pay-method" id="pay-maya">
          <div class="pay-label" onclick="selectPay('pay-maya')">
            <span class="pay-icon" style="color:#00b14f;"><i class="bi bi-wallet2"></i></span>
            <div>
              <p class="pay-name mb-0">Maya</p>
              <p class="pay-sub mb-0">Maya / PayMaya wallet</p>
            </div>
            <input class="pay-radio" type="radio" name="payMethod" value="maya" />
          </div>
          <div class="pay-panel">
            <div style="display:flex;align-items:center;gap:10px;background:#fff;border-radius:var(--radius-sm);padding:.85rem;border:1px solid var(--border);">
              <i class="bi bi-arrow-right-circle-fill" style="font-size:1.8rem;color:#00b14f;"></i>
              <div>
                <p style="font-weight:600;font-size:.88rem;margin:0;">You'll be redirected to Maya</p>
                <p style="font-size:.78rem;color:var(--text-muted);margin:0;">Complete your payment securely in the Maya app or website. You'll return here once done.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Bank Transfer -->
        <div class="pay-method" id="pay-bank">
          <div class="pay-label" onclick="selectPay('pay-bank')">
            <span class="pay-icon text-forest"><i class="bi bi-bank"></i></span>
            <div>
              <p class="pay-name mb-0">Bank transfer / InstaPay</p>
              <p class="pay-sub mb-0">BDO, BPI, UnionBank & more</p>
            </div>
            <input class="pay-radio" type="radio" name="payMethod" value="bank" />
          </div>
          <div class="pay-panel">
            <table style="width:100%;font-size:.84rem;">
              <tr><td style="color:var(--text-muted);padding:4px 0;width:40%;">Account name</td><td style="font-weight:600;">Verdana Market Corp.</td></tr>
              <tr><td style="color:var(--text-muted);padding:4px 0;">Account number</td><td style="font-weight:600;font-family:monospace;">0012-3456-7890</td></tr>
              <tr><td style="color:var(--text-muted);padding:4px 0;">Bank</td><td style="font-weight:600;">BDO Unibank</td></tr>
            </table>
            <p style="font-size:.78rem;color:var(--text-muted);margin-top:.6rem;margin-bottom:0;"><i class="bi bi-info-circle me-1"></i>Upload your payment receipt on the next step. Orders are confirmed within 1–2 business hours.</p>
          </div>
        </div>

        <!-- Cash on Delivery -->
        <div class="pay-method" id="pay-cod">
          <div class="pay-label" onclick="selectPay('pay-cod')">
            <span class="pay-icon" style="color:#d4860a;"><i class="bi bi-cash-coin"></i></span>
            <div>
              <p class="pay-name mb-0">Cash on delivery</p>
              <p class="pay-sub mb-0">Pay when your order arrives</p>
            </div>
            <input class="pay-radio" type="radio" name="payMethod" value="cod" />
          </div>
          <div class="pay-panel">
            <div style="display:flex;align-items:flex-start;gap:10px;background:#fffcf0;border-radius:var(--radius-sm);padding:.85rem;border:1px solid #f0dfb0;">
              <i class="bi bi-exclamation-triangle" style="color:#d4860a;font-size:1.1rem;margin-top:1px;"></i>
              <div>
                <p style="font-weight:600;font-size:.85rem;margin:0;color:#7a5100;">Please prepare exact change</p>
                <p style="font-size:.78rem;color:#9a7020;margin:0;">Our rider may not carry change. Additional COD fee of <strong>₱25</strong> applies.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /col-lg-8 -->

    <!-- ════════════════════════════════════════════
         RIGHT COLUMN — Order summary (sticky)
    ════════════════════════════════════════════ -->
    <div class="col-lg-4">
      <div class="summary-card">
        <div class="summary-header d-flex align-items-center gap-2">
          <i class="bi bi-receipt"></i> Order summary
        </div>
        <div class="summary-body">
          <!-- Mini item list -->
          <div id="summaryItems" style="margin-bottom:.75rem;"></div>
          <div class="divider"></div>

          <div class="sum-row"><span>Subtotal</span><span id="sumSubtotal">—</span></div>
          <div class="sum-row"><span>Shipping</span><span id="sumShipping">₱49</span></div>
          <div class="sum-row" id="discRow" style="display:none;">
            <span>Discount</span><span class="disc" id="sumDisc">—</span>
          </div>
          <div class="sum-row total">
            <span>Total</span>
            <span id="sumTotal">—</span>
          </div>

          <button class="place-btn" onclick="placeOrder()">
            <i class="bi bi-bag-check-fill"></i> Place order
          </button>
          <p class="secure-note"><i class="bi bi-shield-lock-fill text-success"></i> SSL encrypted · safe checkout</p>
          <div class="divider"></div>
          <div style="display:flex;justify-content:center;gap:12px;font-size:1.4rem;color:var(--mint);">
            <i class="bi bi-credit-card-2-front" title="Visa/Mastercard"></i>
            <i class="bi bi-phone-fill" title="GCash"></i>
            <i class="bi bi-wallet2" title="Maya"></i>
            <i class="bi bi-cash-coin" title="COD"></i>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /row -->
</div><!-- /checkout-wrap -->

<script>
/* ─── Street data keyed by city ─── */
const streets = {
  'calamba': ['Barangay Parian','Barangay Real','Barangay Bucal','Barangay Canlubang','Brgy. Halang','Brgy. Punta','Brgy. Lecheria','Brgy. Majada Out'],
  'sta-rosa': ['Barangay Balibago','Barangay Dila','Barangay Pooc','Barangay Labas','Barangay Tagapo','Barangay Ibaba','Barangay Caingin'],
  'binan': ['Barangay Binan','Barangay Canlalay','Barangay De La Paz','Barangay Ganado','Barangay Langkiwa','Barangay Malaban','Barangay Poblacion'],
  'makati': ['Ayala Avenue','Buendia Avenue','Chino Roces Ave','Brgy. Poblacion','Brgy. Bel-Air','Brgy. Pio Del Pilar','Brgy. San Antonio'],
  'quezon-city': ['Brgy. Batasan Hills','Brgy. Commonwealth','Brgy. Diliman','Brgy. Bagong Lipunan','Katipunan Avenue','EDSA','Quezon Avenue'],
};

function filterStreets() {
  const city = document.getElementById('citySelect').value;
  const sel = document.getElementById('streetSelect');
  sel.innerHTML = '';
  if (!city) { sel.innerHTML = '<option value="">Select city first…</option>'; return; }
  sel.innerHTML = '<option value="">Choose barangay/street…</option>';
  (streets[city] || []).forEach(s => {
    const o = document.createElement('option');
    o.value = s; o.textContent = s;
    sel.appendChild(o);
  });
}

/* ─── Address flow ─── */
function showAddressForm() {
  document.getElementById('firstTimeState').classList.add('hidden');
  document.getElementById('addressForm').classList.add('visible');
}
function cancelAddressForm() {
  document.getElementById('firstTimeState').classList.remove('hidden');
  document.getElementById('addressForm').classList.remove('visible');
}
function saveAddress() {
  const firstName = document.querySelectorAll('#addressForm input[type=text]')[0].value || 'First';
  const lastName = document.querySelectorAll('#addressForm input[type=text]')[1].value || 'Last';
  const city = document.getElementById('citySelect').options[document.getElementById('citySelect').selectedIndex]?.text || 'City';
  const street = document.getElementById('streetSelect').options[document.getElementById('streetSelect').selectedIndex]?.text || 'Street';
  document.getElementById('addressForm').classList.remove('visible');
  document.getElementById('savedAddressDisplay').style.display = 'block';
  document.getElementById('savedName').textContent = (firstName + ' ' + lastName).trim();
  document.getElementById('savedAddr').textContent = street + ', ' + city + ', Philippines';
}
function toggleAddressForm() {
  const form = document.getElementById('addressForm');
  form.classList.toggle('visible');
  document.getElementById('savedAddressDisplay').style.display = form.classList.contains('visible') ? 'none' : 'block';
}

/* ─── Shipping method ─── */
let shippingFee = 49;
function pickShipping(radio, id, price) {
  ['ship-std','ship-exp','ship-free'].forEach(i => document.getElementById(i).classList.remove('chosen'));
  document.getElementById(id).classList.add('chosen');
  shippingFee = parseInt(price);
  document.getElementById('sumShipping').textContent = price === '0' ? 'Free' : '₱' + price;
  recalc();
}

/* ─── Cart quantities ─── */
const prices = { 1: 850, 2: 1250, 3: 390 };
const qtys   = { 1: 1, 2: 2, 3: 1 };

function changeQty(id, delta) {
  qtys[id] = Math.max(1, qtys[id] + delta);
  document.getElementById('qty-' + id).textContent = qtys[id];
  const total = prices[id] * qtys[id];
  document.getElementById('price-' + id).textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2});
  recalc();
  renderSummaryItems();
}

/* ─── Voucher ─── */
const validVouchers = {
  'VERDE20': { type: 'pct', val: 20, label: '20% off' },
  'FREESHIP': { type: 'ship', val: 0, label: 'Free shipping' },
  'SAVE150': { type: 'fixed', val: 150, label: '₱150 off' },
  'NEWLEAF': { type: 'fixed', val: 75, label: '₱75 off' },
};
let activeVoucher = null;

function applyVoucher() {
  const code = document.getElementById('voucherInput').value.trim().toUpperCase();
  const msg = document.getElementById('voucherMsg');
  if (validVouchers[code]) {
    activeVoucher = { code, ...validVouchers[code] };
    msg.innerHTML = '<span style="color:var(--sage);font-weight:600;"><i class="bi bi-check-circle me-1"></i>Applied: ' + activeVoucher.label + '</span>';
    recalc();
  } else {
    msg.innerHTML = '<span style="color:var(--danger);"><i class="bi bi-x-circle me-1"></i>Invalid or expired code.</span>';
  }
}

function toggleVouchers(btn) {
  const list = document.getElementById('voucherList');
  const chev = document.getElementById('voucherChevron');
  list.classList.toggle('open');
  chev.className = list.classList.contains('open') ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
  btn.querySelector('span, i.bi-ticket-perforated') || null;
  btn.childNodes[1].textContent = list.classList.contains('open') ? ' Hide vouchers ' : ' Show available vouchers ';
}

function selectVoucher(el, code, desc) {
  document.querySelectorAll('.voucher-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('voucherInput').value = code;
  applyVoucher();
}

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
  el.value = el.value.replace(/\D/g,'');
  if (el.value.length === 4) document.getElementById(to)?.focus();
}

/* ─── Recalculate totals ─── */
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
  document.getElementById('sumSubtotal').textContent = '₱' + sub.toLocaleString('en-PH', {minimumFractionDigits:2});
  if (disc > 0) {
    document.getElementById('discRow').style.display = '';
    document.getElementById('sumDisc').textContent = '−₱' + disc.toLocaleString('en-PH', {minimumFractionDigits:2});
  } else {
    document.getElementById('discRow').style.display = 'none';
  }
  document.getElementById('sumTotal').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2});
  updateItemCount();
}

function updateItemCount() {
  const total = Object.values(qtys).reduce((a,b)=>a+b, 0);
  document.getElementById('itemCountBadge').textContent = total + (total === 1 ? ' item' : ' items');
}

/* ─── Summary mini list ─── */
const itemNames = { 1: 'Bamboo Tote Bag', 2: 'Ceramic Pour-Over Set', 3: 'Wildflower Journal' };
function renderSummaryItems() {
  const wrap = document.getElementById('summaryItems');
  wrap.innerHTML = Object.keys(qtys).map(id => `
    <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:4px 0;color:var(--text-muted);">
      <span>${itemNames[id]} <span style="color:var(--text-main);font-weight:600;">×${qtys[id]}</span></span>
      <span style="color:var(--text-main);">₱${(prices[id]*qtys[id]).toLocaleString('en-PH',{minimumFractionDigits:2})}</span>
    </div>`).join('');
}

/* ─── Place order ─── */
function placeOrder() {
  const btn = document.querySelector('.place-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="border-color:#fff;border-right-color:transparent;"></span>Processing…';
  setTimeout(() => {
    btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Order placed!';
    btn.style.background = 'var(--sage)';
  }, 1800);
}

/* ─── Init ─── */
renderSummaryItems();
recalc();
</script>
</body>
</html>