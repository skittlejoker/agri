<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    require_once '../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
    exit;
}

try {
    // Check if stock column exists to avoid SQL errors on older DBs
    $stockExists = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
        $stockExists = $colStmt && $colStmt->rowCount() > 0;
    } catch (Exception $ie) {
        $stockExists = false;
    }
    
    // Check if price_gram column exists
    $priceGramExists = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_gram'");
        $priceGramExists = $colStmt && $colStmt->rowCount() > 0;
    } catch (Exception $ie) {
        $priceGramExists = false;
    }
    
    // Check if price_kg column exists
    $priceKgExists = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_kg'");
        $priceKgExists = $colStmt && $colStmt->rowCount() > 0;
    } catch (Exception $ie) {
        $priceKgExists = false;
    }

    // Build SELECT with/without stock and price columns
    if ($stockExists && $priceGramExists && $priceKgExists) {
        $select = "SELECT p.id, p.name, p.description, p.price, p.price_gram, p.price_kg, p.image_url, p.stock, p.created_at,
                u.id AS farmer_id, u.full_name AS farmer_name, u.username AS farmer_username
            FROM products p
            JOIN users u ON u.id = p.farmer_id
            ORDER BY p.created_at DESC";
    } elseif ($stockExists && $priceGramExists) {
        $select = "SELECT p.id, p.name, p.description, p.price, p.price_gram, p.image_url, p.stock, p.created_at,
                u.id AS farmer_id, u.full_name AS farmer_name, u.username AS farmer_username
            FROM products p
            JOIN users u ON u.id = p.farmer_id
            ORDER BY p.created_at DESC";
    } elseif ($stockExists) {
        $select = "SELECT p.id, p.name, p.description, p.price, p.image_url, p.stock, p.created_at,
                u.id AS farmer_id, u.full_name AS farmer_name, u.username AS farmer_username
            FROM products p
            JOIN users u ON u.id = p.farmer_id
            ORDER BY p.created_at DESC";
    } else {
        $select = "SELECT p.id, p.name, p.description, p.price, p.image_url, p.created_at,
                u.id AS farmer_id, u.full_name AS farmer_name, u.username AS farmer_username
            FROM products p
            JOIN users u ON u.id = p.farmer_id
            ORDER BY p.created_at DESC";
    }

    $stmt = $pdo->prepare($select);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = array_map(function ($r) use ($stockExists, $priceGramExists, $priceKgExists) {
        return [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'description' => $r['description'] ?? '',
            'price' => (float)$r['price'], // Keep for backward compatibility
            'price_gram' => ($priceGramExists && isset($r['price_gram'])) ? (float)$r['price_gram'] : null,
            'price_kg' => ($priceKgExists && isset($r['price_kg'])) ? (float)$r['price_kg'] : (float)$r['price'],
            'image_url' => $r['image_url'] ?? null,
            'stock' => $stockExists && isset($r['stock']) ? (int)$r['stock'] : 0,
            'farmer' => [
                'id' => (int)$r['farmer_id'],
                'name' => $r['farmer_name'] ?: $r['farmer_username'],
                'username' => $r['farmer_username']
            ],
            'created_at' => $r['created_at']
        ];
    }, $rows ?: []);

    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => count($products)
    ]);
} catch (PDOException $e) {
    error_log('Database error in get_all_products.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('General error in get_all_products.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
