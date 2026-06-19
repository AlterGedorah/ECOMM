<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';

class CheckoutController
{
    private $order;
    private $product;
    private $cart;
    private $user;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->order   = new Order();
        $this->product = new Product();
        $this->cart    = new Cart();
        $this->user    = new User();
    }

    // 
    //  Prepare the data required to render the checkout page:
    //   - current cart items
    //   - subtotal
    //   - whether the user is logged in
    // 
    public function prepareCheckoutPage(): array
    {
        $items   = $this->cart->getItems();
        $details = [];
        $subtotal = 0.0;

        foreach ($items as $id => $qty) {
            if ($this->product->read($id)) {
                $lineTotal = (float)$this->product->price * (int)$qty;
                $subtotal += $lineTotal;
                $details[] = [
                    'id'        => (int)$this->product->id,
                    'name'      => $this->product->name,
                    'price'     => (float)$this->product->price,
                    'quantity'  => (int)$qty,
                    'subtotal'  => $lineTotal,
                    'image'     => $this->product->image_url ?: 'placeholder.jpg',
                    'stock'     => (int)$this->product->quantity,
                ];
            }
        }

        return [
            'isLoggedIn' => $this->isLoggedIn(),
            'user'       => $this->currentUser(),
            'items'      => $details,
            'subtotal'   => $subtotal,
            'isEmpty'    => empty($details),
        ];
    }

    /**
     * Handle the "place order" form submission.
     */
    public function placeOrder(): array
    {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'error' => 'You must be logged in to place an order.'];
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Invalid request.'];
        }

        $userId = (int)$_SESSION['user_id'];

        // ── 1. Validate address ──────────────────────────────────────────
        $addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
        
        if ($addressId === 0) {
            $required = ['first_name','last_name','phone','street','barangay','city','province','postal_code'];
            foreach ($required as $field) {
                if (empty(trim($_POST[$field] ?? ''))) {
                    return ['success' => false, 'error' => "Address field '{$field}' is required."];
                }
            }
        }

        // ADDED: Capture first_name, last_name, and phone from the manual input form
        $address = [
            'address_id'  => $addressId, 
            'first_name'  => trim($_POST['first_name'] ?? ''),
            'last_name'   => trim($_POST['last_name']  ?? ''),
            'phone'       => trim($_POST['phone']      ?? ''),
            'street'      => trim($_POST['street']      ?? ''),
            'barangay'    => trim($_POST['barangay']    ?? ''),
            'city'        => trim($_POST['city']        ?? ''),
            'province'    => trim($_POST['province']    ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'landmark'    => trim($_POST['landmark']    ?? ''),
        ];

        // ── 2. Validate cart ─────────────────────────────────────────────
        $cartItems = $this->cart->getItems();
        if (empty($cartItems)) {
            return ['success' => false, 'error' => 'Your cart is empty.'];
        }

        $productLookup = [];
        foreach ($cartItems as $pid => $qty) {
            if (!$this->product->read($pid)) {
                return ['success' => false, 'error' => "Product #{$pid} not found."];
            }
            if ($this->product->quantity < $qty) {
                return [
                    'success' => false,
                    'error'   => "Only {$this->product->quantity} of \"{$this->product->name}\" available.",
                ];
            }
            $productLookup[$pid] = [
                'id'     => $this->product->id,
                'name'   => $this->product->name,
                'price'  => $this->product->price,
            ];
        }

        // ── 3. Compute shipping / discount ───────────────────────────────
        $shippingMap = ['standard' => 49, 'express' => 99, 'free' => 0];
        $shippingKey = $_POST['shipping'] ?? 'standard';
        $shippingFee = $shippingMap[$shippingKey] ?? 49;

        $discount = 0.0;
        $voucherCode = strtoupper(trim($_POST['voucher'] ?? ''));
        $voucherMap = [
            'VERDE20'  => ['type' => 'pct',   'val' => 20],
            'SAVE150'  => ['type' => 'fixed', 'val' => 150],
            'NEWLEAF'  => ['type' => 'fixed', 'val' => 75],
        ];
        $subtotal = 0.0;
        foreach ($cartItems as $pid => $qty) {
            $subtotal += (float)$productLookup[$pid]['price'] * (int)$qty;
        }
        if (isset($voucherMap[$voucherCode])) {
            $v = $voucherMap[$voucherCode];
            $discount = $v['type'] === 'pct'
                ? round($subtotal * $v['val'] / 100, 2)
                : (float)$v['val'];
        }
        if ($voucherCode === 'FREESHIP') {
            $shippingFee = 0;
        }

        $paymentMethod = $_POST['payMethod'] ?? 'cod';

        // ── 3b. Handle GCash payment proof upload ───────────────────────
        // For GCash, the customer must attach a screenshot/receipt of
        // their transfer. We validate the file (type + size) and store it
        // under /uploads/payment_proofs/ so the admin can review it
        // before marking the order as paid.
        $paymentProofPath = null;
        if ($paymentMethod === 'gcash') {
            $uploadResult = $this->handleGcashProofUpload();
            if (!$uploadResult['success']) {
                return ['success' => false, 'error' => $uploadResult['error']];
            }
            $paymentProofPath = $uploadResult['path'];
        }

        $payment = [
            'method'        => $paymentMethod,
            'shipping_fee'  => $shippingFee,
            'discount'      => $discount,
            'notes'         => trim($_POST['notes'] ?? ''),
            'payment_proof' => $paymentProofPath,
        ];

        // ── 4. Persist ───────────────────────────────────────────────────
        $result = $this->order->placeOrder($userId, $cartItems, $productLookup, $address, $payment);
        if ($result['success']) {
            $this->cart->clear();
            $_SESSION['last_order'] = [
                'order_id'     => $result['order_id'],
                'order_number' => $result['order_number'],
                'total_amount' => $result['total_amount'],
            ];
        }
        return $result;
    }

    /**
     * Build a one-click "Buy Now" payload (single product) and store it in the session.
     * Returns true on success.
     */
    public function buyNow(int $productId, int $quantity): array
    {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'error' => 'Please log in to buy now.'];
        }
        if ($quantity < 1) {
            return ['success' => false, 'error' => 'Invalid quantity.'];
        }
        if (!$this->product->read($productId)) {
            return ['success' => false, 'error' => 'Product not found.'];
        }
        if ($this->product->quantity < $quantity) {
            return [
                'success' => false,
                'error'   => "Only {$this->product->quantity} of \"{$this->product->name}\" available.",
            ];
        }

        // Replace current cart with the single Buy-Now item
        $this->cart->clear();
        $this->cart->add($productId, $quantity);

        $_SESSION['buy_now'] = [
            'product_id' => $productId,
            'quantity'   => $quantity,
        ];
        return ['success' => true];
    }

    /* ── Auth helpers ───────────────────────────────────────────────── */

    /**
     * Validate and store an uploaded GCash payment-proof image.
     *
     * Rules:
     *   - File MUST be present (else returns an error explaining why).
     *   - Max size: 5 MB.
     *   - MIME type must be one of: image/png, image/jpeg, image/webp,
     *     image/gif (re-checked server-side; the client-side accept
     *     attribute is only a hint).
     *   - File is renamed to a timestamp + random hex to avoid collisions
     *     and to prevent path traversal (we strip the original filename).
     *   - Stored under /uploads/payment_proofs/ which is auto-created.
     *
     * @return array { success:bool, path?:string, error?:string }
     */
    private function handleGcashProofUpload(): array
    {
        if (!isset($_FILES['payment_proof']) || (int)($_FILES['payment_proof']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'Please attach a screenshot of your GCash payment before placing the order.'];
        }

        $file = $_FILES['payment_proof'];

        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $phpErrors = [
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server limit.',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form limit.',
                UPLOAD_ERR_PARTIAL    => 'The upload was interrupted; please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
            ];
            $msg = $phpErrors[$file['error']] ?? 'Upload failed (code ' . (int)$file['error'] . ').';
            return ['success' => false, 'error' => $msg];
        }

        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if ((int)$file['size'] > $maxBytes) {
            return ['success' => false, 'error' => 'Payment proof is too large. Maximum size is 5 MB.'];
        }

        $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }
        if (!in_array($mime, $allowed, true)) {
            return ['success' => false, 'error' => 'Payment proof must be a PNG, JPG, WEBP, or GIF image.'];
        }

        // Build a destination folder next to /uploads.
        $projectRoot = realpath(__DIR__ . '/..');
        if ($projectRoot === false) {
            return ['success' => false, 'error' => 'Could not resolve project root.'];
        }
        $destDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payment_proofs';
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            return ['success' => false, 'error' => 'Could not create the upload directory on the server.'];
        }

        $extMap = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext = $extMap[$mime] ?? 'bin';
        $safeName = 'gcash_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destPath = $destDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Could not save the uploaded file. Please try again.'];
        }

        // Return a web-relative path that the admin and order pages can use.
        return [
            'success' => true,
            'path'    => 'uploads/payment_proofs/' . $safeName,
        ];
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function currentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id'          => (int)$_SESSION['user_id'],
            'name'        => $_SESSION['user_name']  ?? '',
            'first_name'  => $_SESSION['user_first_name'] ?? '',
            'middle_name' => $_SESSION['user_middle_name'] ?? '',
            'last_name'   => $_SESSION['user_last_name'] ?? '',
            'email'       => $_SESSION['user_email'] ?? '',
            'phone_num'   => $_SESSION['user_phone_num'] ?? null,
            'role'        => $_SESSION['user_role']  ?? 'user',
        ];
    }
}
?>