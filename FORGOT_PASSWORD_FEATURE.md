# Forgot Password Feature

## Overview
A complete forgot password system that integrates with the existing change password functionality. Users can request a password reset via email/username, receive a secure reset link, and set a new password.

## Flow Diagram

```
Login Page → Forgot Password Link
    ↓
Forgot Password Page (enter email + username)
    ↓
Server generates reset token
    ↓
Reset Link sent (displayed in development)
    ↓
Reset Password Page (enter new password)
    ↓
Password updated successfully
    ↓
Redirect to Login Page
```

## Features

### 1. **Security Features**
- ✅ Secure token generation using `random_bytes()`
- ✅ Time-limited tokens (expires in 1 hour)
- ✅ Single-use tokens (marked as used after reset)
- ✅ Old tokens automatically cleaned up
- ✅ Token validation before allowing reset
- ✅ Passwords encrypted using `password_hash()`

### 2. **User Experience**
- ✅ Simple forgot password form
- ✅ Email and username verification
- ✅ Clear error messages
- ✅ Success confirmations
- ✅ Automatic redirect to login after reset
- ✅ Integration with existing change password feature

### 3. **Files Created**

#### Frontend Files:
- `forgot_password.html` - Page to request password reset
- `reset_password.html` - Page to enter new password

#### Backend Files:
- `api/send_reset_link.php` - Generates and stores reset tokens
- `api/reset_password.php` - Validates token and updates password

#### Database:
- `password_reset_tokens` table - Created automatically
  - Stores user_id, token, expires_at, used status

## How to Use

### For Users:

**Step 1: Request Password Reset**
1. Go to Login page
2. Click "Forgot Password?" link
3. Enter your email address
4. Enter your username
5. Click "Send Reset Link"

**Step 2: Click Reset Link**
- In development: The reset link is displayed in an alert
- In production: Link would be sent to email

**Step 3: Set New Password**
1. Click the reset link (opens reset page)
2. Enter new password (minimum 6 characters)
3. Confirm new password
4. Click "Reset Password"

**Step 4: Login with New Password**
1. Redirected to login page
2. Use your new password to login

## API Endpoints

### 1. Send Reset Link
**URL:** `api/send_reset_link.php`  
**Method:** POST  
**Authentication:** Not required

**Request Body:**
```json
{
  "email": "user@example.com",
  "username": "username"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Password reset link has been generated.",
  "reset_link": "http://.../reset_password.html?token=abc123",
  "token": "abc123"
}
```

### 2. Reset Password
**URL:** `api/reset_password.php`  
**Method:** POST  
**Authentication:** Token-based (via URL parameter)

**Request Body:**
```json
{
  "token": "abc123...",
  "newPassword": "newpassword123",
  "confirmPassword": "newpassword123"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Password has been reset successfully. You can now login with your new password."
}
```

**Error Responses:**
```json
{
  "error": "Invalid or expired reset token"
}
```

## Database Schema

The `password_reset_tokens` table is automatically created:

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

## Security Measures

1. **Token Generation**: Uses cryptographically secure random bytes
2. **Time Limitation**: Tokens expire after 1 hour
3. **Single Use**: Tokens marked as used after successful reset
4. **Automatic Cleanup**: Old and expired tokens are deleted
5. **User Verification**: Requires matching email and username
6. **Password Strength**: Minimum 6 characters
7. **Password Encryption**: Uses PHP's password_hash()

## Integration with Change Password

The forgot password feature integrates seamlessly with the existing "Change Password" feature:

- **Forgot Password**: For users who don't know their current password
- **Change Password**: For logged-in users who know their current password

Both features:
- Use the same password hashing algorithm
- Have the same password requirements
- Provide similar user experience
- Share common validation rules

## Development vs Production

### Development Mode (Current)
- Reset links are displayed in alerts
- Tokens are visible in responses
- Good for testing and demonstration

### Production Mode (To Implement)
1. Uncomment email sending in `send_reset_link.php`
2. Configure SMTP settings
3. Remove token display from responses
4. Update success messages to only reference email

**Example Email Configuration:**
```php
// In send_reset_link.php
mail($email, 'Password Reset Request', 
     "Click this link to reset your password: " . $resetLink);
```

## Testing

### Test Cases:
1. ✅ Request reset with valid email/username
2. ✅ Request reset with invalid email format
3. ✅ Use reset link before expiration
4. ✅ Attempt to use expired token
5. ✅ Attempt to use already-used token
6. ✅ Reset with mismatched passwords
7. ✅ Reset with password too short
8. ✅ Login with new password after reset

## Error Handling

Comprehensive error handling for:
- Missing fields
- Invalid email format
- Non-existent user
- Invalid token
- Expired token
- Already used token
- Password mismatch
- Password too short
- Database errors
- Network errors

## Future Enhancements

1. **Email Integration**: Send actual emails instead of displaying links
2. **Rate Limiting**: Prevent abuse of reset requests
3. **Security Questions**: Add extra verification layer
4. **Two-Factor Authentication**: Optional 2FA for password resets
5. **Password Strength Meter**: Visual indicator of password strength
6. **Recent Password Check**: Prevent reusing recent passwords

