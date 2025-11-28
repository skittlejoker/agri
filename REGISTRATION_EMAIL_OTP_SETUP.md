# Registration & Email Verification OTP Setup

## ✅ Configuration Complete

The registration and email verification system now uses the **same reliable email configuration** as `test_email_direct_capture.php` (port 465 SSL).

## 📧 Email Configuration

**File:** `config/email_config.php`
- **Port:** 465 (SSL) - Most reliable for Windows/XAMPP
- **Encryption:** SSL
- **Host:** smtp.gmail.com
- **Debug:** Enabled (level 2)

## 🔄 Complete User Flow

### 1. **Registration** (`api/register.php`)
- User registers with email, username, password
- System generates 6-digit verification code
- **Email is sent immediately** using reliable SMTP configuration
- User receives OTP code in their email inbox
- Response includes:
  - Success status
  - Email delivery confirmation
  - Gmail queuing status
  - Verification code (for testing if email fails)

### 2. **Email Verification** (`api/verify.php`)
- User enters the 6-digit code from their email
- System verifies the code matches
- Account is activated (`is_verified = 1`)
- User can now login

### 3. **Resend Verification Code** (`api/resend_verification_code.php`)
- If user didn't receive email, they can request a new code
- New 6-digit code is generated
- **Email is sent** using the same reliable configuration
- Includes Gmail delivery confirmation

### 4. **Login** (`api/login.php`)
- User attempts to login
- System checks if email is verified
- **If not verified:** Login is blocked with message "Email not verified"
- **If verified:** Login succeeds

## 🎯 Key Features

### ✅ Reliable Email Sending
- Uses port 465 SSL (same as working test file)
- Automatic space removal from app passwords
- Comprehensive debug output capture
- Gmail queuing verification
- Enhanced error messages

### ✅ Enhanced Error Handling
- Detailed SMTP debug output
- Gmail acceptance verification
- User-friendly error messages
- Fallback verification code display (for testing)

### ✅ Security
- 6-digit cryptographically secure random codes
- Email verification required before login
- Codes stored securely in database
- Codes cleared after successful verification

## 📝 Testing the Flow

### Test Registration:
1. Open registration page
2. Fill in all fields
3. Submit registration
4. **Check email inbox** (and spam folder)
5. You should receive an email with 6-digit code
6. Enter code on verification page
7. Account is activated

### Test Resend Code:
1. If email not received, click "Resend Code"
2. New code is generated and sent
3. Check email again

### Test Login:
1. Try to login before verification → Should fail with "Email not verified"
2. Verify email with code
3. Try to login again → Should succeed

## 🔧 Troubleshooting

### If emails are not received:

1. **Check Gmail Security Settings:**
   - Go to: https://myaccount.google.com/security
   - Look for "Blocked sign-in attempt" alerts
   - Click "Yes, it was me" to approve

2. **Verify App Password:**
   - Ensure 2-Step Verification is enabled
   - Generate new app password: https://myaccount.google.com/apppasswords
   - Update `config/email_config.php` with new password

3. **Check Email Configuration:**
   - Port should be 465
   - Encryption should be 'ssl'
   - App password should have no spaces

4. **Test Email Sending:**
   - Run: `http://localhost/E-commerce/agriculture-marketplace/test_email_direct_capture.php`
   - This uses the exact same configuration
   - If this works, registration emails will work too

5. **Check PHP Error Logs:**
   - Look in XAMPP error logs for SMTP debug output
   - Check for authentication errors (535, 534)
   - Check for connection errors

## 📁 Files Modified

1. **`api/register.php`**
   - Enhanced email sending with Gmail queuing verification
   - Better error messages
   - Includes verification code in response (for testing)

2. **`api/resend_verification_code.php`**
   - Enhanced email sending with delivery confirmation
   - Better error handling
   - Includes verification code in response (for testing)

3. **`config/phpmailer_helper.php`**
   - Already configured to use port 465 SSL
   - Comprehensive debug output
   - Gmail queuing verification

4. **`config/email_config.php`**
   - Port 465 SSL configuration
   - Debug enabled

## 🚀 Next Steps

1. **Test Registration Flow:**
   - Register a new user
   - Check email for verification code
   - Verify email
   - Login successfully

2. **Monitor Email Delivery:**
   - Check if emails arrive in inbox
   - Check spam folder
   - Approve any Gmail security alerts

3. **Production Considerations:**
   - Remove verification code from API responses (currently included for testing)
   - Set `smtp_debug` to 0 in production
   - Monitor error logs for email failures

## 📞 Support

If emails still don't arrive:
1. Run `test_email_direct_capture.php` to verify SMTP connection
2. Check Gmail security settings for blocked attempts
3. Verify app password is correct
4. Check PHP error logs for detailed SMTP debug output

---

**Status:** ✅ Ready for testing
**Configuration:** Port 465 SSL (same as working test file)
**Email Delivery:** Verified with Gmail queuing confirmation






