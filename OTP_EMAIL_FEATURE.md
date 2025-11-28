# OTP-Based Forgot Password Feature with Email Integration

## 🎯 Overview

A complete forgot password system that sends **One-Time Password (OTP) codes** directly to users' personal email addresses for secure password recovery.

## 🚀 Key Features

✅ **Email Integration**: Sends OTP codes using Gmail SMTP  
✅ **Secure OTP Codes**: 6-digit codes with 10-minute expiration  
✅ **Multi-Step Flow**: Request → Verify → Reset  
✅ **Professional Email Template**: Beautiful HTML email with branding  
✅ **Resend Functionality**: Users can request new codes  
✅ **Rate Limiting**: One active code per user at a time  
✅ **Single-Use Verification**: Codes marked as used after verification  
✅ **Automatic Cleanup**: Expired codes removed automatically  

## 📁 Files Created

### Frontend:
- `forgot_password.html` - Multi-step OTP flow interface

### Backend:
- `api/send_otp_code.php` - Generates and sends OTP via email
- `api/verify_otp.php` - Verifies OTP code
- `config/email_config.php` - Gmail SMTP configuration

### Documentation:
- `EMAIL_SETUP_GUIDE.md` - Step-by-step email setup
- `OTP_EMAIL_FEATURE.md` - This file

## 🔐 Security Features

1. **OTP Expiration**: 10-minute validity
2. **Random Generation**: Cryptographically secure random bytes
3. **Single Use**: Codes verified once and marked as used
4. **Rate Limiting**: One code at a time per user
5. **Password Hashing**: Secure `password_hash()` implementation
6. **Token Security**: Reset tokens expire after 30 minutes
7. **Email Verification**: Requires matching email AND username

## 📧 Email Configuration

Uses Gmail (trancem260@gmail.com) for sending OTP codes.

### Setup Required:
1. Enable Gmail 2-Step Verification
2. Generate App Password
3. Update `config/email_config.php` with app password

**See `EMAIL_SETUP_GUIDE.md` for detailed instructions.**

## 🔄 Complete User Flow

### Step 1: Request OTP
```
User → Enter Email + Username → Click "Send Verification Code"
↓
System generates 6-digit OTP
↓
Email sent to user's inbox
↓
Show verification code input section
```

### Step 2: Verify OTP
```
User → Enter 6-digit code from email
↓
System validates code (expires in 10 minutes)
↓
If valid: Mark code as used, generate reset token
↓
Show password reset form
```

### Step 3: Reset Password
```
User → Enter new password twice
↓
System validates password requirements
↓
Update password in database using reset token
↓
Delete reset token (single use)
↓
Redirect to login page
```

## 📨 Email Template

The system sends a professional HTML email with:

- ✅ Branded header with logo
- ✅ Personalized greeting
- ✅ Large, easy-to-read OTP code
- ✅ Security warnings
- ✅ Expiration notice
- ✅ Professional footer

**Example Email:**
```
Subject: AgriMarket - Password Reset Verification Code

Hello John Doe,

You requested to reset your password for your AgriMarket account.

Your verification code is:

┌─────────────────┐
│   123456        │
└─────────────────┘

This code will expire in 10 minutes.

⚠️ Security Notice: If you didn't request this password reset, please ignore this email.

Thank you for using AgriMarket!

Best regards,
AgriMarket Team
```

## 🗄️ Database Schema

### `password_reset_otp` Table:
```sql
CREATE TABLE password_reset_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `password_reset_tokens` Table:
```sql
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🔌 API Endpoints

### 1. Send OTP Code
**URL:** `api/send_otp_code.php`  
**Method:** POST

**Request:**
```json
{
  "email": "user@example.com",
  "username": "username"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Verification code sent to your email",
  "user_id": 123
}
```

### 2. Verify OTP
**URL:** `api/verify_otp.php`  
**Method:** POST

**Request:**
```json
{
  "user_id": 123,
  "otp_code": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Verification code verified",
  "reset_token": "abc123..."
}
```

### 3. Reset Password
**URL:** `api/reset_password.php`  
**Method:** POST

**Request:**
```json
{
  "token": "abc123...",
  "newPassword": "newpass123",
  "confirmPassword": "newpass123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

## 🎨 User Interface

The `forgot_password.html` page has three sections that show/hide dynamically:

### Section 1: Request OTP
- Email input
- Username input
- "Send Verification Code" button

### Section 2: Verify OTP
- 6-digit OTP input
- "Verify Code" button
- "Resend Code" button

### Section 3: Reset Password
- New password input
- Confirm password input
- "Reset Password" button

## 🧪 Testing Instructions

### Test Case 1: Happy Path
1. Go to forgot password page
2. Enter valid email and username
3. Check email for OTP code
4. Enter OTP code within 10 minutes
5. Create new password
6. Login with new password
7. ✅ Should work successfully

### Test Case 2: Expired Code
1. Request OTP code
2. Wait more than 10 minutes
3. Try to verify code
4. ✅ Should show "expired" error

### Test Case 3: Invalid Code
1. Request OTP code
2. Enter wrong OTP code
3. ✅ Should show "invalid code" error

### Test Case 4: Resend Code
1. Request OTP code
2. Click "Resend Code"
3. ✅ Should receive new OTP code

## 🐛 Troubleshooting

### Email Not Sending?
1. Check Gmail app password is correct
2. Enable 2-Step Verification on Gmail
3. Check firewall allows port 587
4. Verify `email_config.php` settings

### OTP Not Received?
1. Check spam folder
2. Verify email address is correct
3. Check email server logs
4. Ensure database connection works

### Code Verification Fails?
1. Check code hasn't expired (10 minutes)
2. Verify code wasn't already used
3. Check database for code existence
4. Ensure correct user_id is being sent

## 🔧 Configuration

### Email Settings (`config/email_config.php`):
```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'trancem260@gmail.com',
    'smtp_password' => 'YOUR_APP_PASSWORD', // REQUIRED!
    'smtp_from_email' => 'trancem260@gmail.com',
    'smtp_from_name' => 'AgriMarket - Agriculture Platform',
    'smtp_encryption' => 'tls'
];
?>
```

## 📝 Next Steps to Use

1. **Set up Gmail App Password**:
   - Follow `EMAIL_SETUP_GUIDE.md`
   - Get 16-character app password
   - Update `email_config.php`

2. **Test the System**:
   - Navigate to forgot password page
   - Request OTP code
   - Check email
   - Enter code and reset password

3. **Go Live**:
   - System is ready to use!
   - Users can now recover passwords via email

## 🎉 Integration Complete!

The forgot password feature is now fully integrated with email delivery and works seamlessly with the existing change password functionality. Users can:

- ✅ Request password reset via email
- ✅ Receive secure OTP codes
- ✅ Verify codes and reset passwords
- ✅ Login with new passwords

**Everything is connected and ready to use!**

