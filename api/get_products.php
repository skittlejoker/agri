<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $farmer_id = $_SESSION['user_id'];

    // Check if stock column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    $hasStockColumn = $stmt->rowCount() > 0;
    
    // Check if price_gram column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_gram'");
    $hasPriceGramColumn = $stmt->rowCount() > 0;
    
    // Check if price_kg column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_kg'");
    $hasPriceKgColumn = $stmt->rowCount() > 0;

    // Build query based on which columns exist - always include price_kg and price_gram if columns exist
    $selectFields = ['id', 'name', 'description', 'price', 'image_url'];
    
    if ($hasPriceGramColumn) {
        $selectFields[] = 'price_gram';
    }
    if ($hasPriceKgColumn) {
        $selectFields[] = 'price_kg';
    }
    if ($hasStockColumn) {
        $selectFields[] = 'stock';
    }
    
    $selectFields[] = 'created_at';
    $selectFields[] = 'updated_at';
    
    $fieldsList = implode(', ', $selectFields);
    
    $stmt = $pdo->prepare("
        SELECT $fieldsList
        FROM products 
        WHERE farmer_id = ? 
        ORDER BY created_at DESC
    ");

    $stmt->execute([$farmer_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $formatted_products = array_map(function ($product) use ($hasStockColumn, $hasPriceGramColumn, $hasPriceKgColumn) {
        return [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['description'] ?? '',
            'price' => (float)$product['price'], // Keep for backward compatibility
            'price_gram' => (isset($product['price_gram']) && $hasPriceGramColumn) ? (float)$product['price_gram'] : null,
            'price_kg' => (isset($product['price_kg']) && $hasPriceKgColumn) ? (float)$product['price_kg'] : (float)$product['price'],
            'image_url' => $product['image_url'] ?? null,
            'stock' => (isset($product['stock']) && $hasStockColumn) ? (int)$product['stock'] : 0,
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at'] ?? null
        ];
    }, $products);

    echo json_encode([
        'success' => true,
        'products' => $formatted_products,
        'total' => count($formatted_products)
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
