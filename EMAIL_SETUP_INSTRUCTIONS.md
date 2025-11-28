# 🔧 Fix OTP Email Not Sending - Step by Step Guide

## ❌ Current Problem
OTP emails are not being sent to your inbox, preventing login.

## ✅ Solution Steps

### Step 1: Get Your Gmail App Password

1. **Go to Gmail App Passwords:**
   - Visit: https://myaccount.google.com/apppasswords
   - OR: Google Account → Security → 2-Step Verification → App passwords

2. **Enable 2-Step Verification (if not already enabled):**
   - Go to: https://myaccount.google.com/security
   - Enable "2-Step Verification"
   - Follow the setup process

3. **Generate App Password:**
   - Select "Mail" as the app
   - Select "Other (Custom name)" as device
   - Enter name: "AgriMarket"
   - Click "Generate"
   - **Copy the 16-character password** (it looks like: `abcd efgh ijkl mnop`)

### Step 2: Update Email Configuration

1. **Open the file:**
   ```
   agriculture-marketplace/config/email_config.php
   ```

2. **Find line 15:**
   ```php
   'smtp_password' => 'your-app-password',
   ```

3. **Replace with your App Password:**
   ```php
   'smtp_password' => 'abcdefghijklmnop',  // Your 16-char App Password (remove spaces)
   ```
   
   **Important:** Remove any spaces from the App Password!

### Step 3: Test Email Configuration

1. **Open in browser:**
   ```
   http://localhost/E-commerce/agriculture-marketplace/test_email.php
   ```

2. **Check the output:**
   - ✅ Green checkmarks = Working!
   - ❌ Red X = Still needs fixing

3. **If test fails:**
   - Check the error message
   - Verify App Password is correct
   - Make sure 2-Step Verification is enabled
   - Check PHP error logs

### Step 4: Disable Debug Mode (After Testing)

Once emails are working:

1. **Open:** `config/email_config.php`
2. **Change line 19:**
   ```php
   'smtp_debug' => 0,  // Change from 2 back to 0
   ```

### Step 5: Test OTP Sending

1. **Try forgot password:**
   - Go to forgot password page
   - Enter your email and username
   - Click "Send Verification Code"
   - Check your inbox (and spam folder)

2. **If still not working:**
   - Check PHP error logs
   - Verify PHPMailer is installed
   - Check firewall/antivirus settings

---

## 🔍 Common Errors & Solutions

### Error: "Authentication failed" or "Invalid login"
**Solution:** 
- App Password is incorrect
- Make sure you're using App Password, not regular password
- Regenerate App Password and try again

### Error: "SMTP connect() failed"
**Solution:**
- Check internet connection
- Verify firewall isn't blocking port 587
- Try port 465 with SSL instead of TLS

### Error: "PHPMailer is not installed"
**Solution:**
```bash
cd agriculture-marketplace
composer install
```

### Error: "Email password not configured"
**Solution:**
- You still have `'your-app-password'` in email_config.php
- Replace it with your actual App Password

---

## 📧 Quick Checklist

- [ ] 2-Step Verification enabled on Gmail
- [ ] App Password generated
- [ ] App Password copied (16 characters)
- [ ] email_config.php updated with App Password
- [ ] test_email.php shows success
- [ ] OTP email received in inbox
- [ ] Debug mode set back to 0

---

## 🆘 Still Not Working?

1. **Check PHP Error Logs:**
   - Location: `C:\xampp\php\logs\php_error_log`
   - Look for PHPMailer errors

2. **Enable More Debugging:**
   - Set `smtp_debug => 2` in email_config.php
   - Try sending OTP again
   - Check error logs for detailed SMTP conversation

3. **Verify PHPMailer:**
   - Check if `vendor/phpmailer/phpmailer/src/PHPMailer.php` exists
   - If not, run: `composer install`

4. **Test with Different Email:**
   - Try sending to a different email address
   - Check if it's a Gmail-specific issue

---

## ✅ Success Indicators

When it's working, you should see:
- ✅ Test email script shows all green checkmarks
- ✅ OTP email arrives in inbox within 30 seconds
- ✅ Email subject: "AgriMarket - Password Reset Verification Code"
- ✅ Email contains 6-digit OTP code
- ✅ No errors in PHP error logs

---

**Need Help?** Check the error message returned by the API - it will tell you exactly what's wrong!








