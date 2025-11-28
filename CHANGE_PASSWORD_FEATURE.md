# Change Password Feature

## Overview
A complete change password functionality has been added to both Farmer and Buyer dashboards, allowing users to update their old password with a new one.

## Features

### 1. **Security Features**
- ✅ Requires current password verification
- ✅ Validates old password before allowing change
- ✅ Minimum password length: 6 characters
- ✅ Password confirmation matching
- ✅ Server-side validation
- ✅ Encrypted password storage (using password_hash)

### 2. **User Interface**
- ✅ Modal popup for changing password
- ✅ Available in both Farmer and Buyer dashboards
- ✅ Clean, user-friendly form
- ✅ Real-time error messages
- ✅ Responsive design

### 3. **Files Created/Modified**

#### New Files:
- `api/change_password.php` - Backend API endpoint for password changes

#### Modified Files:
- `farmer_dashboard.html` - Added "Change Password" link and modal
- `buyer_dashboard.html` - Added "Change Password" link and modal
- `script.js` - Added change password functionality

## How to Use

### For Users:
1. Log in to your Farmer or Buyer dashboard
2. Click on "Change Password" in the navigation menu
3. Enter your current password
4. Enter your new password (at least 6 characters)
5. Confirm your new password
6. Click "Change Password"
7. You'll see a success message when completed

### API Endpoint:
**URL:** `api/change_password.php`  
**Method:** POST  
**Authentication:** Required (Session-based)

**Request Body:**
```json
{
  "oldPassword": "current_password",
  "newPassword": "new_password",
  "confirmPassword": "new_password"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

**Error Responses:**
```json
{
  "error": "Current password is incorrect"
}
```
or
```json
{
  "error": "New passwords do not match"
}
```

## Security Considerations

1. **Old Password Verification**: The system verifies the current password before allowing any changes
2. **Session-based Authentication**: Users must be logged in to change their password
3. **Password Hashing**: All passwords are stored using PHP's `password_hash()` function
4. **Minimal Password Length**: Enforces at least 6 characters
5. **Password Confirmation**: Requires users to confirm their new password twice
6. **SQL Injection Protection**: Uses prepared statements

## Testing

To test the feature:
1. Register a new account
2. Log in to the dashboard
3. Click "Change Password" from the navigation
4. Try changing with incorrect old password (should fail)
5. Try changing with mismatched new passwords (should fail)
6. Try changing with valid information (should succeed)
7. Log out and log in with the new password (should work)

## Error Handling

The feature includes comprehensive error handling for:
- Missing fields
- Incorrect current password
- Passwords don't match
- New password too short
- User not logged in
- Database errors
- Network errors

