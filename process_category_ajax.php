<?php
// Ensure this script only runs for POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit;
}

// Ensure the necessary class is available
require_once 'classes/Category.class.php';

// Set header to return JSON response
header('Content-Type: application/json');

// --- 1. Get and Sanitize Input ---
// Use filter_input for secure retrieval
$name = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'category_description', FILTER_SANITIZE_STRING);

// Retrieve file data
$file_data = $_FILES['category_image'] ?? [];

// --- 2. Call the Model's Business Logic ---
$category_model = new Category();
$response = $category_model->addCategory($name, $description, $file_data);

// --- 3. Return JSON Response ---
echo json_encode($response);
exit;