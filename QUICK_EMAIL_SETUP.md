# 🚀 Quick Email Setup - 2 Minutes!

## Option 1: Use Interactive Setup Wizard (Easiest!)

1. **Open in browser:**
   ```
   http://localhost/E-commerce/agriculture-marketplace/setup_email.php
   ```

2. **Follow the on-screen instructions:**
   - Click the link to get your Gmail App Password
   - Copy the 16-character password
   - Paste it in the form
   - Click "Save Configuration & Test"

3. **Done!** ✅

---

## Option 2: Manual Setup

1. **Get Gmail App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Generate password for "Mail"
   - Copy the 16-character password

2. **Edit file:** `config/email_config.php`

3. **Find line 15 and replace:**
   ```php
   'smtp_password' => 'your-app-password',
   ```
   **With:**
   ```php
   'smtp_password' => 'your-16-char-password-here',
   ```
   (Remove spaces from the password!)

4. **Test:** Open `test_email.php` in browser

---

## ✅ Verify It's Working

1. Go to: `http://localhost/E-commerce/agriculture-marketplace/test_email.php`
2. Should show all green checkmarks ✅
3. Check your email inbox for test email

---

## 🆘 Need Help?

- Check `EMAIL_SETUP_INSTRUCTIONS.md` for detailed guide
- Check PHP error logs if emails still don't send
- Make sure 2-Step Verification is enabled on Gmail








