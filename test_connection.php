<?php

/**
 * Database Connection Test Script
 * 
 * Navigate to: http://localhost/E-commerce/agriculture-marketplace/test_connection.php
 */

?>
<!DOCTYPE html>
<html>

<head>
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .test-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        .info {
            color: #0066cc;
        }

        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }

        h1 {
            color: #333;
        }

        .button {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <h1>🔍 Database Connection Test</h1>

    <?php
    // Database configuration
    $host = 'localhost';
    $dbname = 'agrimarket';
    $username = 'root';
    $password = '';

    $tests = [];

    // Test 1: Check if MySQL is running
    $tests[] = [
        'name' => 'Check MySQL Server',
        'function' => function () use ($host, $username, $password) {
            try {
                $pdo = new PDO("mysql:host=$host", $username, $password);
                return ['status' => 'success', 'message' => 'MySQL is running'];
            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => 'MySQL is not running: ' . $e->getMessage()];
            }
        }
    ];

    // Test 2: Check if database exists
    $tests[] = [
        'name' => 'Check Database Exists',
        'function' => function () use ($host, $username, $password, $dbname) {
            try {
                $pdo = new PDO("mysql:host=$host", $username, $password);
                $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
                $result = $stmt->fetch();
                if ($result) {
                    return ['status' => 'success', 'message' => "Database '$dbname' exists"];
                } else {
                    return ['status' => 'error', 'message' => "Database '$dbname' does not exist"];
                }
            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            }
        }
    ];

    // Test 3: Check connection
    $tests[] = [
        'name' => 'Test Connection',
        'function' => function () use ($host, $username, $password, $dbname) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                return ['status' => 'success', 'message' => 'Connection successful'];
            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()];
            }
        }
    ];

    // Test 4: Check tables
    $tests[] = [
        'name' => 'Check Tables',
        'function' => function () use ($host, $username, $password, $dbname) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($tables) > 0) {
                    return ['status' => 'success', 'message' => 'Found ' . count($tables) . ' tables: ' . implode(', ', $tables)];
                } else {
                    return ['status' => 'error', 'message' => 'No tables found in database'];
                }
            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            }
        }
    ];

    // Test 5: Check users table structure
    $tests[] = [
        'name' => 'Check Users Table',
        'function' => function () use ($host, $username, $password, $dbname) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($columns) > 0) {
                    return ['status' => 'success', 'message' => 'Users table exists with columns: ' . implode(', ', $columns)];
                } else {
                    return ['status' => 'error', 'message' => 'Users table not found'];
                }
            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            }
        }
    ];

    // Test 6: Check products table
    $tests[] = [
        'name' => 'Check Products Table',
        'function' => function () use ($host, $username, $password, $dbname) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $stmt = $pdo->query("DESCRIBE products");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (count($columns) > 0) {
                    return ['status' => 'success', 'message' => 'Products table exists with columns: ' . implode(', ', $columns)];
                } else {
                    return ['status' => 'error', 'message' => 'Products table not found'];
                }
            } catch (PDOException $e) {
                return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
            }
        }
    ];

    // Run all tests
    foreach ($tests as $test) {
        echo '<div class="test-box">';
        echo '<h3>' . htmlspecialchars($test['name']) . '</h3>';

        try {
            $result = $test['function']();
            if ($result['status'] === 'success') {
                echo '<div class="success">✓ ' . htmlspecialchars($result['message']) . '</div>';
            } else {
                echo '<div class="error">✗ ' . htmlspecialchars($result['message']) . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">✗ Exception: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        echo '</div>';
    }

    // Show setup link if database doesn't exist
    echo '<div class="test-box">';
    echo '<h3>Quick Setup</h3>';
    echo '<p class="info">If the database is missing, click below to set it up:</p>';
    echo '<a href="setup_database.php" class="button">Setup Database Now</a>';
    echo '</div>';

    // Show PHP info
    echo '<div class="test-box">';
    echo '<h3>PHP Configuration</h3>';
    echo '<pre>';
    echo 'PHP Version: ' . phpversion() . "\n";
    echo 'PDO MySQL Available: ' . (extension_loaded('pdo_mysql') ? '✓ Yes' : '✗ No') . "\n";
    echo 'Session Save Path: ' . session_save_path() . "\n";
    echo 'Session Cookie: ' . ini_get('session.cookie_lifetime') . "\n";
    echo '</pre>';
    echo '</div>';
    ?>
</body>

</html>

