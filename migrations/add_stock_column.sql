-- Migration: Add stock column to products table
-- Run this if you already have an existing database

USE agrimarket;

-- Add stock column if it doesn't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0 
AFTER image_url;


