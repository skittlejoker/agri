<?php
// Start output buffering
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['user_id']) || !isset($input['otp_code'])) {
    ob_clean();
    error_log("verify_otp.php: Missing user_id or otp_code. Input: " . json_encode($input));
    sendResponse(['error' => 'User ID and OTP code are required'], 400);
}

$userId = intval($input['user_id']);
$rawInputCode = $input['otp_code'];

// Log raw input for debugging
error_log("verify_otp.php: Raw input - user_id: {$userId}, raw_otp_code: '" . var_export($rawInputCode, true) . "' (type: " . gettype($rawInputCode) . ", length: " . strlen($rawInputCode) . ")");

// Normalize OTP code - remove ALL non-digit characters including spaces, dashes, etc.
// This handles cases where users copy from email with formatting
$otpCode = preg_replace('/[^\d]/', '', $rawInputCode); // Remove ALL non-digits (more aggressive)
$otpCode = trim($otpCode); // Remove any remaining whitespace
$otpCode = preg_replace('/\D/', '', $otpCode); // Double-check: remove all non-digits again
$otpCode = str_pad($otpCode, 6, '0', STR_PAD_LEFT); // Pad to 6 digits

// Log the normalized code
error_log("verify_otp.php: Normalized code - '{$otpCode}' (length: " . strlen($otpCode) . ", bytes: " . bin2hex($otpCode) . ")");

// Validate OTP code format (must be exactly 6 digits after normalization)
if (!preg_match('/^\d{6}$/', $otpCode) || strlen($otpCode) !== 6) {
    ob_clean();
    error_log("verify_otp.php: Invalid OTP format after normalization - '{$otpCode}'");
    sendResponse(['error' => 'Invalid OTP code format. Must be 6 digits.'], 400);
}

// Validate user ID
if ($userId <= 0) {
    ob_clean();
    error_log("verify_otp.php: Invalid user_id - {$userId}");
    sendResponse(['error' => 'Invalid user ID'], 400);
}

try {
    // Check database column type to ensure it's VARCHAR
    $columnType = 'VARCHAR';
    try {
        $columnInfoStmt = $pdo->prepare("SHOW COLUMNS FROM password_reset_otp WHERE Field = 'otp_code'");
        $columnInfoStmt->execute();
        $columnInfo = $columnInfoStmt->fetch(PDO::FETCH_ASSOC);
        if ($columnInfo) {
            $columnType = $columnInfo['Type'];
            error_log("verify_otp.php: OTP column type: {$columnType}, Null: {$columnInfo['Null']}, Default: {$columnInfo['Default']}");

            // If column is INT, we need to handle it differently
            if (stripos($columnType, 'int') !== false) {
                error_log("verify_otp.php: ⚠️ WARNING - Column is INT type, not VARCHAR. This may cause comparison issues.");
            }
        }
    } catch (Exception $e) {
        error_log("verify_otp.php: Could not check column info: " . $e->getMessage());
    }

    // First, get ALL recent OTPs for this user (including expired/verified) for debugging
    $allRecentStmt = $pdo->prepare("SELECT id, otp_code, verified, expires_at, created_at, 
                                     LENGTH(otp_code) as code_length,
                                     HEX(otp_code) as code_hex,
                                     BINARY otp_code as code_binary
                                     FROM password_reset_otp 
                                     WHERE user_id = ? 
                                     ORDER BY created_at DESC 
                                     LIMIT 10");
    $allRecentStmt->execute([$userId]);
    $allRecentOtps = $allRecentStmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("verify_otp.php: Found " . count($allRecentOtps) . " recent OTP records for user {$userId}");

    // Log all recent OTPs for debugging
    foreach ($allRecentOtps as $idx => $otp) {
        $rawCode = trim($otp['otp_code']);
        $normalizedCode = str_pad(preg_replace('/\D/', '', $rawCode), 6, '0', STR_PAD_LEFT);
        error_log("verify_otp.php: OTP #{$idx} - ID: {$otp['id']}, Raw: '{$rawCode}' (len:{$otp['code_length']}, hex:{$otp['code_hex']}), Normalized: '{$normalizedCode}', Verified: {$otp['verified']}, Expires: {$otp['expires_at']}");
    }

    // Get all valid (unverified and not expired) OTPs for this user
    $allValidStmt = $pdo->prepare("SELECT id, otp_code, verified, expires_at, created_at 
                                    FROM password_reset_otp 
                                    WHERE user_id = ? 
                                    AND verified = 0 
                                    AND expires_at > NOW() 
                                    ORDER BY created_at DESC");
    $allValidStmt->execute([$userId]);
    $allValidOtps = $allValidStmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("verify_otp.php: Found " . count($allValidOtps) . " valid (unverified and not expired) OTP records for user {$userId}");

    $otpData = null;
    $currentTime = time();
    $inputCodeInt = intval($otpCode);

    // SIMPLIFIED DIRECT APPROACH: Get most recent OTP and compare
    error_log("verify_otp.php: ========================================");
    error_log("verify_otp.php: VERIFICATION START - User ID: {$userId}, Input: '{$otpCode}' (len:" . strlen($otpCode) . ", int:{$inputCodeInt})");
    error_log("verify_otp.php: Found " . count($allValidOtps) . " valid OTPs, " . count($allRecentOtps) . " recent OTPs");

    // Get the MOST RECENT OTP for this user (regardless of expiry/verification status)
    $stmt = $pdo->prepare("SELECT * FROM password_reset_otp 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT 1");
    $stmt->execute([$userId]);
    $latestOtp = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($latestOtp) {
        $latestRaw = trim($latestOtp['otp_code']);
        $latestRaw = preg_replace('/[\x00-\x1F\x7F]/', '', $latestRaw);
        $latestNormalized = preg_replace('/[^\d]/', '', $latestRaw);
        $latestNormalized = str_pad($latestNormalized, 6, '0', STR_PAD_LEFT);
        $latestInt = intval($latestNormalized);

        error_log("verify_otp.php: Latest OTP - ID:{$latestOtp['id']}, Raw:'{$latestRaw}', Normalized:'{$latestNormalized}', Verified:{$latestOtp['verified']}, Expires:{$latestOtp['expires_at']}");

        // Check if code matches
        $codeMatches = ($latestNormalized === $otpCode) ||
            ($latestRaw === $otpCode) ||
            ($latestInt === $inputCodeInt && $latestInt > 0) ||
            (ltrim($latestNormalized, '0') === ltrim($otpCode, '0') && ltrim($latestNormalized, '0') !== '');

        if ($codeMatches) {
            // Code matches! Now check if it's valid
            $expiresAt = strtotime($latestOtp['expires_at']);
            $isValid = ($expiresAt > $currentTime);
            $notVerified = ($latestOtp['verified'] == 0);

            if (!$notVerified) {
                error_log("verify_otp.php: Code matches but already verified");
                ob_clean();
                sendResponse(['error' => 'This verification code has already been used. Please request a new code.'], 400);
            } elseif (!$isValid) {
                $expiredMinutes = round(($currentTime - $expiresAt) / 60);
                error_log("verify_otp.php: Code matches but expired {$expiredMinutes} minutes ago");
                ob_clean();
                sendResponse(['error' => 'Verification code has expired. Please request a new code.'], 400);
            } else {
                // Code matches and is valid!
                $otpData = $latestOtp;
                error_log("verify_otp.php: ✅✅✅ MATCH FOUND - ID: {$latestOtp['id']}, Code: '{$latestNormalized}'");
            }
        } else {
            error_log("verify_otp.php: Code does NOT match - Latest: '{$latestNormalized}', Input: '{$otpCode}'");
        }
    } else {
        error_log("verify_otp.php: No OTP found for user {$userId}");
    }

    // If no match with latest, try all valid OTPs
    if (!$otpData && !empty($allValidOtps)) {
        error_log("verify_otp.php: Trying all valid OTPs...");
        foreach ($allValidOtps as $dbOtp) {
            $rawStored = trim($dbOtp['otp_code']);
            $rawStored = preg_replace('/[\x00-\x1F\x7F]/', '', $rawStored);
            $storedNormalized = preg_replace('/[^\d]/', '', $rawStored);
            $storedNormalized = str_pad($storedNormalized, 6, '0', STR_PAD_LEFT);

            if ($storedNormalized === $otpCode || $rawStored === $otpCode || intval($storedNormalized) === $inputCodeInt) {
                $otpData = $dbOtp;
                error_log("verify_otp.php: ✅✅✅ MATCH FOUND in valid OTPs - ID: {$dbOtp['id']}");
                break;
            }
        }
    }

    // Final SQL fallback
    if (!$otpData) {
        $stmt = $pdo->prepare("SELECT * FROM password_reset_otp 
                               WHERE user_id = ? 
                               AND (otp_code = ? OR TRIM(otp_code) = ? OR CAST(otp_code AS UNSIGNED) = ?)
                               AND verified = 0 
                               AND expires_at > NOW()
                               ORDER BY created_at DESC
                               LIMIT 1");
        $stmt->execute([$userId, $otpCode, $otpCode, $inputCodeInt]);
        $otpData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($otpData) {
            error_log("verify_otp.php: ✅✅✅ MATCH FOUND via SQL fallback - ID: {$otpData['id']}");
        }
    }

    // If no match in valid OTPs, check all recent OTPs (for better error messages)
    if (!$otpData) {
        foreach ($allRecentOtps as $dbOtp) {
            $rawStoredCode = trim($dbOtp['otp_code']);
            $rawStoredCode = preg_replace('/[\x00-\x1F\x7F]/', '', $rawStoredCode);
            $storedCodeNormalized = str_pad(preg_replace('/[^\d]/', '', $rawStoredCode), 6, '0', STR_PAD_LEFT);

            if ($storedCodeNormalized === $otpCode) {
                // Code matches but might be expired or verified
                $expiresAt = strtotime($dbOtp['expires_at']);
                $isValid = ($expiresAt > $currentTime);
                $notVerified = ($dbOtp['verified'] == 0);

                if (!$notVerified) {
                    error_log("verify_otp.php: Code found but already verified - ID: {$dbOtp['id']}");
                    ob_clean();
                    sendResponse(['error' => 'This verification code has already been used. Please request a new code.'], 400);
                } elseif (!$isValid) {
                    $expiredMinutes = round(($currentTime - $expiresAt) / 60);
                    error_log("verify_otp.php: Code found but expired - ID: {$dbOtp['id']}, Expired {$expiredMinutes} minutes ago");
                    ob_clean();
                    sendResponse(['error' => 'Verification code has expired. Please request a new code.'], 400);
                }
            }
        }
    }

    // If still no match, provide helpful error message
    if (!$otpData) {
        // Method 1: Direct exact match
        $stmt = $pdo->prepare("SELECT * FROM password_reset_otp 
                               WHERE user_id = ? 
                               AND otp_code = ? 
                               AND verified = 0 
                               AND expires_at > NOW()
                               ORDER BY created_at DESC
                               LIMIT 1");
        $stmt->execute([$userId, $otpCode]);
        $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpData) {
            error_log("verify_otp.php: ✅ MATCH FOUND via SQL exact match - ID: {$otpData['id']}, Code: '{$otpCode}'");
        } else {
            // Method 2: Try with input as integer (in case DB stores as number)
            if ($inputCodeInt > 0) {
                $stmt = $pdo->prepare("SELECT * FROM password_reset_otp 
                                       WHERE user_id = ? 
                                       AND CAST(otp_code AS UNSIGNED) = ? 
                                       AND verified = 0 
                                       AND expires_at > NOW()
                                       ORDER BY created_at DESC
                                       LIMIT 1");
                $stmt->execute([$userId, $inputCodeInt]);
                $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($otpData) {
                    error_log("verify_otp.php: ✅ MATCH FOUND via SQL integer cast - ID: {$otpData['id']}, Code: '{$otpCode}'");
                }
            }

            // Method 3: Try with TRIM (handles whitespace)
            if (!$otpData) {
                $stmt = $pdo->prepare("SELECT * FROM password_reset_otp 
                                       WHERE user_id = ? 
                                       AND TRIM(otp_code) = ? 
                                       AND verified = 0 
                                       AND expires_at > NOW()
                                       ORDER BY created_at DESC
                                       LIMIT 1");
                $stmt->execute([$userId, $otpCode]);
                $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($otpData) {
                    error_log("verify_otp.php: ✅ MATCH FOUND via SQL TRIM - ID: {$otpData['id']}, Code: '{$otpCode}'");
                }
            }

            // Method 4: Get latest OTP and compare in PHP (most flexible)
            if (!$otpData && !empty($allValidOtps)) {
                $latestOtp = $allValidOtps[0];
                $latestRaw = trim($latestOtp['otp_code']);
                $latestNormalized = str_pad(preg_replace('/[^\d]/', '', $latestRaw), 6, '0', STR_PAD_LEFT);

                // Final PHP comparison with all methods
                if (
                    $latestNormalized === $otpCode ||
                    intval($latestRaw) === $inputCodeInt ||
                    $latestRaw === $otpCode
                ) {
                    $stmt = $pdo->prepare("SELECT * FROM password_reset_otp WHERE id = ?");
                    $stmt->execute([$latestOtp['id']]);
                    $otpData = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("verify_otp.php: ✅ MATCH FOUND via final PHP comparison - ID: {$latestOtp['id']}");
                }
            }
        }
    }

    // Final attempt: Check if ANY OTP matches (even expired/verified) to provide better error
    if (!$otpData) {
        foreach ($allRecentOtps as $dbOtp) {
            $rawStoredCode = trim($dbOtp['otp_code']);
            $rawStoredCode = preg_replace('/[\x00-\x1F\x7F]/', '', $rawStoredCode);
            $storedCodeNormalized = str_pad(preg_replace('/[^\d]/', '', $rawStoredCode), 6, '0', STR_PAD_LEFT);

            if ($storedCodeNormalized === $otpCode) {
                // Found matching code but it's not valid
                $expiresAt = strtotime($dbOtp['expires_at']);
                $isValid = ($expiresAt > $currentTime);
                $notVerified = ($dbOtp['verified'] == 0);

                if (!$notVerified) {
                    ob_clean();
                    error_log("verify_otp.php: Code matches but already verified - ID: {$dbOtp['id']}, Code: '{$storedCodeNormalized}'");
                    sendResponse(['error' => 'This verification code has already been used. Please request a new code.'], 400);
                } elseif (!$isValid) {
                    ob_clean();
                    $expiredMinutes = round(($currentTime - $expiresAt) / 60);
                    error_log("verify_otp.php: Code matches but expired - ID: {$dbOtp['id']}, Code: '{$storedCodeNormalized}', Expired {$expiredMinutes} minutes ago");
                    sendResponse(['error' => 'Verification code has expired. Please request a new code.'], 400);
                }
            }
        }
    }

    if (!$otpData) {
        // Check all OTPs (including expired/verified) to provide better error messages
        $allOtpsStmt = $pdo->prepare("SELECT id, otp_code, verified, expires_at, created_at 
                                       FROM password_reset_otp 
                                       WHERE user_id = ? 
                                       ORDER BY created_at DESC LIMIT 5");
        $allOtpsStmt->execute([$userId]);
        $allOtps = $allOtpsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allOtps as $dbOtp) {
            $rawStoredCode = trim($dbOtp['otp_code']);
            $storedCodeNormalized = preg_replace('/\D/', '', $rawStoredCode);
            $storedCodeNormalized = str_pad($storedCodeNormalized, 6, '0', STR_PAD_LEFT);

            // Check if code matches (using same normalization as input)
            $codeMatches = ($storedCodeNormalized === $otpCode) ||
                (intval($rawStoredCode) === intval($otpCode)) ||
                (strval($rawStoredCode) === strval($otpCode));

            if ($codeMatches) {
                // Code matches but something else is wrong
                if ($dbOtp['verified'] == 1) {
                    ob_clean();
                    error_log("verify_otp.php: OTP already verified - user_id: {$userId}, otp_code: {$otpCode}");
                    sendResponse(['error' => 'This verification code has already been used. Please request a new code.'], 400);
                }

                $expiresAt = strtotime($dbOtp['expires_at']);
                if ($expiresAt <= time()) {
                    ob_clean();
                    $expiredMinutes = round((time() - $expiresAt) / 60);
                    error_log("verify_otp.php: OTP expired - user_id: {$userId}, otp_code: {$otpCode}, expired {$expiredMinutes} minutes ago");
                    sendResponse(['error' => 'Verification code has expired. Please request a new code.'], 400);
                }
            }
        }

        // Check if there's a recent OTP for this user but with different code
        if (!empty($allValidOtps)) {
            $latestOtp = $allValidOtps[0];
            $latestCode = preg_replace('/\D/', '', trim($latestOtp['otp_code']));
            $latestCode = str_pad($latestCode, 6, '0', STR_PAD_LEFT);

            // Also check all recent OTPs to see if any match
            $foundMatchInRecent = false;
            foreach ($allRecentOtps as $recentOtp) {
                $recentCode = preg_replace('/\D/', '', trim($recentOtp['otp_code']));
                $recentCode = str_pad($recentCode, 6, '0', STR_PAD_LEFT);
                if ($recentCode === $otpCode) {
                    $foundMatchInRecent = true;
                    error_log("verify_otp.php: ⚠️ Found matching code in recent OTPs but it's verified or expired - ID: {$recentOtp['id']}, Verified: {$recentOtp['verified']}, Expires: {$recentOtp['expires_at']}");
                    break;
                }
            }

            ob_clean();
            error_log("verify_otp.php: Code mismatch - Latest OTP: '{$latestCode}' (raw: '{$latestOtp['otp_code']}', hex: " . bin2hex($latestOtp['otp_code']) . "), Input: '{$otpCode}' (hex: " . bin2hex($otpCode) . "), Verified: {$latestOtp['verified']}, Expires: {$latestOtp['expires_at']}, Found in recent: " . ($foundMatchInRecent ? 'YES' : 'NO'));

            // Calculate time remaining
            $expiresAt = strtotime($latestOtp['expires_at']);
            $timeRemaining = $expiresAt - time();
            $minutesRemaining = round($timeRemaining / 60);

            // Provide more helpful error message with debugging info
            $errorMsg = 'Invalid verification code. Please check the code and try again.';
            if ($foundMatchInRecent) {
                $errorMsg = 'This verification code has already been used or has expired. Please request a new code.';
            }

            // Include helpful debug info (can be removed in production)
            $debugInfo = [
                'input_normalized' => $otpCode,
                'latest_stored_normalized' => $latestCode,
                'latest_stored_raw' => $latestOtp['otp_code'],
                'expires_at' => $latestOtp['expires_at'],
                'time_remaining_minutes' => $minutesRemaining,
                'verified' => $latestOtp['verified'],
                'user_id' => $userId
            ];

            error_log("verify_otp.php: Debug info: " . json_encode($debugInfo));

            sendResponse([
                'error' => $errorMsg,
                'debug_info' => $debugInfo,
                'tip' => 'If you copied the code from email, make sure there are no extra spaces. Try typing it manually or use the Resend Code button.'
            ], 400);
        }

        // Log failed attempt for security monitoring
        ob_clean();
        error_log("verify_otp.php: ❌ Failed OTP verification - user_id: {$userId}, otp_code: '{$otpCode}', No matching OTP found");
        sendResponse(['error' => 'Invalid or expired verification code. Please request a new code.'], 400);
    }

    // Log successful match
    error_log("verify_otp.php: OTP matched - user_id: {$userId}, otp_code: {$otpCode}, expires_at: {$otpData['expires_at']}");

    // Mark OTP as verified - use the ID from the found record for more reliable update
    try {
        if (isset($otpData['id'])) {
            $stmt = $pdo->prepare("UPDATE password_reset_otp SET verified = 1 WHERE id = ?");
            $stmt->execute([$otpData['id']]);
        } else {
            // Fallback to user_id and code if ID not available
            $stmt = $pdo->prepare("UPDATE password_reset_otp SET verified = 1 WHERE user_id = ? AND otp_code = ?");
            $stmt->execute([$userId, $otpCode]);
        }

        if ($stmt->rowCount() === 0) {
            ob_clean();
            error_log("verify_otp.php: Failed to mark OTP as verified - user_id: {$userId}, otp_code: {$otpCode}");
            sendResponse(['error' => 'Failed to verify code. Please try again.'], 500);
        }
    } catch (PDOException $e) {
        ob_clean();
        error_log("verify_otp.php: Database error updating OTP verification status: " . $e->getMessage());
        sendResponse(['error' => 'Database error occurred while verifying code'], 500);
    }

    // Generate a temporary reset token for password reset
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Create or update password_reset_tokens table
    try {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($createTableSQL);
    } catch (PDOException $e) {
        // Table might already exist
        error_log("verify_otp.php: password_reset_tokens table creation: " . $e->getMessage());
    }

    // Delete old tokens for this user
    $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND (used = 1 OR expires_at < NOW())");
    $stmt->execute([$userId]);

    // Insert new reset token
    $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $resetToken, $expiresAt]);

    // Verify token was stored correctly
    $verifyTokenStmt = $pdo->prepare("SELECT token, expires_at, used FROM password_reset_tokens WHERE user_id = ? AND token = ?");
    $verifyTokenStmt->execute([$userId, $resetToken]);
    $storedToken = $verifyTokenStmt->fetch(PDO::FETCH_ASSOC);

    if ($storedToken) {
        error_log("verify_otp.php: ✅ Successfully verified OTP and created reset token for user_id: {$userId}, Token: " . substr($resetToken, 0, 10) . "... (length: " . strlen($resetToken) . "), Expires: {$storedToken['expires_at']}");
    } else {
        error_log("verify_otp.php: ⚠️ WARNING - Token not found after insertion! user_id: {$userId}, Token: " . substr($resetToken, 0, 10) . "...");
    }

    ob_clean();
    sendResponse([
        'success' => true,
        'message' => 'Verification code verified successfully',
        'reset_token' => $resetToken,
        'token_length' => strlen($resetToken) // For debugging
    ]);
} catch (PDOException $e) {
    ob_clean();
    error_log("Database error in verify_otp.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(['error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    ob_clean();
    error_log("General error in verify_otp.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendResponse(['error' => 'An error occurred. Please try again.'], 500);
}
