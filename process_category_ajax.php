<?php
// process_category_ajax.php

session_start();

// --- CRITICAL CSRF CHECK ---
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';

// Use hash_equals() for constant-time comparison to prevent timing attacks
if (!hash_equals($session_token, $submitted_token)) {
    // Attack detected!
    error_log("CSRF attack detected! Session ID: " . session_id());
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Security Error: Invalid request token.']);
    exit;
}
// --- CSRF check passed, proceed with business logic ---


// Ensure this script only runs for POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit;
}

// Ensure the necessary class is available
require_once 'classes/Category.class.php';

// Set header to return JSON response
header('Content-Type: application/json');

// --- Determine Action ---
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?? 'add'; // Default action
$category_model = new Category();
$response = ['success' => false, 'message' => 'Invalid action specified.'];

switch ($action) {

    /* ---------------------------------
       ADD CATEGORY
    -----------------------------------*/
    case 'add':
        $name = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'category_description', FILTER_SANITIZE_STRING);
        $file_data = $_FILES['category_image'] ?? [];

        $response = $category_model->addCategory($name, $description, $file_data);
        break;


    /* ---------------------------------
       UPDATE CATEGORY
    -----------------------------------*/
    case 'update':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'category_description', FILTER_SANITIZE_STRING);
        $old_image_path = filter_input(INPUT_POST, 'old_image', FILTER_SANITIZE_STRING);
        $file_data = $_FILES['category_image'] ?? [];

        if ($id === false || $id === null) {
            $response = ['success' => false, 'message' => 'Missing category ID for update.'];
        } else {
            $response = $category_model->updateCategory($id, $name, $description, $file_data, $old_image_path);
        }
        break;


    /* ---------------------------------
       DELETE CATEGORY
    -----------------------------------*/
    case 'delete':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id === false || $id === null) {
            $response = ['success' => false, 'message' => 'Missing category ID for delete.'];
        } else {
            $response = $category_model->deleteCategory($id);
        }
        break;
}

// --- Return JSON Response ---
echo json_encode($response);
exit;
