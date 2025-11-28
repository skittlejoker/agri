<?php
/**
 * Add review columns to orders table
 * Run this script once to set up the review system
 */

require_once '../config/database.php';

try {
    echo "<h2>Adding Review Columns to Orders Table</h2>";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
    $hasReviewRating = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
    $hasReviewComment = $stmt->rowCount() > 0;
    
    if ($hasReviewRating && $hasReviewComment) {
        echo "<p style='color: green;'>✓ Review columns already exist. No action needed.</p>";
        exit;
    }
    
    // Add review_rating column
    if (!$hasReviewRating) {
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN review_rating INT DEFAULT NULL");
            echo "<p style='color: green;'>✓ Successfully added review_rating column</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error adding review_rating: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>→ review_rating column already exists</p>";
    }
    
    // Add review_comment column
    if (!$hasReviewComment) {
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN review_comment TEXT DEFAULT NULL");
            echo "<p style='color: green;'>✓ Successfully added review_comment column</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error adding review_comment: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>→ review_comment column already exists</p>";
    }
    
    // Final verification
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_rating'");
    $finalRating = $stmt->rowCount() > 0;
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'review_comment'");
    $finalComment = $stmt->rowCount() > 0;
    
    if ($finalRating && $finalComment) {
        echo "<h3 style='color: green;'>✓ Review system is now set up successfully!</h3>";
    } else {
        echo "<h3 style='color: red;'>✗ Setup incomplete. Please check database permissions.</h3>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}



