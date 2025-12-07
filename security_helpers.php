<?php
// security_helpers.php (or integrated into header.php)

// Ensures a cryptographically secure, 32-character hexadecimal token is generated
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // Use random_bytes for a strong token source
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_field() {
    $token = generate_csrf_token();
    // Use htmlspecialchars() for XSS protection when outputting the token
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>