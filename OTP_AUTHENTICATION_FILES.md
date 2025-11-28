# OTP Authentication System - Complete File List

## 📋 Overview
This document lists all necessary files for a fully functional OTP (One-Time Password) authentication system that can send and receive emails.

---

## 🔧 Core Configuration Files

### 1. `config/email_config.php`
**Purpose**: Gmail SMTP configuration for sending emails
**Required**: ✅ YES
**Status**: ✅ Configured
**Action Required**: 
- Replace `'your-app-password'` with your Gmail App Password
- Get App Password from: https://myaccount.google.com/apppasswords

**Contents**:
- SMTP host: `smtp.gmail.com`
- SMTP port: `587`
- SMTP encryption: `tls`
- Gmail username and password

---

### 2. `config/phpmailer_helper.php`
**Purpose**: PHPMailer helper functions for sending emails
**Required**: ✅ YES
**Status**: ✅ Complete
**Functions Included**:
- `sendEmail()` - Generic email sending function
- `sendVerificationEmail()` - Sends account verification code
- `sendOTPEmail()` - Sends password reset OTP code
- `getEmailConfig()` - Retrieves email configuration

**Features**:
- PHPMailer integration
- SMTP authentication
- HTML email templates
- Error handling and logging
- Input sanitization (XSS protection)

---

### 3. `config/database.php`
**Purpose**: Database connection and helper functions
**Required**: ✅ YES
**Status**: ✅ Complete
**Functions**:
- `sendResponse()` - JSON response helper
- `hashPassword()` - Password hashing
- `verifyPassword()` - Password verification

---

## 📧 OTP Email API Endpoints

### 4. `api/send_otp_code.php`
**Purpose**: Generates and sends OTP code for password reset
**Required**: ✅ YES
**Status**: ✅ Complete
**Features**:
- Generates secure 6-digit OTP using `random_int()`
- Stores OTP in database with 10-minute expiry
- Sends email via PHPMailer
- Validates email and username
- Auto-creates `password_reset_otp` table

**Request**:
```json
POST /api/send_otp_code.php
{
  "email": "user@example.com",
  "username": "username"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Verification code has been sent...",
  "user_id": 123
}
```

---

### 5. `api/verify_otp.php`
**Purpose**: Verifies OTP code and generates reset token
**Required**: ✅ YES
**Status**: ✅ Complete
**Features**:
- Validates OTP format (6 digits)
- Checks OTP expiration
- Marks OTP as verified
- Generates secure reset token (64-char hex)
- Auto-creates `password_reset_tokens` table

**Request**:
```json
POST /api/verify_otp.php
{
  "user_id": 123,
  "otp_code": "123456"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Verification code verified successfully",
  "reset_token": "abc123..."
}
```

---

### 6. `api/reset_password.php`
**Purpose**: Resets password using reset token
**Required**: ✅ YES
**Status**: ✅ Complete
**Features**:
- Validates reset token format
- Checks token expiration
- Ensures token is not already used
- Securely hashes new password
- Marks token as used

**Request**:
```json
POST /api/reset_password.php
{
  "token": "abc123...",
  "newPassword": "newpass123",
  "confirmPassword": "newpass123"
}
```

---

## 🔐 Account Verification Files

### 7. `api/register.php`
**Purpose**: User registration with email verification
**Required**: ✅ YES
**Status**: ✅ Complete
**Features**:
- Creates new user account
- Generates verification code
- Sends verification email via PHPMailer
- Stores verification code in database

**Uses**: `sendVerificationEmail()` from `phpmailer_helper.php`

---

### 8. `api/verify.php`
**Purpose**: Verifies email address with verification code
**Required**: ✅ YES
**Status**: ✅ Complete
**Features**:
- Validates verification code
- Updates user as verified
- Clears verification code after use

**Request**:
```json
POST /api/verify.php
{
  "email": "user@example.com",
  "verification_code": "123456"
}
```

---

### 9. `api/resend_verification_code.php`
**Purpose**: Resends verification code to user email
**Required**: ✅ YES (NEW FILE)
**Status**: ✅ Complete
**Features**:
- Generates new verification code
- Updates code in database
- Resends email via PHPMailer

**Request**:
```json
POST /api/resend_verification_code.php
{
  "email": "user@example.com"
}
```

---

### 10. `api/login.php`
**Purpose**: User login with email verification check
**Required**: ✅ YES
**Status**: ✅ Complete
**Features**:
- Validates credentials
- Checks if email is verified
- Blocks login if email not verified
- Returns verification requirement status

**Response if not verified**:
```json
{
  "error": "Email not verified",
  "requires_verification": true,
  "email": "user@example.com"
}
```

---

## 📦 Required Dependencies

### PHPMailer Library
**Location**: `vendor/phpmailer/phpmailer/`
**Installation**:
```bash
composer require phpmailer/phpmailer
```
OR manually download and place in `vendor/phpmailer/phpmailer/`

**Required Files**:
- `src/PHPMailer.php`
- `src/SMTP.php`
- `src/Exception.php`

---

## 🗄️ Database Tables

### Auto-Created Tables (No manual setup needed)

1. **`password_reset_otp`**
   - Created by: `api/send_otp_code.php`
   - Stores OTP codes for password reset
   - Auto-expires after 10 minutes

2. **`password_reset_tokens`**
   - Created by: `api/verify_otp.php`
   - Stores reset tokens after OTP verification
   - Auto-expires after 30 minutes

### Manual Setup Required

3. **`users` table columns** (if not exists):
   - `verification_code` VARCHAR(6)
   - `is_verified` TINYINT(1) DEFAULT 0

**SQL Script**: `sql/add_email_verification.sql` (if available)

---

## 🔄 Complete OTP Flow

### Password Reset Flow:
```
1. User requests OTP
   → api/send_otp_code.php
   → Generates OTP → Stores in DB → Sends Email

2. User enters OTP
   → api/verify_otp.php
   → Validates OTP → Generates Reset Token

3. User resets password
   → api/reset_password.php
   → Validates Token → Updates Password
```

### Account Verification Flow:
```
1. User registers
   → api/register.php
   → Creates Account → Sends Verification Email

2. User verifies email
   → api/verify.php
   → Validates Code → Marks as Verified

3. User can login
   → api/login.php
   → Checks Verification → Allows Login
```

---

## ✅ Setup Checklist

### Step 1: Install PHPMailer
- [ ] Run `composer install` OR
- [ ] Manually download PHPMailer to `vendor/phpmailer/phpmailer/`

### Step 2: Configure Email
- [ ] Enable Gmail 2-Step Verification
- [ ] Generate Gmail App Password
- [ ] Update `config/email_config.php` with App Password
- [ ] Replace `'your-app-password'` with actual password

### Step 3: Database Setup
- [ ] Ensure `users` table exists
- [ ] Add `verification_code` and `is_verified` columns (if needed)
- [ ] Tables `password_reset_otp` and `password_reset_tokens` will auto-create

### Step 4: Test Email Sending
- [ ] Test registration email
- [ ] Test OTP email
- [ ] Check spam folder
- [ ] Verify email delivery

---

## 🧪 Testing Endpoints

### Test OTP Sending:
```bash
POST /api/send_otp_code.php
Content-Type: application/json

{
  "email": "test@example.com",
  "username": "testuser"
}
```

### Test OTP Verification:
```bash
POST /api/verify_otp.php
Content-Type: application/json

{
  "user_id": 1,
  "otp_code": "123456"
}
```

### Test Password Reset:
```bash
POST /api/reset_password.php
Content-Type: application/json

{
  "token": "reset_token_here",
  "newPassword": "newpass123",
  "confirmPassword": "newpass123"
}
```

---

## 🔒 Security Features

✅ Cryptographically secure OTP generation (`random_int()`)  
✅ OTP expiration (10 minutes)  
✅ Single-use OTP codes  
✅ Secure reset tokens (64-char hex)  
✅ Token expiration (30 minutes)  
✅ Input validation and sanitization  
✅ XSS protection in email templates  
✅ SQL injection prevention (prepared statements)  
✅ Password hashing (bcrypt)  
✅ Error logging for security monitoring  

---

## 📝 File Summary

| File | Purpose | Required | Status |
|------|---------|----------|--------|
| `config/email_config.php` | Email configuration | ✅ | ✅ Ready |
| `config/phpmailer_helper.php` | Email helper functions | ✅ | ✅ Complete |
| `config/database.php` | Database connection | ✅ | ✅ Complete |
| `api/send_otp_code.php` | Send OTP email | ✅ | ✅ Complete |
| `api/verify_otp.php` | Verify OTP code | ✅ | ✅ Complete |
| `api/reset_password.php` | Reset password | ✅ | ✅ Complete |
| `api/register.php` | User registration | ✅ | ✅ Complete |
| `api/verify.php` | Email verification | ✅ | ✅ Complete |
| `api/resend_verification_code.php` | Resend code | ✅ | ✅ Complete |
| `api/login.php` | User login | ✅ | ✅ Complete |

---

## 🚀 All Files Are Ready!

All necessary files for OTP authentication are in place and properly configured. The system can:
- ✅ Send OTP codes via email
- ✅ Receive and verify OTP codes
- ✅ Send account verification emails
- ✅ Reset passwords securely
- ✅ Handle all error cases

**Only Action Required**: Update `config/email_config.php` with your Gmail App Password!








