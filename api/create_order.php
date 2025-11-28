<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Check if user is logged in as buyer
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only buyers can place orders.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $buyer_id = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);

    // Support both JSON and form data
    if (!$input) {
        $input = $_POST;
    }

    if (!isset($input['cart_items']) || !is_array($input['cart_items']) || empty($input['cart_items'])) {
        echo json_encode(['success' => false, 'message' => 'No items in cart']);
        exit;
    }

    // Validate payment method and delivery address
    $valid_payment_methods = ['ewallet', 'cash_on_delivery', 'gcash', 'bank_transfer'];
    $payment_method = isset($input['payment_method']) && in_array($input['payment_method'], $valid_payment_methods)
        ? $input['payment_method']
        : 'cash_on_delivery';

    $delivery_address = isset($input['delivery_address']) ? trim($input['delivery_address']) : '';
    if (empty($delivery_address)) {
        echo json_encode(['success' => false, 'message' => 'Delivery address is required']);
        exit;
    }

    // Calculate delivery distance (simple approximation based on address length or use a distance service)
    // For now, we'll use a simple formula based on address
    // In production, you'd use a geocoding service or have coordinates stored
    $delivery_distance = 0;
    $estimated_delivery_time = 0;

    // Simple distance calculation - can be replaced with actual geocoding
    // For demo: assume 5-50km based on address complexity, ~30 min per 10km
    if (!empty($delivery_address)) {
        // Simple heuristic: longer addresses might be farther (very simplified)
        $base_distance = 10 + (strlen($delivery_address) % 40); // 10-50km range
        $delivery_distance = round($base_distance, 2);
        // Estimate time: 30 minutes per 10km, minimum 30 minutes
        $estimated_delivery_time = max(30, round($base_distance * 3));
    }

    // Check if orders table exists before starting transaction (DDL statements auto-commit)
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    $ordersTableExists = $stmt->rowCount() > 0;

    if (!$ordersTableExists) {
        // Create orders table if it doesn't exist (must be done BEFORE transaction)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                buyer_id INT NOT NULL,
                farmer_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                INDEX idx_buyer (buyer_id),
                INDEX idx_farmer (farmer_id),
                INDEX idx_product (product_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Begin transaction
    $pdo->beginTransaction();

    $orders_created = [];
    $errors = [];

    foreach ($input['cart_items'] as $item) {
        $product_id = intval($item['product_id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);

        if ($product_id <= 0 || $quantity <= 0) {
            $errors[] = "Invalid product ID or quantity for item";
            continue;
        }

        // Get product details and check stock
        $stmt = $pdo->prepare("
            SELECT p.id, p.farmer_id, p.price, p.stock, p.name, u.full_name AS farmer_name
            FROM products p
            JOIN users u ON u.id = p.farmer_id
            WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $errors[] = "Product ID {$product_id} not found";
            continue;
        }

        // Check if stock column exists and validate stock
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
        $hasStockColumn = $stmt->rowCount() > 0;

        if ($hasStockColumn) {
            $available_stock = intval($product['stock']);
            if ($available_stock < $quantity) {
                $errors[] = "Insufficient stock for {$product['name']}. Available: {$available_stock}, Requested: {$quantity}";
                continue;
            }
        }

        $farmer_id = intval($product['farmer_id']);
        $unit_price = floatval($product['price']);
        $total_price = $unit_price * $quantity;

        // Determine payment status based on payment method
        // E-wallet, GCash, and Bank Transfer require immediate payment
        // Cash on delivery is unpaid until delivery
        $payment_status = in_array($payment_method, ['ewallet', 'gcash', 'bank_transfer']) ? 'paid' : 'unpaid';

        // Check if enhanced orders table columns exist
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
        $hasEnhancedColumns = $stmt->rowCount() > 0;

        if ($hasEnhancedColumns) {
            // Create order with enhanced fields
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    buyer_id, farmer_id, product_id, quantity, unit_price, total_price, 
                    status, payment_method, payment_status, shipping_status, 
                    delivery_address, delivery_distance, estimated_delivery_time
                )
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, 'to_ship', ?, ?, ?)
            ");
            $stmt->execute([
                $buyer_id,
                $farmer_id,
                $product_id,
                $quantity,
                $unit_price,
                $total_price,
                $payment_method,
                $payment_status,
                $delivery_address,
                $delivery_distance,
                $estimated_delivery_time
            ]);
        } else {
            // Fallback for old schema
            $stmt = $pdo->prepare("
                INSERT INTO orders (buyer_id, farmer_id, product_id, quantity, unit_price, total_price, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$buyer_id, $farmer_id, $product_id, $quantity, $unit_price, $total_price]);
        }
        $order_id = $pdo->lastInsertId();

        // Update product stock if stock column exists
        if ($hasStockColumn) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$quantity, $product_id]);
        }

        $orders_created[] = [
            'order_id' => $order_id,
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'farmer_name' => $product['farmer_name'],
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'total_price' => $total_price
        ];
    }

    if (!empty($errors) && empty($orders_created)) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Commit transaction
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    $response = [
        'success' => true,
        'message' => count($orders_created) . ' order(s) placed successfully',
        'orders' => $orders_created
    ];

    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }

    echo json_encode($response);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database error in create_order.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General error in create_order.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
