<?php

/**
 * Check Gmail Blocking - Verify if Gmail is blocking your emails
 * This will help diagnose why emails aren't arriving
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Check Gmail Blocking</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { background: white; padding: 30px; border-radius: 10px; max-width: 1000px; margin: 0 auto; }
h1 { color: #2e8b57; }
.success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
.info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #004085; }
.step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #6c757d; border-radius: 5px; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Check Gmail Blocking - Diagnostic Guide</h1>";

echo "<div class='info'>";
echo "<strong>If emails show 'sent successfully' but don't arrive, Gmail might be blocking them.</strong><br>";
echo "Follow these steps to diagnose and fix the issue.";
echo "</div>";

echo "<h2>Step 1: Check Gmail Security Activity</h2>";
echo "<div class='step'>";
echo "<strong>1. Go to Gmail Security:</strong><br>";
echo "<a href='https://myaccount.google.com/security' target='_blank' style='background: #2e8b57; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0;'>Open Gmail Security Settings</a><br><br>";
echo "<strong>2. Scroll to &quot;Recent security activity&quot;</strong><br>";
echo "<strong>3. Look for:</strong><br>";
echo "• 'Blocked sign-in attempt'<br>";
echo "• 'New sign-in from Windows'<br>";
echo "• Any security alerts<br><br>";
echo "<strong>4. If you see blocked attempts:</strong><br>";
echo "• Click on the alert<br>";
echo "• Click 'Yes, it was me' to approve<br>";
echo "• This will allow future emails to be sent<br>";
echo "</div>";

echo "<h2>Step 2: Check App Password</h2>";
echo "<div class='step'>";
echo "<strong>1. Verify App Password is correct:</strong><br>";
echo "• Go to: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a><br>";
echo "• Make sure 'AgriMarket' app password exists<br>";
echo "• If unsure, delete and create a new one<br><br>";
echo "<strong>2. Verify 2-Step Verification is enabled:</strong><br>";
echo "• App passwords only work with 2-Step Verification<br>";
echo "• Check: <a href='https://myaccount.google.com/security' target='_blank'>Security Settings</a><br>";
echo "</div>";

echo "<h2>Step 3: Check Email Filters</h2>";
echo "<div class='step'>";
echo "<strong>1. Check Gmail Filters:</strong><br>";
echo "• Go to Gmail Settings → Filters and Blocked Addresses<br>";
echo "• Make sure emails from 'trancem260@gmail.com' aren't being filtered<br><br>";
echo "<strong>2. Check Spam Folder:</strong><br>";
echo "• Emails might be going to spam<br>";
echo "• Mark as 'Not Spam' if found<br>";
echo "</div>";

echo "<h2>Step 4: Try Alternative Port (465 SSL)</h2>";
echo "<div class='step'>";
echo "<strong>If port 587 doesn't work, try port 465 with SSL:</strong><br>";
echo "1. Open <code>config/email_config.php</code><br>";
echo "2. Change these lines:<br>";
echo "<pre>'smtp_port' => 465,\n'smtp_encryption' => 'ssl',</pre>";
echo "3. Save and test again<br>";
echo "</div>";

echo "<h2>Step 5: Test Email Sending</h2>";
echo "<div class='step'>";
echo "<strong>After fixing the issues above:</strong><br>";
echo "1. Run the simple test: <a href='simple_email_test.php'>simple_email_test.php</a><br>";
echo "2. Check the SMTP conversation output<br>";
echo "3. Look for '250 2.0.0 OK' after DATA command (means Gmail queued it)<br>";
echo "4. Check your inbox and spam folder<br>";
echo "</div>";

echo "<h2>Common Issues & Solutions</h2>";

echo "<div class='warning'>";
echo "<strong>Issue: Authentication Failed (535/534 errors)</strong><br>";
echo "Solution:<br>";
echo "• Regenerate app password<br>";
echo "• Make sure 2-Step Verification is enabled<br>";
echo "• Verify password has no spaces in config file<br>";
echo "</div>";

echo "<div class='warning'>";
echo "<strong>Issue: Connection Timeout</strong><br>";
echo "Solution:<br>";
echo "• Check Windows Firewall allows port 587 (or 465)<br>";
echo "• Check antivirus isn't blocking SMTP<br>";
echo "• Try port 465 with SSL instead<br>";
echo "</div>";

echo "<div class='warning'>";
echo "<strong>Issue: Email Sent But Not Received</strong><br>";
echo "Solution:<br>";
echo "• Check Gmail security for blocked sign-in attempts<br>";
echo "• Check spam folder<br>";
echo "• Wait 2-3 minutes (Gmail can delay)<br>";
echo "• Try sending to a different email address<br>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Quick Links:</strong><br>";
echo "• <a href='simple_email_test.php'>Simple Email Test</a><br>";
echo "• <a href='test_email_with_verification.php'>Email Test with Verification</a><br>";
echo "• <a href='https://myaccount.google.com/security' target='_blank'>Gmail Security Settings</a><br>";
echo "• <a href='https://myaccount.google.com/apppasswords' target='_blank'>App Passwords</a><br>";
echo "</div>";

echo "</div></body></html>";
