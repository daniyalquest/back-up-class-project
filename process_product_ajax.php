<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    exit;
}

// Ensure the new class is available
require_once 'classes/Product.class.php';

header('Content-Type: application/json');

// --- 1. Get and Sanitize Input ---
// filter_input for integer for category ID
$category_id = filter_input(INPUT_POST, 'product_category', FILTER_VALIDATE_INT); 
$name = filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'product_description', FILTER_SANITIZE_STRING);
// filter_input for float/decimal for price
$price = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT); 

// Handle failed filtering
if ($category_id === false || $category_id === null || $price === false || $price === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid category or price value submitted.']);
    exit;
}

$file_data = $_FILES['product_image'] ?? [];

// --- 2. Call the Model's Business Logic ---
$product_model = new Product();
$response = $product_model->addProduct($category_id, $name, $description, $price, $file_data);

// --- 3. Return JSON Response ---
echo json_encode($response);
exit;