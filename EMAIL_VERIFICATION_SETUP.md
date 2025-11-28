# Email Verification System Setup Guide

## Overview
Complete email verification system for user registration using PHPMailer and Gmail SMTP.

## Installation Steps

### 1. Install PHPMailer via Composer

```bash
cd agriculture-marketplace
composer install
```

If you don't have Composer installed:
- Download from: https://getcomposer.org/download/
- Or use: `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"`
- Then: `php composer-setup.php`

### 2. Update Database

Run the SQL migration to add verification columns:

```sql
-- Option 1: Run via phpMyAdmin
-- Open phpMyAdmin → Select 'agrimarket' database → SQL tab
-- Copy and paste the contents of: sql/add_email_verification.sql

-- Option 2: Run via command line
mysql -u root -p agrimarket < sql/add_email_verification.sql
```

Or manually execute:
```sql
ALTER TABLE users 
ADD COLUMN verification_code VARCHAR(100) NULL,
ADD COLUMN is_verified TINYINT(1) DEFAULT 0;
```

### 3. Configure Gmail SMTP

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Enter "AgriMarket" as the name
   - Copy the 16-character password

3. **Update Email Configuration**:
   - Open: `config/email_config.php`
   - Replace `'your-app-password'` with your generated app password
   - Update `smtp_username` and `smtp_from_email` if using different Gmail

### 4. Test the System

1. **Register a new user** at: `register.html`
2. **Check email** for verification code
3. **Verify email** at: `verify.html`
4. **Login** at: `login.html`

## File Structure

```
agriculture-marketplace/
├── api/
│   ├── register.php          # Registration with email sending
│   ├── verify.php            # Email verification logic
│   └── login.php             # Login with verification check
├── config/
│   ├── email_config.php      # SMTP configuration
│   └── phpmailer_helper.php  # PHPMailer helper functions
├── sql/
│   └── add_email_verification.sql  # Database migration
├── verify.html               # Verification page
├── register.html             # Registration page (existing)
├── login.html                # Login page (existing)
└── composer.json             # Composer dependencies
```

## Features

✅ **Registration**: Sends 6-digit verification code via email
✅ **Verification**: Validates code and activates account
✅ **Login Protection**: Blocks unverified users from logging in
✅ **Email Templates**: Beautiful HTML email with verification code
✅ **Error Handling**: Comprehensive error messages
✅ **Security**: Secure code generation and validation

## Troubleshooting

### Email Not Sending

1. **Check Gmail App Password**: Make sure you're using the correct 16-character app password
2. **Check SMTP Settings**: Verify `smtp_host`, `smtp_port`, and `smtp_encryption` in `email_config.php`
3. **Enable Debug Mode**: Set `smtp_debug => 2` in `email_config.php` to see detailed errors
4. **Check PHP Error Logs**: Look in `C:\xampp\php\logs\php_error_log` for errors

### Verification Code Not Working

1. **Check Database**: Verify `verification_code` column exists in users table
2. **Check Code Format**: Code must be exactly 6 digits
3. **Check Email**: Make sure you're using the same email used during registration

### Login Blocked

1. **Verify Account**: Make sure you've completed email verification
2. **Check Database**: Verify `is_verified = 1` in users table
3. **Re-verify**: If needed, register again and verify

## Using Different SMTP Server

To use a different SMTP server (not Gmail), update `config/email_config.php`:

```php
return [
    'smtp_host' => 'smtp.yourdomain.com',
    'smtp_port' => 587,  // or 465 for SSL
    'smtp_username' => 'your-email@yourdomain.com',
    'smtp_password' => 'your-password',
    'smtp_from_email' => 'your-email@yourdomain.com',
    'smtp_from_name' => 'AgriMarket',
    'smtp_encryption' => 'tls',  // or 'ssl' for port 465
    'smtp_debug' => 0
];
```

## Security Notes

- Verification codes expire when account is verified
- Codes are cleared from database after successful verification
- Passwords are hashed using PHP's `password_hash()`
- All inputs are validated and sanitized
- SQL injection protection via prepared statements




