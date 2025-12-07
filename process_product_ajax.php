<?php
// process_product_ajax.php

session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require_once 'classes/Product.class.php';
require_once 'security_helpers.php'; // Required for the CSRF check
header('Content-Type: application/json');

// --- CRITICAL CSRF CHECK (Must be first) ---
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';

if (!hash_equals($session_token, $submitted_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security Error: Invalid request token.']);
    exit;
}
// --- CSRF check passed, proceed ---

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?? 'add';
$product_model = new Product();
$response = ['success' => false, 'message' => 'Invalid action specified.'];

// --- Common Input Retrieval and Sanitization ---
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$name = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'product_description', FILTER_SANITIZE_STRING);
// Prices should be validated as floats
$price = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT);

switch ($action) {
    case 'add':
        $file_data = $_FILES['product_image'] ?? [];
        $response = $product_model->addProduct($category_id, $name, $description, $price, $file_data);
        break;

    case 'update':
        $old_image_path = filter_input(INPUT_POST, 'old_image', FILTER_SANITIZE_STRING);
        $file_data = $_FILES['product_image'] ?? [];

        if ($id === false || $id === null) {
            $response = ['success' => false, 'message' => 'Missing product ID for update.'];
        } else {
            $response = $product_model->updateProduct($id, $category_id, $name, $description, $price, $file_data, $old_image_path);
        }
        break;

    case 'delete':
        if ($id === false || $id === null) {
            $response = ['success' => false, 'message' => 'Missing product ID for delete.'];
        } else {
            $response = $product_model->deleteProduct($id);
        }
        break;
}

echo json_encode($response);
exit;