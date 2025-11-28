<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Check if user is logged in
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
    $product_id = intval($_POST['product_id'] ?? 0);
    $name = trim($_POST['product_name'] ?? '');
    $price = isset($_POST['product_price']) ? floatval($_POST['product_price']) : null;
    $price_gram = isset($_POST['product_price_gram']) ? floatval($_POST['product_price_gram']) : null;
    $price_kg = isset($_POST['product_price_kg']) ? floatval($_POST['product_price_kg']) : null;
    $description = trim($_POST['product_description'] ?? '');
    $stock = isset($_POST['product_stock']) ? intval($_POST['product_stock']) : null;

    // Validate product ID
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    // Check if product belongs to the farmer
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND farmer_id = ?");
    $stmt->execute([$product_id, $farmer_id]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
        exit;
    }

    // Handle stock-only update (quick update)
    // Check if this is a stock-only update by seeing if stock is set and other fields are not
    $hasName = isset($_POST['product_name']) && !empty(trim($_POST['product_name'] ?? ''));
    $hasPrice = isset($_POST['product_price']) && $_POST['product_price'] !== '';
    $hasPriceGram = isset($_POST['product_price_gram']) && $_POST['product_price_gram'] !== '';
    $hasPriceKg = isset($_POST['product_price_kg']) && $_POST['product_price_kg'] !== '';
    $hasDescription = isset($_POST['product_description']) && !empty(trim($_POST['product_description'] ?? ''));
    $hasImage = isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK;

    // If only stock is provided (and product_id), treat as stock-only update
    $isStockOnlyUpdate = ($stock !== null && !$hasName && !$hasPrice && !$hasPriceGram && !$hasPriceKg && !$hasDescription && !$hasImage);

    if ($isStockOnlyUpdate) {
        if ($stock < 0) {
            echo json_encode(['success' => false, 'message' => 'Stock quantity cannot be negative']);
            exit;
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

                if (!$hasStockColumn) {
                    echo json_encode(['success' => false, 'message' => 'Failed to add stock column. Please run add_stock_column.php']);
                    exit;
                }
            }
        }

        if ($hasStockColumn) {
            $stmt = $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ? AND farmer_id = ?");
            $result = $stmt->execute([$stock, $product_id, $farmer_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database does not support stock column. Please run add_stock_column.php']);
            exit;
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update stock']);
            exit;
        }
    }

    // Full product update
    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Product name must be at least 2 characters long';
    }

    if ($price !== null && $price <= 0) {
        $errors[] = 'Price must be greater than 0';
    }

    if ($price_gram !== null && $price_gram <= 0) {
        $errors[] = 'Price per gram must be greater than 0';
    }

    if ($price_kg !== null && $price_kg <= 0) {
        $errors[] = 'Price per kilogram must be greater than 0';
    }

    if ($stock !== null && $stock < 0) {
        $errors[] = 'Stock quantity cannot be negative';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Handle image upload if new image is provided
    $image_url = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
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

    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock'");
    $hasStockColumn = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_gram'");
    $hasPriceGramColumn = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'price_kg'");
    $hasPriceKgColumn = $stmt->rowCount() > 0;

    // Use price_kg as default price if price_kg is set, otherwise use price
    $finalPrice = ($price_kg !== null) ? $price_kg : ($price !== null ? $price : 0);

    // Build update query dynamically based on available columns
    $updateFields = ['name = ?', 'description = ?', 'price = ?', 'updated_at = NOW()'];
    $updateValues = [$name, $description, $finalPrice];
    
    if ($hasPriceGramColumn && $price_gram !== null) {
        $updateFields[] = 'price_gram = ?';
        $updateValues[] = $price_gram;
    }
    
    if ($hasPriceKgColumn && $price_kg !== null) {
        $updateFields[] = 'price_kg = ?';
        $updateValues[] = $price_kg;
    }
    
    if ($image_url) {
        $updateFields[] = 'image_url = ?';
        $updateValues[] = $image_url;
    }
    
    if ($hasStockColumn && $stock !== null) {
        $updateFields[] = 'stock = ?';
        $updateValues[] = $stock;
    }
    
    $updateValues[] = $product_id;
    $updateValues[] = $farmer_id;
    
    $sql = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE id = ? AND farmer_id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($updateValues);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
