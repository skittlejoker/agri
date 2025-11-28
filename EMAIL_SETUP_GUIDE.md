# Email Setup Guide for Forgot Password Feature

## Overview
This guide will help you configure your Gmail account (trancem260@gmail.com) to send verification codes for the forgot password feature.

## Step 1: Enable 2-Step Verification (Required)

1. Go to your Google Account settings: https://myaccount.google.com/
2. Navigate to **Security** tab
3. Under **How you sign in to Google**, click on **2-Step Verification**
4. Follow the prompts to enable 2-Step Verification
5. Complete the verification process

## Step 2: Generate App Password

1. Once 2-Step Verification is enabled, go back to **Security** tab
2. Under **2-Step Verification**, click on **App passwords**
3. Select **Mail** as the app
4. Select **Other (Custom name)** as the device
5. Enter "AgriMarket System" as the name
6. Click **Generate**
7. Copy the 16-character password (it will look like: `abcd efgh ijkl mnop`)

## Step 3: Update Email Configuration

Open the file: `agriculture-marketplace/config/email_config.php`

Replace `'your-app-password'` with your generated app password (remove spaces):

```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'trancem260@gmail.com',
    'smtp_password' => 'abcdefghijklmnop', // Your 16-character app password
    'smtp_from_email' => 'trancem260@gmail.com',
    'smtp_from_name' => 'AgriMarket - Agriculture Platform',
    'smtp_encryption' => 'tls'
];
?>
```

## Step 4: Test Email Sending

### Test in Development
1. Navigate to: `http://localhost/E-commerce/agriculture-marketplace/forgot_password.html`
2. Enter your email and username
3. Click "Send Verification Code"
4. Check your email inbox for the 6-digit code
5. Enter the code and reset your password

### Check Spam Folder
If you don't receive the email immediately, check your spam/junk folder.

## Troubleshooting

### Issue: Emails Not Sending
**Solution:** Check the following:
1. Ensure 2-Step Verification is enabled on Gmail
2. App password is correctly entered (no spaces)
3. Port 587 is not blocked by firewall
4. Check PHP error logs for detailed error messages

### Issue: "Authentication failed"
**Solution:** 
- Regenerate app password
- Make sure you're using the app password, not your regular Gmail password

### Issue: Emails Going to Spam
**Solution:**
1. Add trancem260@gmail.com to your contacts
2. Mark emails as "Not Spam"

## Security Notes

1. **Never commit your app password to version control**
2. Keep `email_config.php` in `.gitignore` if using Git
3. For production, consider using a dedicated email service like SendGrid or Mailgun
4. Rate limit email sending to prevent abuse

## Production Recommendations

### Option 1: Use Dedicated Email Service
- **SendGrid**: Free tier allows 100 emails/day
- **Mailgun**: Free tier allows 5,000 emails/month
- **Amazon SES**: Very affordable, pay per email

### Option 2: Use PHPMailer
For more robust email delivery, consider using PHPMailer library:

1. Install PHPMailer via Composer:
```bash
composer require phpmailer/phpmailer
```

2. Update `send_otp_code.php` to use PHPMailer instead of `mail()`

## How It Works

### Flow:
1. User enters email and username
2. System generates 6-digit OTP code
3. OTP saved to database (expires in 10 minutes)
4. Email sent to user with OTP code
5. User enters OTP code
6. System verifies OTP and issues reset token
7. User creates new password
8. Password updated in database

### Security Features:
- ✅ OTP codes expire in 10 minutes
- ✅ Codes are randomly generated (6 digits)
- ✅ Single-use verification
- ✅ Rate limiting (one code at a time per user)
- ✅ Secure password hashing

## Database Tables

The system automatically creates these tables:

```sql
-- Stores OTP codes
CREATE TABLE password_reset_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Stores reset tokens (after OTP verification)
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

## Testing Checklist

- [ ] Gmail app password generated
- [ ] Email config updated
- [ ] Email sends successfully
- [ ] OTP code received in inbox
- [ ] Code verification works
- [ ] Password reset succeeds
- [ ] Can login with new password
- [ ] Old codes expire properly
- [ ] Rate limiting works
- [ ] Invalid codes are rejected

## Support

If you encounter issues:
1. Check PHP error logs
2. Verify email configuration
3. Test with a simple PHP mail script
4. Check Gmail security settings

