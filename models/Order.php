<?php
require_once __DIR__ . '/../config/Database.php';

class Order
{
    private $conn;
    private $orders_table = "orders";
    private $items_table = "order_items";
    private $history_table = "order_status_history";
    private $addresses_table = "addresses";

    public $id;
    public $order_number;
    public $customer_id;
    public $shipping_address_id;
    public $billing_address_id;
    public $order_status;
    public $payment_status;
    public $payment_method;
    public $payment_reference;
    public $subtotal;
    public $delivery_fee;
    public $tax_amount;
    public $discount_amount;
    public $total_amount;
    public $customer_notes;
    public $admin_notes;
    public $estimated_delivery_date;
    public $actual_delivery_date;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Generate a unique, human-friendly order number, e.g. R3F-20260613-AB12CD
     */
    public static function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return "R3F-{$date}-{$random}";
    }

    /**
     * Persist a complete order in a single transaction.
     *
     * @param int   $userId
     * @param array $cartItems  Associative array: product_id => quantity
     * @param array $productLookup  ['id' => ['id'=>..,'name'=>..,'price'=>..,'quantity'=>..]]
     * @param array $address    [address_id,first_name,last_name,phone,street,barangay,city,province,postal_code,landmark]
     * @param array $payment    [method, shipping_fee, discount, notes]
     * @return array            ['success'=>bool,'order_id'=>?,'order_number'=>?,'error'=>?]
     */
    public function placeOrder(int $userId, array $cartItems, array $productLookup, array $address, array $payment): array
    {
        $this->conn->begin_transaction();
        try {
            // 1. Insert / reuse shipping address
            $addressId = $this->saveAddress($userId, $address);
            if (!$addressId) {
                throw new Exception("Could not save shipping address.");
            }

            // 2. Compute totals from the provided product snapshot
            $subtotal = 0.0;
            $lineItems = [];
            foreach ($cartItems as $productId => $qty) {
                if (!isset($productLookup[$productId])) {
                    throw new Exception("Product {$productId} no longer exists.");
                }
                $p = $productLookup[$productId];
                $row = [
                    'product_id' => (int) $p['id'],
                    'product_name' => $p['name'],
                    'price_per_unit' => (float) $p['price'],
                    'quantity' => (int) $qty,
                ];
                $row['total_price'] = $row['price_per_unit'] * $row['quantity'];
                $subtotal += $row['total_price'];
                $lineItems[] = $row;
            }

            $shippingFee = (float) ($payment['shipping_fee'] ?? 0);
            $discount = (float) ($payment['discount'] ?? 0);
            $totalAmount = max(0, $subtotal + $shippingFee - $discount);

            // 3. Create order header
            $orderNumber = self::generateOrderNumber();
            $paymentMethod = $this->mapPaymentMethod($payment['method'] ?? 'cod');

            $sql = <<<SQL
INSERT INTO {$this->orders_table}
    (order_number, customer_id, shipping_address_id, billing_address_id,
     order_status, payment_status, payment_method,
     subtotal, delivery_fee, tax_amount, discount_amount, total_amount,
     customer_notes, estimated_delivery_date)
VALUES (?, ?, ?, NULL, 'pending', 'unpaid', ?, ?, ?, ?, ?, ?, ?, ?)
SQL;
            $stmt = $this->conn->prepare($sql);
            $eta = date('Y-m-d', strtotime('+3 business days'));
            // Normalize values into variables so they can be passed by reference to bind_param
            $notes = $payment['notes'] ?? null;
            $taxAmount = 0.0; // no tax calculation for now
            $types = "siisdddddss"; // order_number(s), customer_id(i), shipping_address_id(i), payment_method(s), subtotal(d), delivery_fee(d), tax_amount(d), discount_amount(d), total_amount(d), customer_notes(s), eta(s)
            $stmt->bind_param(
                $types,
                $orderNumber,
                $userId,
                $addressId,
                $paymentMethod,
                $subtotal,
                $shippingFee,
                $taxAmount,
                $discount,
                $totalAmount,
                $notes,
                $eta
            );
            if (!$stmt->execute()) {
                throw new Exception("Could not create order: " . $stmt->error);
            }
            $orderId = $stmt->insert_id;
            $stmt->close();

            // 4. Insert order items + decrement stock
            $itemSql = <<<SQL
INSERT INTO {$this->items_table}
    (order_id, product_id, product_name, quantity, price_per_unit)
VALUES (?, ?, ?, ?, ?)
SQL;
            $itemStmt = $this->conn->prepare($itemSql);
            $stockStmt = $this->conn->prepare("
                UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?
            ");

            foreach ($lineItems as $li) {
                // Bind into variables in the correct order: order_id, product_id, product_name, quantity, price
                $prodId = $li['product_id'];
                $prodName = $li['product_name'];
                $qty = $li['quantity'];
                $ppu = $li['price_per_unit'];
                $itemStmt->bind_param(
                    "iisid",
                    $orderId,
                    $prodId,
                    $prodName,
                    $qty,
                    $ppu
                );
                if (!$itemStmt->execute()) {
                    throw new Exception("Could not save order item: " . $itemStmt->error);
                }

                $stockStmt->bind_param("iii", $qty, $prodId, $qty);
                if (!$stockStmt->execute() || $this->conn->affected_rows === 0) {
                    throw new Exception("Insufficient stock for {$prodName}.");
                }
            }
            $itemStmt->close();
            $stockStmt->close();

            // 5. Log initial status
            $histStmt = $this->conn->prepare("
                INSERT INTO {$this->history_table} (order_id, status, notes, changed_by)
                VALUES (?, 'pending', 'Order placed', ?)
            ");
            $histStmt->bind_param("ii", $orderId, $userId);
            $histStmt->execute();
            $histStmt->close();

            $this->conn->commit();
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Save or reuse a shipping address for the user, and update user profile info.
     */
    private function saveAddress(int $userId, array $a): ?int
    {
        // 1. Reuse existing address if provided
        if (!empty($a['address_id']) && (int)$a['address_id'] > 0) {
            return (int)$a['address_id'];
        }

        // 2. Update the user's Profile (Name and Phone)
        $firstName = $a['first_name'] ?? '';
        $lastName  = $a['last_name'] ?? '';
        $phone     = $a['phone'] ?? '';

        if (!empty($firstName) && !empty($lastName)) {
            // The users.phone_num column is INT(11), so we MUST strip the
            // phone down to digits only (no "+", spaces or dashes) before
            // binding. "+63 917 555 4444" → "639175554444" (still fits INT(11)).
            $phoneDigits = preg_replace('/\D+/', '', (string) $phone);
            if ($phoneDigits === '') {
                $phoneDigits = null;
            }

            $updUser = $this->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_num = ? WHERE id = ?");
            if ($updUser) {
                $updUser->bind_param("sssi", $firstName, $lastName, $phoneDigits, $userId);
                $updUser->execute();
                $updUser->close();

                // Update session variables so the UI updates immediately without needing to relog.
                // Keep the formatted version in the session (for display) but
                // the DB now holds digits only.
                $_SESSION['user_first_name'] = $firstName;
                $_SESSION['user_last_name']  = $lastName;
                $_SESSION['user_phone_num']  = $phoneDigits;
                $_SESSION['user_name']       = trim($firstName . ' ' . $lastName);
            }
        }

        // 3. Save Address to database
        $street   = trim($a['street'] ?? '');
        $barangay = trim($a['barangay'] ?? '');
        $city     = trim($a['city'] ?? '');
        $region   = trim($a['province'] ?? '');
        $postal   = trim($a['postal_code'] ?? '');
        $landmark = $a['landmark'] ?? null;

        // Insert into proper distinct columns (city, region, etc.)
        // Columns being bound: user_id (i), street_address, barangay, city,
        // region, postal_code, landmark (6 s) → type string is "issssss" (7 chars).
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->addresses_table}
                (user_id, address_type, address_label, street_address, barangay, city, region, postal_code, landmark, is_default_shipping)
            VALUES (?, 'shipping', 'Home', ?, ?, ?, ?, ?, ?, 1)
        ");

        if ($stmt) {
            $stmt->bind_param("issssss", $userId, $street, $barangay, $city, $region, $postal, $landmark);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            return $newId;
        } else {
            // Fallback in case your database schema doesn't have 'city' and 'region' columns yet
            $stmtFallback = $this->conn->prepare("
                INSERT INTO {$this->addresses_table}
                    (user_id, address_type, address_label, street_address, barangay, postal_code, landmark, is_default_shipping)
                VALUES (?, 'shipping', 'Home', ?, ?, ?, ?, 1)
            ");
            $fullStreet = trim("{$street}, {$city}, {$region}", ', ');
            $stmtFallback->bind_param("issss", $userId, $fullStreet, $barangay, $postal, $landmark);
            $stmtFallback->execute();
            $newId = $stmtFallback->insert_id;
            $stmtFallback->close();
            return $newId;
        }
    }

    /**
     * Normalize the payment method string into the values accepted by the orders table.
     */
    private function mapPaymentMethod(string $method): string
    {
        $method = strtolower(trim($method));
        $map = [
            'card' => 'credit_card',
            'gcash' => 'gcash',
            'maya' => 'maya',
            'bank' => 'bank_transfer',
            'cod' => 'cash_on_delivery',
        ];
        return $map[$method] ?? 'cash_on_delivery';
    }

    /**
     * Get all orders for a given user, newest first.
     */
    public function getOrdersByUser(int $userId): array
    {
        $stmt = $this->conn->prepare("
            SELECT o.*, a.street_address, a.barangay
            FROM {$this->orders_table} o
            LEFT JOIN {$this->addresses_table} a ON o.shipping_address_id = a.id
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Get full order with line items.
     */
    public function getOrderWithItems(int $orderId, int $userId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT o.*, a.street_address, a.barangay, a.postal_code, a.landmark
            FROM {$this->orders_table} o
            LEFT JOIN {$this->addresses_table} a ON o.shipping_address_id = a.id
            WHERE o.id = ? AND o.customer_id = ?
        ");
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$order) {
            return null;
        }

        $itemStmt = $this->conn->prepare("SELECT * FROM {$this->items_table} WHERE order_id = ?");
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();

        $order['items'] = $items;
        return $order;
    }

    /**
     * Cancel an order owned by the given user.
     */
    public function cancelOrder(int $orderId, int $userId, string $reason = ''): array
    {
        $cancellable = ['pending', 'confirmed', 'processing'];

        $this->conn->begin_transaction();
        try {
            // Lock the row and verify ownership + current state
            $stmt = $this->conn->prepare("
                SELECT order_status, payment_status FROM {$this->orders_table}
                WHERE id = ? AND customer_id = ? FOR UPDATE
            ");
            $stmt->bind_param("ii", $orderId, $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception("Order not found.");
            }
            if (!in_array($row['order_status'], $cancellable, true)) {
                throw new Exception("This order can no longer be cancelled.");
            }

            // Update order header
            $upd = $this->conn->prepare("
                UPDATE {$this->orders_table}
                SET order_status    = 'cancelled',
                    cancelled_at    = CURRENT_TIMESTAMP,
                    cancelled_reason = ?
                WHERE id = ? AND customer_id = ?
            ");
            $upd->bind_param("sii", $reason, $orderId, $userId);
            if (!$upd->execute()) {
                throw new Exception("Could not cancel order: " . $upd->error);
            }
            $upd->close();

            // If the order was already paid, mark the payment as refunded
            // so it shows up in the Return/Refund tab too.
            if ($row['payment_status'] === 'paid') {
                $refund = $this->conn->prepare("
                    UPDATE {$this->orders_table}
                    SET payment_status = 'refunded'
                    WHERE id = ? AND customer_id = ?
                ");
                $refund->bind_param("ii", $orderId, $userId);
                $refund->execute();
                $refund->close();
            }

            // Log the status change
            $note = $reason !== '' ? "Cancelled by customer: {$reason}" : 'Cancelled by customer';
            $hist = $this->conn->prepare("
                INSERT INTO {$this->history_table} (order_id, status, notes, changed_by)
                VALUES (?, 'cancelled', ?, ?)
            ");
            $hist->bind_param("isi", $orderId, $note, $userId);
            $hist->execute();
            $hist->close();

            $this->conn->commit();
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
