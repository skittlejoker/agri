# OTP Email Troubleshooting Guide

## 🚨 Problem: Not Receiving OTP Emails

### Step 1: Run Direct Email Test

Open this file in your browser:
```
http://localhost/E-commerce/agriculture-marketplace/test_capturephp
```

This will:
- Show you the EXACT error message
- Display SMTP debug output (the conversation with Gmail)
- Help identify the specific problem

### Step 2: Check Common Issues

#### Issue 1: Authentication Failed (Error 535/534)
**Symptoms:**
- Error message contains "Authentication failed" or "Invalid login"
- SMTP debug shows "535" or "534" error code

**Solutions:**
1. Verify app password is correct (no spaces)
2. Check 2-Step Verification is enabled: https://myaccount.google.com/security
3. Generate a NEW app password: https://myaccount.google.com/apppasswords
4. Make sure you're using the app password, NOT your regular Gmail password

#### Issue 2: Connection Failed
**Symptoms:**
- Error: "SMTP connect() failed"
- Error: "Connection refused" or "Connection timed out"

**Solutions:**
1. **Check Windows Firewall:**
   - Open Windows Firewall
   - Allow outbound connections on port 587 (or 465)
   - Or temporarily disable firewall to test

2. **Check Antivirus:**
   - Some antivirus software blocks SMTP connections
   - Temporarily disable to test

3. **Try Alternative Port:**
   - If port 587 doesn't work, try port 465 with SSL
   - Update `email_config.php`:
     ```php
     'smtp_port' => 465,
     'smtp_encryption' => 'ssl',
     ```
   - Test with: `test_email_alternative_port.php`

#### Issue 3: Email Sent But Not Received
**Symptoms:**
- Test shows "Email sent successfully"
- But email doesn't arrive in inbox or spam

**Solutions:**
1. **Wait 2-3 minutes** - Gmail can delay emails
2. **Check Spam/Junk folder** - Emails might be filtered
3. **Check Gmail Account Activity:**
   - Go to: https://myaccount.google.com/security
   - Look for "Recent security activity"
   - Check if there are blocked login attempts
   - If you see "Blocked sign-in attempt", click "Yes, it was me"

4. **Check Gmail Filters:**
   - Go to Gmail Settings → Filters and Blocked Addresses
   - Make sure emails from "trancem260@gmail.com" aren't being filtered

5. **Try Different Email Address:**
   - Test sending to a different email address
   - This helps identify if it's a Gmail-specific issue

### Step 3: Test Alternative Configuration

If port 587 (TLS) doesn't work, test port 465 (SSL):
```
http://localhost/E-commerce/agriculture-marketplace/test_email_alternative_port.php
```

If this works, update your `email_config.php`:
```php
'smtp_port' => 465,
'smtp_encryption' => 'ssl',
```

### Step 4: Check PHP Error Logs

Check these locations for detailed error messages:
- `C:\xampp\php\logs\php_error_log`
- `C:\xampp\apache\logs\error.log`

Look for lines containing "PHPMailer" or "SMTP"

### Step 5: Verify Gmail Settings

1. **2-Step Verification:**
   - Must be enabled for app passwords to work
   - Check: https://myaccount.google.com/security

2. **App Password:**
   - Must be generated specifically for "Mail"
   - Should be 16 characters (no spaces when entered)
   - Check: https://myaccount.google.com/apppasswords

3. **Account Security:**
   - Check for any security alerts
   - Make sure account isn't locked or restricted

### Step 6: Test Files Available

1. **test_direct_email.php** - Direct email test with detailed errors
2. **test_email_otp_complete.php** - Full diagnostic test
3. **test_email_alternative_port.php** - Test with port 465 (SSL)
4. **fix_otp_email.php** - Quick configuration check

### Step 7: Manual OTP Entry (Temporary Workaround)

If email sending fails but you need to test the system:
- The API now returns the OTP code in the response (for testing only)
- You can manually enter the code to test the verification flow
- **Remove this in production!**

## 🔧 Quick Fixes

### Fix 1: Update Port to 465
```php
// In email_config.php
'smtp_port' => 465,
'smtp_encryption' => 'ssl',
```

### Fix 2: Regenerate App Password
1. Go to: https://myaccount.google.com/apppasswords
2. Delete old "AgriMarket" password
3. Create new one
4. Copy password (remove spaces)
5. Update `email_config.php`

### Fix 3: Check Firewall
```powershell
# In PowerShell (Run as Administrator)
netsh advfirewall firewall add rule name="SMTP Outbound" dir=out action=allow protocol=TCP localport=587
```

### Fix 4: Increase Timeout
Already set to 60 seconds, but you can increase if needed:
```php
// In phpmailer_helper.php, line 140
$mail->Timeout = 90; // Increase to 90 seconds
```

## 📊 Debugging Checklist

- [ ] PHPMailer is installed and loaded
- [ ] Database connection works
- [ ] Email config file exists and is readable
- [ ] App password is set (no spaces)
- [ ] 2-Step Verification is enabled
- [ ] Port 587 is not blocked by firewall
- [ ] Antivirus is not blocking SMTP
- [ ] Gmail account is not locked/restricted
- [ ] Test email shows "sent successfully"
- [ ] Checked spam folder
- [ ] Waited 2-3 minutes for email
- [ ] Checked Gmail account activity

## 🆘 Still Not Working?

1. Run `test_direct_email.php` and copy the FULL error message
2. Check PHP error logs
3. Check Gmail account activity for blocked attempts
4. Try sending to a different email address
5. Try port 465 instead of 587
6. Verify app password is correct (regenerate if unsure)

## ✅ Success Indicators

When everything works:
- ✅ Test shows "Email sent successfully"
- ✅ Email arrives within 1-2 minutes
- ✅ Email is in inbox (not spam)
- ✅ OTP code in email matches generated code
- ✅ Code works when entered in verification form

---

**Remember:** The system now includes the OTP code in API responses for testing. Once email is working, you should remove this feature for security.






