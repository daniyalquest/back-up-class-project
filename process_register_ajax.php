<?php
// process_register_ajax.php (Controller)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    exit;
}

require_once 'classes/User.class.php';
header('Content-Type: application/json');

// --- 1. Get and Sanitize Input ---
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
// Pass password directly, as it will be hashed in the Model
$password = $_POST['password'] ?? ''; 

// --- 2. Call the Model's Business Logic ---
$user_model = new User();
$response = $user_model->registerUser($username, $email, $password);

// --- 3. Return JSON Response ---
echo json_encode($response);
exit;