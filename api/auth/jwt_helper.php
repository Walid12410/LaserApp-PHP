<?php
// Simple JWT functions without external libs

// Secret key - keep this secret and secure
define('JWT_SECRET', '7824e4ce593f24ec36f2b6e4e00785d035d0e75fc35ea457486acd12e0074bd9568cdd421162eb402acd1e8b5f81a10e0c693f9202e35ed6bb4db313a215fd4e');

// Generate JWT token
function jwt_encode($payload, $exp = 3600) {
    $header = base64UrlEncode(json_encode(['typ'=>'JWT','alg'=>'HS256']));
    $payload['exp'] = time() + $exp;
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header.$payloadEncoded", JWT_SECRET, true);
    $signatureEncoded = base64UrlEncode($signature);
    return "$header.$payloadEncoded.$signatureEncoded";
}

// Decode and verify JWT token, return payload or false
function jwt_decode($jwt) {
    $parts = explode('.', $jwt);
    if(count($parts) !== 3) return false;
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
    $signature = base64UrlDecode($signatureEncoded);
    $valid_signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);

    if (!hash_equals($signature, $valid_signature)) return false;

    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    if ($payload['exp'] < time()) return false; // expired

    return $payload;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}
?>
