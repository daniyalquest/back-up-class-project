<?php
// 1. Start the session to gain access to session variables
session_start();

// 2. Unset all session variables (clears user_id, username, etc.)
$_SESSION = array();

// 3. Destroy the session (removes the session file/data on the server)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redirect the user to the login page
header('Location: login.php');
exit;