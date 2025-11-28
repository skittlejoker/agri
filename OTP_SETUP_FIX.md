# OTP Email Setup - Complete Fix Guide

## 🔧 Quick Fix Steps

### Step 1: Verify Your Gmail App Password

1. **Go to Google App Passwords**: https://myaccount.google.com/apppasswords
2. **Find your "AgriMarket" app password** (or create a new one)
3. **Copy the password** - it will look like: `xxxx xxxx xxxx xxxx` (4 groups of 4 characters)
4. **IMPORTANT**: Remove ALL spaces when copying to the config file
   - ❌ Wrong: `yzfa utie mzkl lafy`
   - ✅ Correct: `yzfautiemzkllafy`

### Step 2: Update email_config.php

Open `config/email_config.php` and make sure:

```php
'smtp_password' => 'YOUR_16_CHAR_PASSWORD_WITH_NO_SPACES',
```

**Example:**
- If Google shows: `yzfa utie mzkl lafy`
- Enter in config: `yzfautiemzkllafy` (no spaces!)

### Step 3: Test the Setup

1. **Open the test page**: Navigate to `test_email_otp_complete.php` in your browser
   - Example: `http://localhost/E-commerce/agriculture-marketplace/test_email_direct_capture.php`

2. **Run the diagnostic test** - it will check:
   - ✅ Database connection
   - ✅ PHPMailer installation
   - ✅ Email configuration
   - ✅ Test email sending

3. **Click "Send Test OTP Email"** button

4. **Check your email inbox** (and spam folder)

### Step 4: Common Issues & Solutions

#### Issue: "Authentication failed" or "Invalid login"
**Solution:**
- Verify the app password is correct (no spaces)
- Make sure 2-Step Verification is enabled on Gmail
- Try regenerating the app password

#### Issue: "SMTP connect() failed"
**Solution:**
- Check if port 587 is blocked by firewall
- Try using port 465 with SSL instead:
  ```php
  'smtp_port' => 465,
  'smtp_encryption' => 'ssl',
  ```

#### Issue: "PHPMailer not found"
**Solution:**
- Run: `composer install` in the project directory
- Or manually download PHPMailer and place in `PHPMailer/` folder

#### Issue: Email sent but not received
**Solution:**
- Check spam/junk folder
- Wait a few minutes (Gmail can delay)
- Verify the recipient email address is correct
- Check Gmail account activity for blocked login attempts

### Step 5: Verify Database Tables

The system will automatically create these tables, but you can verify:

```sql
-- Check if tables exist
SHOW TABLES LIKE 'password_reset_otp';
SHOW TABLES LIKE 'password_reset_tokens';
SHOW TABLES LIKE 'users';
```

### Step 6: Test the Full Flow

1. Go to `forgot_password.html`
2. Enter a registered email and username
3. Check email for OTP code
4. Enter the code to verify
5. Reset your password

## 🐛 Debugging

### Check PHP Error Logs

Look in your PHP error log (usually in XAMPP: `C:\xampp\php\logs\php_error_log` or `C:\xampp\apache\logs\error.log`)

### Enable Verbose Debugging

In `email_config.php`, set:
```php
'smtp_debug' => 2, // Shows detailed SMTP communication
```

Then check the error logs for detailed SMTP output.

### Test Direct Email Sending

Use `test_otp_send.php` or `test_email_otp_complete.php` to test email sending directly without going through the full OTP flow.

## ✅ Success Indicators

When everything works, you should see:
- ✅ Test email page shows "Email sent successfully"
- ✅ OTP email arrives in your inbox within 1-2 minutes
- ✅ Email contains a 6-digit code in a styled box
- ✅ Code works when entered in the verification form

## 📞 Still Having Issues?

1. Run the complete diagnostic test: `test_email_direct_capture.php`
2. Check PHP error logs
3. Verify Gmail account settings (2-Step Verification enabled)
4. Try creating a fresh app password
5. Check firewall/antivirus settings for port 587

---

**Note**: The system now automatically removes spaces from app passwords, but it's still best practice to enter them without spaces in the config file.






