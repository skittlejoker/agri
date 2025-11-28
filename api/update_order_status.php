<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Check if user is logged in as farmer
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only farmers can update order status.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $farmer_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    // Support both JSON and form data
    if (!$input) {
        $input = $_POST;
    }

    $order_id = intval($input['order_id'] ?? 0);
    $status = trim($input['status'] ?? '');
    $shipping_status = trim($input['shipping_status'] ?? '');

    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    $valid_shipping_statuses = ['to_ship', 'shipped', 'delivered'];

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    // Check if enhanced columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_status'");
    $hasEnhancedColumns = $stmt->rowCount() > 0;

    // Check if timestamp columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipped_at'");
    $hasShippedAt = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivered_at'");
    $hasDeliveredAt = $stmt->rowCount() > 0;

    // Check if order exists and belongs to this farmer
    // Build SELECT query based on available columns
    $selectFields = "o.id, o.product_id, o.quantity, o.status";
    if ($hasEnhancedColumns) {
        $selectFields .= ", o.shipping_status";
    }
    if ($hasShippedAt || $hasDeliveredAt) {
        // We'll check these individually if needed
    }

    $stmt = $pdo->prepare("
        SELECT $selectFields
        FROM orders o
        WHERE o.id = ? AND o.farmer_id = ?
    ");
    $stmt->execute([$order_id, $farmer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }

    $result = false;
    $currentOrderStatus = $order['status'] ?? '';
    $currentShippingStatus = $order['shipping_status'] ?? null;

    // Handle status updates - map legacy statuses to enhanced columns if needed
    if (!empty($status)) {
        // Map legacy statuses to shipping_status for enhanced orders
        if ($hasEnhancedColumns) {
            // Check if shipping_status is provided separately (preferred method)
            if (!empty($shipping_status) && in_array($shipping_status, $valid_shipping_statuses)) {
                // Both status and shipping_status provided - update both
                $updateFields = ['status = ?', 'shipping_status = ?'];
                $updateValues = [$status, $shipping_status];
                
                // If marking as shipped, set shipped_at timestamp
                if ($shipping_status === 'shipped' && $hasShippedAt && ($currentShippingStatus !== 'shipped')) {
                    $updateFields[] = 'shipped_at = NOW()';
                }
                
                // If marking as delivered, set delivered_at timestamp
                if ($shipping_status === 'delivered' && $hasDeliveredAt && ($currentShippingStatus !== 'delivered')) {
                    $updateFields[] = 'delivered_at = NOW()';
                    // Auto-update payment status for cash on delivery
                    try {
                        $stmt2 = $pdo->prepare("SELECT payment_method, payment_status FROM orders WHERE id = ?");
                        $stmt2->execute([$order_id]);
                        $orderPayment = $stmt2->fetch(PDO::FETCH_ASSOC);
                        if ($orderPayment && isset($orderPayment['payment_method']) && $orderPayment['payment_method'] === 'cash_on_delivery' && isset($orderPayment['payment_status']) && $orderPayment['payment_status'] === 'unpaid') {
                            $updateFields[] = 'payment_status = ?';
                            $updateValues[] = 'paid';
                        }
                    } catch (PDOException $e) {
                        // Payment columns might not exist, ignore
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND farmer_id = ?");
                $updateValues[] = $order_id;
                $updateValues[] = $farmer_id;
                $result = $stmt->execute($updateValues);
            } elseif ($status === 'shipped') {
                // Legacy: Update shipping_status to 'shipped'
                $updateFields = ['shipping_status = ?', 'status = ?'];
                $updateValues = ['shipped', 'confirmed'];

                if ($hasShippedAt && ($currentShippingStatus !== 'shipped')) {
                    $updateFields[] = 'shipped_at = NOW()';
                }

                $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND farmer_id = ?");
                $updateValues[] = $order_id;
                $updateValues[] = $farmer_id;
                $result = $stmt->execute($updateValues);
            } elseif ($status === 'to_ship') {
                // Legacy: Update shipping_status to 'to_ship'
                $updateFields = ['shipping_status = ?', 'status = ?'];
                $updateValues = ['to_ship', 'confirmed'];

                $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND farmer_id = ?");
                $updateValues[] = $order_id;
                $updateValues[] = $farmer_id;
                $result = $stmt->execute($updateValues);
            } elseif ($status === 'completed') {
                // Update both status and shipping_status
                $updateFields = ['status = ?'];
                $updateValues = ['completed'];

                // If shipping_status exists and is not delivered, set it
                if ($currentShippingStatus !== 'delivered') {
                    $updateFields[] = 'shipping_status = ?';
                    $updateValues[] = 'delivered';

                    if ($hasDeliveredAt) {
                        $updateFields[] = 'delivered_at = NOW()';
                    }

                    // Auto-update payment status for cash on delivery
                    try {
                        $stmt2 = $pdo->prepare("SELECT payment_method, payment_status FROM orders WHERE id = ?");
                        $stmt2->execute([$order_id]);
                        $orderPayment = $stmt2->fetch(PDO::FETCH_ASSOC);
                        if ($orderPayment && isset($orderPayment['payment_method']) && $orderPayment['payment_method'] === 'cash_on_delivery' && isset($orderPayment['payment_status']) && $orderPayment['payment_status'] === 'unpaid') {
                            $updateFields[] = 'payment_status = ?';
                            $updateValues[] = 'paid';
                        }
                    } catch (PDOException $e) {
                        // Payment columns might not exist, ignore
                    }
                }

                $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND farmer_id = ?");
                $updateValues[] = $order_id;
                $updateValues[] = $farmer_id;
                $result = $stmt->execute($updateValues);
            } elseif (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
                // For pending, confirmed, cancelled - just update status
                // Also update shipping_status to null or appropriate value
                $updateFields = ['status = ?'];
                $updateValues = [$status];

                if ($status === 'pending' || $status === 'confirmed') {
                    // Don't update shipping_status for pending/confirmed
                } elseif ($status === 'cancelled') {
                    // Cancel shipping if it was in progress - set to empty string instead of NULL
                    if ($currentShippingStatus && in_array($currentShippingStatus, ['to_ship', 'shipped'])) {
                        $updateFields[] = 'shipping_status = ?';
                        $updateValues[] = '';
                    }
                }

                $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND farmer_id = ?");
                $updateValues[] = $order_id;
                $updateValues[] = $farmer_id;
                $result = $stmt->execute($updateValues);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid status value']);
                exit;
            }
        } else {
            // Legacy order - just update status
            // Map "shipped" to "completed" for legacy orders
            if ($status === 'shipped' || $status === 'to_ship') {
                $status = 'completed'; // Map shipped/to_ship to completed for legacy orders
            }
            
            if (!in_array($status, $valid_statuses)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status. Must be one of: ' . implode(', ', $valid_statuses)]);
                exit;
            }

            // If cancelling an order, restore stock
            if ($status === 'cancelled' && $currentOrderStatus !== 'cancelled') {
                // Check if stock column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
                $hasStockColumn = $stmt->rowCount() > 0;

                if ($hasStockColumn) {
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$order['quantity'], $order['product_id']]);
                }
            }

            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND farmer_id = ?");
            $result = $stmt->execute([$status, $order_id, $farmer_id]);
        }
    } elseif ($hasEnhancedColumns && !empty($shipping_status) && in_array($shipping_status, $valid_shipping_statuses)) {
        // Direct shipping_status update (if provided separately)
        $updateFields = ['shipping_status = ?'];
        $updateValues = [$shipping_status];

        // If marking as shipped, set shipped_at timestamp
        if ($shipping_status === 'shipped' && $hasShippedAt && ($currentShippingStatus !== 'shipped')) {
            $updateFields[] = 'shipped_at = NOW()';
        }

        // If marking as delivered, set delivered_at timestamp and update payment if COD
        if ($shipping_status === 'delivered' && $hasDeliveredAt && ($currentShippingStatus !== 'delivered')) {
            $updateFields[] = 'delivered_at = NOW()';
            // Auto-update payment status for cash on delivery
            try {
                $stmt2 = $pdo->prepare("SELECT payment_method, payment_status FROM orders WHERE id = ?");
                $stmt2->execute([$order_id]);
                $orderPayment = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($orderPayment && isset($orderPayment['payment_method']) && $orderPayment['payment_method'] === 'cash_on_delivery' && isset($orderPayment['payment_status']) && $orderPayment['payment_status'] === 'unpaid') {
                    $updateFields[] = 'payment_status = ?';
                    $updateValues[] = 'paid';
                }
            } catch (PDOException $e) {
                // Payment columns might not exist, ignore
            }
        }

        $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND farmer_id = ?");
        $updateValues[] = $order_id;
        $updateValues[] = $farmer_id;
        $result = $stmt->execute($updateValues);
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
} catch (PDOException $e) {
    error_log("Database error in update_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in update_order_status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
