<?php
// Debug endpoint to check OTP status
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$otpCode = isset($_GET['otp']) ? trim($_GET['otp']) : '';

if (!$userId) {
    echo json_encode(['error' => 'user_id required']);
    exit;
}

try {
    // Get all OTPs for this user
    $stmt = $pdo->prepare("SELECT id, otp_code, verified, expires_at, created_at, 
                           LENGTH(otp_code) as code_length,
                           HEX(otp_code) as code_hex,
                           NOW() as current_time,
                           TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_remaining
                           FROM password_reset_otp 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT 10");
    $stmt->execute([$userId]);
    $otps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'user_id' => $userId,
        'input_otp' => $otpCode,
        'input_normalized' => $otpCode ? str_pad(preg_replace('/[^\d]/', '', $otpCode), 6, '0', STR_PAD_LEFT) : '',
        'total_otps' => count($otps),
        'otps' => []
    ];
    
    foreach ($otps as $otp) {
        $raw = trim($otp['otp_code']);
        $normalized = str_pad(preg_replace('/[^\d]/', '', $raw), 6, '0', STR_PAD_LEFT);
        $isValid = ($otp['seconds_remaining'] > 0);
        $notVerified = ($otp['verified'] == 0);
        
        $match = false;
        if ($otpCode) {
            $inputNormalized = str_pad(preg_replace('/[^\d]/', '', $otpCode), 6, '0', STR_PAD_LEFT);
            $match = ($normalized === $inputNormalized) || 
                     (intval($raw) === intval($otpCode)) ||
                     ($raw === $otpCode);
        }
        
        $result['otps'][] = [
            'id' => $otp['id'],
            'raw_code' => $raw,
            'normalized_code' => $normalized,
            'code_length' => $otp['code_length'],
            'code_hex' => $otp['code_hex'],
            'verified' => (bool)$otp['verified'],
            'expires_at' => $otp['expires_at'],
            'created_at' => $otp['created_at'],
            'is_valid' => $isValid,
            'not_verified' => $notVerified,
            'seconds_remaining' => $otp['seconds_remaining'],
            'matches_input' => $match,
            'can_be_used' => ($match && $isValid && $notVerified)
        ];
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}



