<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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

// Check if user is logged in (implement your session check here)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $farmer_id = $_SESSION['user_id'];
    
    // Get POST data - handle both form data and JSON
    $name = trim($_POST['product_name'] ?? '');
    $price_gram = floatval($_POST['product_price_gram'] ?? 0);
    $price_kg = floatval($_POST['product_price_kg'] ?? 0);
    $description = trim($_POST['product_description'] ?? '');
    $stock = intval($_POST['product_stock'] ?? 0);
    
    // Debug logging
    error_log("add_product.php - Received POST data: " . print_r($_POST, true));
    error_log("add_product.php - Parsed: name=$name, price_gram=$price_gram, price_kg=$price_kg, stock=$stock, farmer_id=$farmer_id");

    // Validate input
    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Product name must be at least 2 characters long';
    }

    if ($price_gram <= 0) {
        $errors[] = 'Price per gram must be greater than 0';
    }

    if ($price_kg <= 0) {
        $errors[] = 'Price per kilogram must be greater than 0';
    }

    if ($stock < 0) {
        $errors[] = 'Stock quantity cannot be negative';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Handle image - can be URL or file upload
    $image_url = null;

    // First, check if image URL is provided
    if (isset($_POST['product_image']) && !empty(trim($_POST['product_image']))) {
        $image_url = trim($_POST['product_image']);
        // Validate URL format
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image URL']);
            exit;
        }
    }
    // Otherwise, handle file upload
    elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed']);
            exit;
        }

        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
            $image_url = 'uploads/products/' . $file_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }

    // Check if stock column exists, and add it if it doesn't
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    $hasStockColumn = $stmt->rowCount() > 0;

    if (!$hasStockColumn) {
        // Automatically add the stock column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER image_url");
            $hasStockColumn = true;
        } catch (PDOException $e) {
            // If column already exists (race condition) or other error
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
            $hasStockColumn = $stmt->rowCount() > 0;
        }
    }
    
    // Check if price_gram column exists, and add it if it doesn't
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_gram'");
    $hasPriceGramColumn = $stmt->rowCount() > 0;

    if (!$hasPriceGramColumn) {
        // Automatically add the price_gram column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN price_gram DECIMAL(10,2) DEFAULT 0 AFTER price");
            $hasPriceGramColumn = true;
        } catch (PDOException $e) {
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_gram'");
            $hasPriceGramColumn = $stmt->rowCount() > 0;
        }
    }
    
    // Check if price_kg column exists, and add it if it doesn't
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_kg'");
    $hasPriceKgColumn = $stmt->rowCount() > 0;

    if (!$hasPriceKgColumn) {
        // Automatically add the price_kg column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN price_kg DECIMAL(10,2) DEFAULT 0 AFTER price_gram");
            $hasPriceKgColumn = true;
        } catch (PDOException $e) {
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_kg'");
            $hasPriceKgColumn = $stmt->rowCount() > 0;
        }
    }

    // Insert product into database
    // Keep price column for backward compatibility (use price_kg as default)
    if ($hasStockColumn && $hasPriceGramColumn && $hasPriceKgColumn) {
        $stmt = $pdo->prepare("
            INSERT INTO products (farmer_id, name, description, price, price_gram, price_kg, image_url, stock, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$farmer_id, $name, $description, $price_kg, $price_gram, $price_kg, $image_url, $stock]);
    } elseif ($hasStockColumn && $hasPriceGramColumn) {
        $stmt = $pdo->prepare("
            INSERT INTO products (farmer_id, name, description, price, price_gram, image_url, stock, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$farmer_id, $name, $description, $price_kg, $price_gram, $image_url, $stock]);
    } elseif ($hasStockColumn) {
        $stmt = $pdo->prepare("
            INSERT INTO products (farmer_id, name, description, price, image_url, stock, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$farmer_id, $name, $description, $price_kg, $image_url, $stock]);
    } else {
        // Fallback: insert without stock and price columns if they don't exist
        $stmt = $pdo->prepare("
            INSERT INTO products (farmer_id, name, description, price, image_url, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $result = $stmt->execute([$farmer_id, $name, $description, $price_kg, $image_url]);
    }

    if ($result) {
        $product_id = $pdo->lastInsertId();
        error_log("Product added successfully with ID: $product_id");
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Product added successfully',
            'product_id' => $product_id
        ]);
        exit;
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Failed to add product. SQL Error: " . print_r($errorInfo, true));
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add product to database',
            'error' => $errorInfo[2] ?? 'Unknown database error',
            'debug' => [
                'farmer_id' => $farmer_id,
                'name' => $name,
                'price_gram' => $price_gram,
                'price_kg' => $price_kg
            ]
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in add_product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in add_product.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
