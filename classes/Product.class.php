<?php

// Use the absolute path fix from our last interaction
require_once __DIR__ . '/../connection.php';

class Product {
    
    /**
     * Adds a new product to the database.
     */
    public function addProduct(int $category_id, string $name, string $description, float $price, array $file_data): array {
        
        $conn = get_db_connection();
        $upload_dir = 'img/products/'; // Use a different directory for product images
        $image_path_for_db = "";
        $errors = [];

        // --- 1. Comprehensive Input Validation (Including Image Requirement) ---
        
        // Check 1: Category ID
        if ($category_id <= 0) {
            $errors[] = 'A valid Category must be selected.';
        }

        // Check 2: Product Name
        if (empty($name)) {
            $errors[] = 'Product Name is required.';
        }

        // Check 3: Price
        if ($price <= 0) {
            $errors[] = 'Price must be a positive value.';
        }
        
        // NEW CHECK 4: Product Image is required
        // UPLOAD_ERR_NO_FILE is the constant PHP sets when the field is submitted empty.
        // We check for that error code specifically.
        if (empty($file_data) || $file_data['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Product Image is required.';
        }
        
        // Check 5: Handle all collected errors
        if (!empty($errors)) {
            $conn->close();
            // Return all validation errors joined by a space
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        // --- 2. Check for Duplicate Product Name (Secure Prepared Statement) ---
        $check_sql = "SELECT COUNT(*) FROM `products` WHERE `name` = ?";
        $stmt_check = $conn->prepare($check_sql); 
        $stmt_check->bind_param("s", $name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_row();
        $stmt_check->close();

        if ($row_check[0] > 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Error: A product with this name already exists.'];
        }
        
        // --- 3. Image Handling (Only proceeds if file was present and passed initial validation) ---
        // We know file_data is NOT empty and error is NOT UPLOAD_ERR_NO_FILE from validation above.
        // The check $file_data['error'] === UPLOAD_ERR_OK handles other errors like size/type limits from php.ini
        if ($file_data['error'] === UPLOAD_ERR_OK) {
            
            // Check directory existence
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $conn->close();
                    error_log("Failed to create upload directory: " . $upload_dir);
                    return ['success' => false, 'message' => 'File Error: Failed to prepare upload directory.'];
                }
            }

            $img = $file_data;
            $file_parts = explode('.', $img['name']);
            $ext = strtolower(end($file_parts));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                 $conn->close();
                 return ['success' => false, 'message' => 'You cannot upload files of type **' . $ext . '** (Only JPG, JPEG, PNG, GIF allowed).'];
            } elseif ($img['size'] > 5000000) { // 5MB limit
                 $conn->close();
                 return ['success' => false, 'message' => 'Your file is too large! Max 5MB allowed.'];
            } else {
                // Generate a safe, unique filename
                $safe_name = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
                $new_name = $safe_name . "-" . time() . "." . $ext; // Adding a timestamp for uniqueness
                $dest = $upload_dir . $new_name; 
                
                if (move_uploaded_file($img['tmp_name'], $dest)) {
                    $image_path_for_db = $dest; 
                } else {
                    $conn->close();
                    error_log("Error moving uploaded file: " . $img['tmp_name'] . " to " . $dest);
                    return ['success' => false, 'message' => 'File Error: Failed to move uploaded file.'];
                }
            }
        }
        
        // --- 4. Execute Insertion (Secure Prepared Statement) ---
        // Note: $image_path_for_db is guaranteed to have a value here if the image passed all checks.
        // If it still somehow failed file handling, $image_path_for_db would be "", but the validation
        // should have prevented execution in that case.
        $sql = "INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `image`) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // 'issds' denotes integer, string, string, double/float, string
        $stmt->bind_param("issds", $category_id, $name, $description, $price, $image_path_for_db);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'New product added successfully!'];
        } else {
            error_log("Product Insert Error: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Database Error: Could not add product.'];
        }
    }
}