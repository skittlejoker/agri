<?php
// Configure session cookie settings before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

header('Content-Type: application/json');
// Note: CORS headers removed for same-origin requests to allow credentials
// If you need CORS, set specific origin instead of *

require_once '../config/database.php';

// Check if user is logged in as buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only buyers can submit reviews.']);
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

    $order_id = intval($input['order_id'] ?? 0);
    $rating = intval($input['rating'] ?? 0);
    $comment = trim($input['comment'] ?? '');

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        exit;
    }

    // Check if enhanced columns exist and create them if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
    $hasReviewRating = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
    $hasReviewComment = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_status'");
    $hasShippingStatus = $stmt->rowCount() > 0;

    // Check if delivered_at exists to determine where to add columns
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivered_at'");
    $hasDeliveredAt = $stmt->rowCount() > 0;
    
    // Auto-create review columns if they don't exist - use simplest approach
    if (!$hasReviewRating) {
        try {
            // Try simplest approach first - add at end of table
            $pdo->exec("ALTER TABLE orders ADD COLUMN review_rating INT DEFAULT NULL");
            $hasReviewRating = true;
            error_log("Successfully created review_rating column");
        } catch (PDOException $e) {
            error_log("Error creating review_rating column: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            // Check if column exists now (might have been created by another request)
            $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
            $hasReviewRating = $stmt->rowCount() > 0;
            if (!$hasReviewRating) {
                // Check if it's a duplicate column error
                if (strpos($e->getMessage(), 'Duplicate column') !== false || $e->getCode() == '42S21') {
                    $hasReviewRating = true; // Column exists, just race condition
                } else {
                    error_log("Failed to create review_rating column. Database may not have ALTER permissions.");
                }
            }
        }
    }
    
    if (!$hasReviewComment) {
        try {
            // Try simplest approach first - add at end of table
            $pdo->exec("ALTER TABLE orders ADD COLUMN review_comment TEXT DEFAULT NULL");
            $hasReviewComment = true;
            error_log("Successfully created review_comment column");
        } catch (PDOException $e) {
            error_log("Error creating review_comment column: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            // Check if column exists now (might have been created by another request)
            $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
            $hasReviewComment = $stmt->rowCount() > 0;
            if (!$hasReviewComment) {
                // Check if it's a duplicate column error
                if (strpos($e->getMessage(), 'Duplicate column') !== false || $e->getCode() == '42S21') {
                    $hasReviewComment = true; // Column exists, just race condition
                } else {
                    error_log("Failed to create review_comment column. Database may not have ALTER permissions.");
                }
            }
        }
    }
    
    $hasReviewColumns = $hasReviewRating && $hasReviewComment;
    
    // Final check - verify columns actually exist
    if (!$hasReviewColumns) {
        // Re-check one more time
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
        $hasReviewRating = $stmt->rowCount() > 0;
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
        $hasReviewComment = $stmt->rowCount() > 0;
        $hasReviewColumns = $hasReviewRating && $hasReviewComment;
    }
    
    if (!$hasReviewColumns) {
        error_log("Review columns could not be created. review_rating: " . ($hasReviewRating ? 'exists' : 'missing') . ", review_comment: " . ($hasReviewComment ? 'exists' : 'missing'));
        error_log("Please run the SQL script: migrations/add_review_columns.sql");
        echo json_encode([
            'success' => false, 
            'message' => 'Review system setup failed. Please run the database migration script: migrations/add_review_columns.sql'
        ]);
        exit;
    }

    // Build query based on available columns
    if ($hasShippingStatus) {
        // Enhanced orders table with shipping_status
        $stmt = $pdo->prepare("
            SELECT o.id, o.shipping_status, o.status, o.delivered_at
            FROM orders o
            WHERE o.id = ? AND o.buyer_id = ?
        ");
    } else {
        // Legacy orders table without shipping_status
        $stmt = $pdo->prepare("
            SELECT o.id, o.status, o.delivered_at
            FROM orders o
            WHERE o.id = ? AND o.buyer_id = ?
        ");
    }
    
    $stmt->execute([$order_id, $buyer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }

    // Check if order is delivered (handle both enhanced and legacy)
    $isDelivered = false;
    if ($hasShippingStatus && isset($order['shipping_status'])) {
        $isDelivered = ($order['shipping_status'] === 'delivered');
    } else if (isset($order['status'])) {
        // Legacy: check if status is 'completed'
        $isDelivered = ($order['status'] === 'completed');
    }

    if (!$isDelivered) {
        echo json_encode(['success' => false, 'message' => 'You can only review delivered or completed orders']);
        exit;
    }

    // Final verification - ensure columns exist before updating
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
    $finalCheckRating = $stmt->rowCount() > 0;
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
    $finalCheckComment = $stmt->rowCount() > 0;
    
    if (!$finalCheckRating || !$finalCheckComment) {
        error_log("CRITICAL: Review columns missing before UPDATE. review_rating: " . ($finalCheckRating ? 'exists' : 'MISSING') . ", review_comment: " . ($finalCheckComment ? 'exists' : 'MISSING'));
        echo json_encode([
            'success' => false, 
            'message' => 'Review columns are missing. Please run migrations/add_review_columns.php to set up the review system.'
        ]);
        exit;
    }
    
    // Update review - check if updated_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'updated_at'");
    $hasUpdatedAt = $stmt->rowCount() > 0;
    
    if ($hasUpdatedAt) {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET review_rating = ?, review_comment = ?, updated_at = NOW() 
            WHERE id = ? AND buyer_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET review_rating = ?, review_comment = ? 
            WHERE id = ? AND buyer_id = ?
        ");
    }
    
    try {
        $result = $stmt->execute([$rating, $comment, $order_id, $buyer_id]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Review submitted successfully'
            ]);
        } else {
            // Check for errors
            $errorInfo = $stmt->errorInfo();
            error_log("Update review failed: " . print_r($errorInfo, true));
            $errorMessage = 'Failed to submit review. Please try again.';
            
            // Provide more specific error message if possible
            if (isset($errorInfo[2])) {
                error_log("SQL Error: " . $errorInfo[2]);
                // Don't expose SQL errors to user, but log them
            }
            
            echo json_encode(['success' => false, 'message' => $errorMessage]);
        }
    } catch (PDOException $e) {
        error_log("PDO Exception during review update: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again or contact support.']);
    }
} catch (PDOException $e) {
    error_log("Database error in submit_review.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Provide more helpful error message based on error type
    $errorMessage = 'Database error occurred. Please try again or contact support.';
    
    // Check for specific error types
    $errorMsg = $e->getMessage();
    if (strpos($errorMsg, 'Unknown column') !== false) {
        $errorMessage = 'Review system is not properly set up. Please contact support.';
    } elseif (strpos($errorMsg, 'Table') !== false && strpos($errorMsg, "doesn't exist") !== false) {
        $errorMessage = 'Database table is missing. Please contact support.';
    }
    
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch (Exception $e) {
    error_log("General error in submit_review.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
