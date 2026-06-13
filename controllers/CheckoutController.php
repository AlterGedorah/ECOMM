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

    /**
     * Prepare the data required to render the checkout page:
     *  - current cart items
     *  - subtotal
     *  - whether the user is logged in
     */
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
        $required = ['first_name','last_name','phone','street','barangay','city','province','postal_code'];
        foreach ($required as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                return ['success' => false, 'error' => "Address field '{$field}' is required."];
            }
        }

        $address = [
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

        $payment = [
            'method'       => $_POST['payMethod'] ?? 'cod',
            'shipping_fee' => $shippingFee,
            'discount'     => $discount,
            'notes'        => trim($_POST['notes'] ?? ''),
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
            'id'    => (int)$_SESSION['user_id'],
            'name'  => $_SESSION['user_name']  ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role']  ?? 'user',
        ];
    }
}
?>
