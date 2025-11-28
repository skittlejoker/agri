# AgriMarket PHP Backend Setup

## Database Setup

1. **Start XAMPP** and ensure MySQL is running
2. **Open phpMyAdmin** (http://localhost/phpmyadmin)
3. **Import the database**:
   - Click "Import" tab
   - Choose file: `database.sql`
   - Click "Go" to execute

## File Structure

```
agriculture-marketplace/
├── api/
│   ├── login.php          # Login endpoint
│   ├── register.php       # Registration endpoint
│   ├── logout.php         # Logout endpoint
│   └── check_session.php  # Session validation
├── config/
│   └── database.php       # Database configuration
├── database.sql           # Database schema
├── script-php.js         # Updated JS for PHP backend
└── [existing HTML files]
```

## Switching to PHP Backend

To use the PHP backend instead of localStorage:

1. **Update HTML files** to use `script-php.js` instead of `script.js`:
   ```html
   <!-- Change this line in all HTML files -->
   <script src="script-php.js"></script>
   ```

2. **Access via XAMPP**:
   - URL: `http://localhost/agriculture-marketplace/`
   - Or: `http://localhost/E-commerce/agriculture-marketplace/`

## API Endpoints

### POST /api/register.php
```json
{
  "fullName": "John Doe",
  "email": "john@example.com",
  "username": "johndoe",
  "password": "password123",
  "confirmPassword": "password123",
  "userType": "farmer"
}
```

### POST /api/login.php
```json
{
  "username": "johndoe",
  "password": "password123",
  "userType": "farmer"
}
```

### GET /api/check_session.php
Returns current session status and user info.

### GET /api/logout.php
Destroys current session.

## Sample Data

The database includes sample users:
- **Farmer**: username: `johnfarmer`, password: `password`
- **Buyer**: username: `janebuyer`, password: `password`

## Security Notes

- Passwords are hashed using PHP's `password_hash()`
- Sessions are used for authentication
- Input validation on all endpoints
- SQL injection protection via prepared statements

## Troubleshooting

1. **Database connection error**: Check XAMPP MySQL is running
2. **404 errors**: Ensure files are in correct XAMPP directory
3. **Permission errors**: Check file permissions in XAMPP
4. **Session issues**: Clear browser cookies/cache
