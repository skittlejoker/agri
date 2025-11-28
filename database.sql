-- AgriMarket Database Schema
-- Run this SQL script to create the database and tables

CREATE DATABASE IF NOT EXISTS agrimarket;
USE agrimarket;

-- Users table for login/registration
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('farmer', 'buyer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table for farmer products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cart table for buyer cart items
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_buyer_product (buyer_id, product_id)
);

-- Insert sample data for testing
INSERT INTO users (full_name, email, username, password, user_type) VALUES
('John Farmer', 'john@example.com', 'johnfarmer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'farmer'),
('Jane Buyer', 'jane@example.com', 'janebuyer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer');

-- Insert sample products
INSERT INTO products (farmer_id, name, description, price, image_url) VALUES
(1, 'Organic Carrots', 'Fresh organic carrots from local farm', 3.50, 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=400'),
(1, 'Fresh Tomatoes', 'Ripe red tomatoes, perfect for salads', 4.00, 'https://images.unsplash.com/photo-1546470427-5c1d0b0b0b0b?w=400'),
(1, 'Green Lettuce', 'Crisp green lettuce leaves', 2.50, 'https://images.unsplash.com/photo-1622206151226-18ca2c9ab4a1?w=400');

-- Note: The password hash above is for 'password' - change this in production!
