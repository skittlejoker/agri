-- Enhanced Orders Table Migration
-- Adds payment, shipping, delivery, and review features

ALTER TABLE orders
ADD COLUMN IF NOT EXISTS payment_method ENUM('ewallet', 'cash_on_delivery') DEFAULT 'cash_on_delivery' AFTER status,
ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid' AFTER payment_method,
ADD COLUMN IF NOT EXISTS shipping_status ENUM('to_ship', 'shipped', 'delivered') DEFAULT 'to_ship' AFTER payment_status,
ADD COLUMN IF NOT EXISTS delivery_address TEXT AFTER shipping_status,
ADD COLUMN IF NOT EXISTS delivery_distance DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Distance in kilometers' AFTER delivery_address,
ADD COLUMN IF NOT EXISTS estimated_delivery_time INT DEFAULT 0 COMMENT 'Estimated delivery time in minutes' AFTER delivery_distance,
ADD COLUMN IF NOT EXISTS shipped_at DATETIME NULL AFTER estimated_delivery_time,
ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL AFTER shipped_at,
ADD COLUMN IF NOT EXISTS review_rating INT NULL CHECK (review_rating BETWEEN 1 AND 5) AFTER delivered_at,
ADD COLUMN IF NOT EXISTS review_comment TEXT NULL AFTER review_rating,
ADD INDEX idx_payment_status (payment_status),
ADD INDEX idx_shipping_status (shipping_status),
ADD INDEX idx_shipped_at (shipped_at);

-- Update existing orders to have default values
UPDATE orders SET 
    payment_method = 'cash_on_delivery',
    payment_status = CASE 
        WHEN status = 'completed' THEN 'paid'
        ELSE 'unpaid'
    END,
    shipping_status = CASE
        WHEN status = 'completed' THEN 'delivered'
        WHEN status = 'confirmed' THEN 'to_ship'
        ELSE 'to_ship'
    END
WHERE payment_method IS NULL;



