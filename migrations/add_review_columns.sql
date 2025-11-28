-- Add review columns to orders table
-- Run this SQL script if automatic column creation fails

ALTER TABLE orders ADD COLUMN review_rating INT DEFAULT NULL;
ALTER TABLE orders ADD COLUMN review_comment TEXT DEFAULT NULL;



