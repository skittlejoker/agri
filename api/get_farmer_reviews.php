<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Check if user is logged in as farmer
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only farmers can view reviews.']);
    exit;
}

try {
    $farmer_id = $_SESSION['user_id'];

    // Check if review columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
    $hasReviewRating = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
    $hasReviewComment = $stmt->rowCount() > 0;

    if (!$hasReviewRating || !$hasReviewComment) {
        echo json_encode([
            'success' => true,
            'reviews' => [],
            'total' => 0,
            'average_rating' => 0,
            'message' => 'Review features not yet available'
        ]);
        exit;
    }

    // Get all reviews for products sold by this farmer
    try {
        $stmt = $pdo->prepare("
            SELECT 
                o.id as order_id,
                o.review_rating,
                o.review_comment,
                o.created_at as review_date,
                o.quantity,
                o.total_price,
                p.id as product_id,
                p.name as product_name,
                p.image_url as product_image,
                u.id as buyer_id,
                u.name as buyer_name,
                u.email as buyer_email
            FROM orders o
            INNER JOIN products p ON o.product_id = p.id
            INNER JOIN users u ON o.buyer_id = u.id
            WHERE o.farmer_id = ? 
            AND o.review_rating IS NOT NULL
            AND o.review_rating > 0
            ORDER BY o.created_at DESC
        ");

        $stmt->execute([$farmer_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SQL Error in get_farmer_reviews.php query: " . $e->getMessage());
        // If query fails, return empty reviews instead of error
        $reviews = [];
    }

    // Format the response
    $formatted_reviews = array_map(function ($review) {
        return [
            'order_id' => (int)$review['order_id'],
            'product_id' => (int)$review['product_id'],
            'product_name' => $review['product_name'],
            'product_image' => $review['product_image'],
            'buyer_id' => (int)$review['buyer_id'],
            'buyer_name' => $review['buyer_name'],
            'buyer_email' => $review['buyer_email'],
            'rating' => (int)$review['review_rating'],
            'comment' => $review['review_comment'] ?? '',
            'review_date' => $review['review_date'],
            'quantity' => (int)$review['quantity'],
            'total_price' => (float)$review['total_price']
        ];
    }, $reviews);

    // Calculate average rating
    $totalRating = 0;
    $reviewCount = count($formatted_reviews);
    foreach ($formatted_reviews as $review) {
        $totalRating += $review['rating'];
    }
    $averageRating = $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0;

    echo json_encode([
        'success' => true,
        'reviews' => $formatted_reviews,
        'total' => $reviewCount,
        'average_rating' => $averageRating
    ]);
} catch (PDOException $e) {
    error_log("Database error in get_farmer_reviews.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again or contact support.'
    ]);
} catch (Exception $e) {
    error_log("General error in get_farmer_reviews.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

