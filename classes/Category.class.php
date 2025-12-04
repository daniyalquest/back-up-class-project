<?php
// Include the database connection function
require_once __DIR__ . '/../connection.php';

class Category {
    
    /**
     * Adds a new category to the database, handling validation and file upload.
     * @param string $name The category name.
     * @param string $description The category description.
     * @param array $file_data The $_FILES['category_image'] array.
     * @return array Associative array with 'success' (bool) and 'message' (string).
     */
    public function addCategory(string $name, string $description, array $file_data): array {
        
        $conn = get_db_connection();
        $upload_dir = 'img/categories/';
        $image_path_for_db = "";

        // --- 1. Basic Input Validation ---
        if (empty($name)) {
            $conn->close();
            return ['success' => false, 'message' => 'Category Name is required.'];
        }

        // --- 2. Check for Duplicate Category Name (Secure Prepared Statement) ---
        $check_sql = "SELECT COUNT(*) FROM `categories` WHERE `name` = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        $stmt->close();
        
        if ($row[0] > 0) {
            $conn->close();
            return ['success' => false, 'message' => "Error: A category named <strong>" . htmlspecialchars($name) . "</strong> already exists."];
        }

        // --- 3. Handle File Upload (Secure File Handling) ---
        if (!empty($file_data) && $file_data['error'] === UPLOAD_ERR_OK) {
            
            // Check directory existence
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $conn->close();
                    // Avoid exposing filesystem details; log error and give generic user message
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
        $sql = "INSERT INTO `categories` (`name`, `description`, `image`) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // 'sss' denotes three string parameters
        $stmt->bind_param("sss", $name, $description, $image_path_for_db);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'New category added successfully!'];
        } else {
            // Log the actual database error internally
            error_log("Database Insert Error: " . $stmt->error);
            $stmt->close();
            $conn->close();
            // Return a generic error message to the user
            return ['success' => false, 'message' => 'Database Error: Could not add category.'];
        }
    }
// Inside class Category { ... }

    /**
     * Retrieves all categories for use in dropdowns, etc.
     * @return array Array of category data arrays.
     */
    public function getAllCategories(): array {
        $conn = get_db_connection();
        
        // Only fetch the ID and Name (what the dropdown needs)
        $sql = "SELECT id, name FROM `categories` ORDER BY name ASC";
        
        $result = $conn->query($sql);
        $categories = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        $conn->close();
        return $categories;
    }
}