-- Add email verification columns to users table
-- Run this SQL to add verification support

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS verification_code VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0;

-- Update existing users to be verified (optional - for existing data)
-- UPDATE users SET is_verified = 1 WHERE verification_code IS NULL;




