# Fix Database Error

If you're seeing "Database error occurred" messages, follow these steps:

## Step 1: Ensure MySQL is Running

1. Open XAMPP Control Panel
2. Make sure MySQL is running (green indicator)
3. If not running, click "Start" next to MySQL

## Step 2: Set Up the Database

You have two options:

### Option A: Use the Setup Script (Easiest)

1. Open your browser
2. Navigate to: `http://localhost/E-commerce/agriculture-marketplace/setup_database.php`
3. The script will automatically:
   - Create the database
   - Create all necessary tables
   - Add sample data
   - Show you the login credentials

### Option B: Manual Database Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a database
3. Name it: `agrimarket`
4. Select the database
5. Go to the "SQL" tab
6. Copy and paste the contents of `database.sql`
7. Click "Go" to execute

## Step 3: Verify Database Setup

Run this test script in your browser:
`http://localhost/E-commerce/agriculture-marketplace/test_connection.php`

Create the file `test_connection.php` with this content:

```php
<?php
$host = 'localhost';
$dbname = 'agrimarket';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✓ Database connection successful!";
    
    // Test query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<br><br>Tables found: " . implode(", ", $tables);
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage();
}
?>
```

## Step 4: Check Session Configuration

Make sure PHP sessions are configured properly in your `php.ini`:

```ini
session.save_handler = files
session.save_path = "C:/xampp/tmp"
session.use_cookies = 1
session.use_only_cookies = 1
```

To find your php.ini:
1. Create a file called `phpinfo.php` with: `<?php phpinfo(); ?>`
2. Open it in your browser
3. Look for "Loaded Configuration File"
4. Edit that file

## Step 5: Check File Permissions

Make sure the `config/` directory is readable:
```bash
Right-click config folder -> Properties -> Security -> Edit -> Allow "Read & Execute"
```

## Step 6: Clear Browser Cache

Sometimes old error messages can persist:
1. Press `Ctrl + Shift + Delete`
2. Select "Cached images and files"
3. Click "Clear data"

## Common Issues and Solutions

### Issue: "Table doesn't exist"
**Solution**: Run the setup script or manually import `database.sql`

### Issue: "Access denied"
**Solution**: Check that MySQL password is empty (default XAMPP), or update `config/database.php`

### Issue: "Connection refused"
**Solution**: Start MySQL in XAMPP Control Panel

### Issue: "PDO not found"
**Solution**: Enable PDO extension in php.ini:
```ini
extension=pdo_mysql
```

## Testing the Fix

After setup, try:

1. Log in as a farmer:
   - Username: `johnfarmer`
   - Password: `password`

2. Try adding a product with these details:
   - Name: carrots
   - Description: fresh carrots
   - Price: 111
   - Image URL: https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?w=400

3. The product should appear immediately in "My Products"

## Additional Help

If issues persist, check:
1. XAMPP error logs: `C:\xampp\apache\logs\error.log`
2. PHP error logs: Check the path shown in phpinfo.php
3. Browser console: F12 -> Console tab

## Quick Database Reset

If you want to start fresh:

```sql
DROP DATABASE IF EXISTS agrimarket;
CREATE DATABASE agrimarket;
USE agrimarket;
-- Then paste contents of database.sql
```


