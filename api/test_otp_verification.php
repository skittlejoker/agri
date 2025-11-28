<?php
// Test script to verify OTP storage and retrieval
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain');

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$otpCode = isset($_GET['otp']) ? trim($_GET['otp']) : '';

if (!$userId || !$otpCode) {
    echo "Usage: test_otp_verification.php?user_id=1&otp=123456\n";
    exit;
}

echo "=== OTP Verification Test ===\n\n";
echo "User ID: {$userId}\n";
echo "Input OTP: '{$otpCode}'\n";
echo "Input Length: " . strlen($otpCode) . "\n";
echo "Input Hex: " . bin2hex($otpCode) . "\n\n";

// Normalize input
$normalizedInput = preg_replace('/[^\d]/', '', $otpCode);
$normalizedInput = str_pad($normalizedInput, 6, '0', STR_PAD_LEFT);
echo "Normalized Input: '{$normalizedInput}'\n\n";

// Get all OTPs for this user
$stmt = $pdo->prepare("SELECT id, otp_code, verified, expires_at, created_at, 
                       LENGTH(otp_code) as code_length,
                       HEX(otp_code) as code_hex
                       FROM password_reset_otp 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 5");
$stmt->execute([$userId]);
$otps = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($otps) . " OTP records:\n\n";

foreach ($otps as $idx => $otp) {
    $raw = trim($otp['otp_code']);
    $normalized = str_pad(preg_replace('/[^\d]/', '', $raw), 6, '0', STR_PAD_LEFT);
    $expiresAt = strtotime($otp['expires_at']);
    $isValid = ($expiresAt > time());
    $notVerified = ($otp['verified'] == 0);
    
    echo "OTP #{$idx} (ID: {$otp['id']}):\n";
    echo "  Raw: '{$raw}' (length: {$otp['code_length']}, hex: {$otp['code_hex']})\n";
    echo "  Normalized: '{$normalized}'\n";
    echo "  Verified: " . ($otp['verified'] ? 'YES' : 'NO') . "\n";
    echo "  Expires: {$otp['expires_at']} (" . ($isValid ? 'VALID' : 'EXPIRED') . ")\n";
    echo "  Created: {$otp['created_at']}\n";
    
    // Compare
    $match1 = ($normalized === $normalizedInput);
    $match2 = (intval($raw) === intval($normalizedInput));
    $match3 = ($raw === $otpCode);
    
    echo "  Match (normalized): " . ($match1 ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  Match (integer): " . ($match2 ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  Match (direct): " . ($match3 ? 'YES ✅' : 'NO ❌') . "\n";
    
    if (($match1 || $match2 || $match3) && $notVerified && $isValid) {
        echo "  ✅ THIS OTP SHOULD MATCH!\n";
    } elseif ($match1 || $match2 || $match3) {
        echo "  ⚠️ Code matches but: " . (!$notVerified ? 'Already verified' : '') . (!$isValid ? 'Expired' : '') . "\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";



