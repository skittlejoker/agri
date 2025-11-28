<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];

    // Check if orders table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    $ordersTableExists = $stmt->rowCount() > 0;

    if (!$ordersTableExists) {
        echo json_encode([
            'success' => true,
            'orders' => [],
            'total' => 0,
            'message' => 'Orders table does not exist yet. No orders have been placed.'
        ]);
        exit;
    }

    // Check if enhanced columns exist (check multiple columns to be sure)
    $hasEnhancedColumns = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
        $hasPaymentMethod = $stmt->rowCount() > 0;

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_status'");
        $hasShippingStatus = $stmt->rowCount() > 0;

        // Both must exist to use enhanced features
        $hasEnhancedColumns = $hasPaymentMethod && $hasShippingStatus;
    } catch (Exception $e) {
        // If there's any error checking columns, assume enhanced features don't exist
        $hasEnhancedColumns = false;
    }

    // Get orders based on user type
    $rows = [];
    try {
        if ($user_type === 'buyer') {
            // Get orders for buyer
            if ($hasEnhancedColumns) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT o.id, o.buyer_id, o.farmer_id, o.product_id, o.quantity, 
                               o.unit_price, o.total_price, o.status, o.created_at, o.updated_at,
                               o.payment_method, o.payment_status, o.shipping_status,
                               o.delivery_address, o.delivery_distance, o.estimated_delivery_time,
                               o.shipped_at, o.delivered_at, o.review_rating, o.review_comment,
                               p.name AS product_name, p.image_url,
                               u.full_name AS farmer_name, u.username AS farmer_username
                        FROM orders o
                        JOIN products p ON p.id = o.product_id
                        JOIN users u ON u.id = o.farmer_id
                        WHERE o.buyer_id = ?
                        ORDER BY o.created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // If enhanced query fails (columns missing), fall back to basic query
                    error_log("Enhanced query failed, using basic query: " . $e->getMessage());
                    $hasEnhancedColumns = false;
                    $stmt = $pdo->prepare("
                        SELECT o.id, o.buyer_id, o.farmer_id, o.product_id, o.quantity, 
                               o.unit_price, o.total_price, o.status, o.created_at, o.updated_at,
                               p.name AS product_name, p.image_url,
                               u.full_name AS farmer_name, u.username AS farmer_username
                        FROM orders o
                        JOIN products p ON p.id = o.product_id
                        JOIN users u ON u.id = o.farmer_id
                        WHERE o.buyer_id = ?
                        ORDER BY o.created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $stmt = $pdo->prepare("
                    SELECT o.id, o.buyer_id, o.farmer_id, o.product_id, o.quantity, 
                           o.unit_price, o.total_price, o.status, o.created_at, o.updated_at,
                           p.name AS product_name, p.image_url,
                           u.full_name AS farmer_name, u.username AS farmer_username
                    FROM orders o
                    JOIN products p ON p.id = o.product_id
                    JOIN users u ON u.id = o.farmer_id
                    WHERE o.buyer_id = ?
                    ORDER BY o.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else if ($user_type === 'farmer') {
            // Get orders for farmer
            if ($hasEnhancedColumns) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT o.id, o.buyer_id, o.farmer_id, o.product_id, o.quantity, 
                               o.unit_price, o.total_price, o.status, o.created_at, o.updated_at,
                               o.payment_method, o.payment_status, o.shipping_status,
                               o.delivery_address, o.delivery_distance, o.estimated_delivery_time,
                               o.shipped_at, o.delivered_at, o.review_rating, o.review_comment,
                               p.name AS product_name, p.image_url,
                               u.full_name AS buyer_name, u.username AS buyer_username
                        FROM orders o
                        JOIN products p ON p.id = o.product_id
                        JOIN users u ON u.id = o.buyer_id
                        WHERE o.farmer_id = ?
                        ORDER BY o.created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // If enhanced query fails (columns missing), fall back to basic query
                    error_log("Enhanced query failed, using basic query: " . $e->getMessage());
                    $hasEnhancedColumns = false;
                    $stmt = $pdo->prepare("
                        SELECT o.id, o.buyer_id, o.farmer_id, o.product_id, o.quantity, 
                               o.unit_price, o.total_price, o.status, o.created_at, o.updated_at,
                               p.name AS product_name, p.image_url,
                               u.full_name AS buyer_name, u.username AS buyer_username
                        FROM orders o
                        JOIN products p ON p.id = o.product_id
                        JOIN users u ON u.id = o.buyer_id
                        WHERE o.farmer_id = ?
                        ORDER BY o.created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $stmt = $pdo->prepare("
                    SELECT o.id, o.buyer_id, o.farmer_id, o.product_id, o.quantity, 
                           o.unit_price, o.total_price, o.status, o.created_at, o.updated_at,
                           p.name AS product_name, p.image_url,
                           u.full_name AS buyer_name, u.username AS buyer_username
                    FROM orders o
                    JOIN products p ON p.id = o.product_id
                    JOIN users u ON u.id = o.buyer_id
                    WHERE o.farmer_id = ?
                    ORDER BY o.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user type']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Query execution error in get_orders.php: " . $e->getMessage());
        throw $e; // Re-throw to be caught by outer catch block
    }

    // Ensure $rows is an array
    if ($rows === false || !is_array($rows)) {
        $rows = [];
    }

    $orders = array_map(function ($row) use ($user_type, $hasEnhancedColumns) {
        $order = [
            'id' => (int)$row['id'],
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['product_name'],
            'product_image' => $row['image_url'] ?? null,
            'quantity' => (int)$row['quantity'],
            'unit_price' => (float)$row['unit_price'],
            'total_price' => (float)$row['total_price'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'] ?? null,
            'counterparty' => $user_type === 'buyer'
                ? [
                    'id' => (int)$row['farmer_id'],
                    'name' => $row['farmer_name'],
                    'username' => $row['farmer_username'],
                    'type' => 'farmer'
                ]
                : [
                    'id' => (int)$row['buyer_id'],
                    'name' => $row['buyer_name'],
                    'username' => $row['buyer_username'],
                    'type' => 'buyer'
                ]
        ];

        // Add enhanced fields if available
        if ($hasEnhancedColumns) {
            // Only add fields if they exist in the row (safe access)
            $order['payment_method'] = isset($row['payment_method']) ? $row['payment_method'] : 'cash_on_delivery';
            $order['payment_status'] = isset($row['payment_status']) ? $row['payment_status'] : 'unpaid';
            $order['shipping_status'] = isset($row['shipping_status']) ? $row['shipping_status'] : 'to_ship';
            $order['delivery_address'] = isset($row['delivery_address']) ? $row['delivery_address'] : '';
            $order['delivery_distance'] = isset($row['delivery_distance']) ? (float)$row['delivery_distance'] : 0;
            $order['estimated_delivery_time'] = isset($row['estimated_delivery_time']) ? (int)$row['estimated_delivery_time'] : 0;
            $order['shipped_at'] = isset($row['shipped_at']) ? $row['shipped_at'] : null;
            $order['delivered_at'] = isset($row['delivered_at']) ? $row['delivered_at'] : null;
            $order['review_rating'] = isset($row['review_rating']) && $row['review_rating'] !== null ? (int)$row['review_rating'] : null;
            $order['review_comment'] = isset($row['review_comment']) ? $row['review_comment'] : null;
        }

        return $order;
    }, $rows ?: []);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => count($orders),
        'user_type' => $user_type
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_orders.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_orders.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
