<?php
require_once __DIR__ . '/../models/Order.php';

class OrderController
{
    private $order;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->order = new Order();
    }

    /**
     * Get all orders for the admin dashboard, newest first.
     */
    public function adminIndex(): array
    {
        $database = new Database();
        $conn = $database->getConnection();
        $sql = "
            SELECT o.*,
                   u.first_name, u.last_name, u.email, u.phone_num,
                   a.street_address, a.barangay, a.city, a.region, a.postal_code
            FROM orders o
            LEFT JOIN users u      ON o.customer_id = u.id
            LEFT JOIN addresses a  ON o.shipping_address_id = a.id
            ORDER BY o.created_at DESC
        ";
        $res = $conn->query($sql);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Get a single order with its line items (admin view, no user restriction).
     */
    public function adminShow(int $orderId): ?array
    {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            SELECT o.*,
                   u.first_name, u.last_name, u.email, u.phone_num,
                   a.street_address, a.barangay, a.city, a.region, a.postal_code
            FROM orders o
            LEFT JOIN users u      ON o.customer_id = u.id
            LEFT JOIN addresses a  ON o.shipping_address_id = a.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order) {
            return null;
        }

        $itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $itemStmt->close();
        $order['items'] = $items;

        // Status history
        $histStmt = $conn->prepare("
            SELECT h.*, COALESCE(u.first_name, 'System') AS changed_by_name
            FROM order_status_history h
            LEFT JOIN users u ON h.changed_by = u.id
            WHERE h.order_id = ?
            ORDER BY h.changed_at ASC
        ");
        $histStmt->bind_param("i", $orderId);
        $histStmt->execute();
        $history = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $histStmt->close();
        $order['history'] = $history;

        return $order;
    }

    /**
     * Update the order / payment status of an order, with stock adjustments and a history entry.
     *
     * @param int    $orderId
     * @param string $orderStatus  pending|confirmed|processing|shipped|delivered|cancelled
     * @param string $paymentStatus unpaid|paid|refunded|failed
     * @param string $adminNotes
     * @param int    $changedBy   admin user id
     */
    public function updateStatus(int $orderId, string $orderStatus, string $paymentStatus, string $adminNotes, int $changedBy): array
    {
        $database = new Database();
        $conn = $database->getConnection();
        $conn->begin_transaction();

        try {
            // Lock the order row
            $stmt = $conn->prepare("SELECT order_status, payment_status FROM orders WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception("Order not found.");
            }

            $previousStatus = $row['order_status'];

            // Update the order header
            $upd = $conn->prepare("
                UPDATE orders
                SET order_status   = ?,
                    payment_status = ?,
                    admin_notes    = NULLIF(?, '')
                WHERE id = ?
            ");
            $upd->bind_param("sssi", $orderStatus, $paymentStatus, $adminNotes, $orderId);
            if (!$upd->execute()) {
                throw new Exception("Could not update order: " . $upd->error);
            }
            $upd->close();

            // If the order is being cancelled, restore stock.
            if ($orderStatus === 'cancelled' && $previousStatus !== 'cancelled') {
                $itemsStmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->bind_param("i", $orderId);
                $itemsStmt->execute();
                $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $itemsStmt->close();

                $restock = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                foreach ($items as $it) {
                    $pid = (int)$it['product_id'];
                    $qty = (int)$it['quantity'];
                    if ($pid <= 0 || $qty <= 0) {
                        continue;
                    }
                    $restock->bind_param("ii", $qty, $pid);
                    $restock->execute();
                }
                $restock->close();

                // Auto-refund if it was paid
                if ($row['payment_status'] === 'paid' && $paymentStatus !== 'refunded') {
                    $refund = $conn->prepare("UPDATE orders SET payment_status = 'refunded' WHERE id = ?");
                    $refund->bind_param("i", $orderId);
                    $refund->execute();
                    $refund->close();
                    $paymentStatus = 'refunded';
                }
            }

            // Append history entry if status actually changed
            if ($orderStatus !== $previousStatus) {
                $note = "Status changed from {$previousStatus} to {$orderStatus} by admin";
                $hist = $conn->prepare("
                    INSERT INTO order_status_history (order_id, status, notes, changed_by)
                    VALUES (?, ?, ?, ?)
                ");
                $hist->bind_param("issi", $orderId, $orderStatus, $note, $changedBy);
                $hist->execute();
                $hist->close();
            }

            $conn->commit();
            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
