<?php
// process_login_ajax.php (Controller)

session_start(); // Start session to store user login state

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    exit;
}

require_once 'classes/User.class.php';
header('Content-Type: application/json');

// --- 1. Get and Sanitize Input ---
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? ''; 

// --- 2. Call the Model's Business Logic ---
$user_model = new User();
$response = $user_model->loginUser($email, $password);

// --- 3. Handle Session on Success ---
if ($response['success']) {
    // Set session variables upon successful login
    $_SESSION['user_id'] = $response['user']['id'];
    $_SESSION['username'] = $response['user']['username'];
    $_SESSION['user_role'] = $response['user']['role'];
    
    // Clear sensitive data from the response before sending
    unset($response['user']); 
}

// --- 4. Return JSON Response ---
echo json_encode($response);
exit;