<?php
// classes/Product.class.php

require_once __DIR__ . '/../connection.php';

class Product
{

    // Helper for secure file upload and validation
    private function handleImageUpload(array $file_data, string $upload_dir, string $name, string $old_image_path = ''): array
    {
        if (empty($file_data) || $file_data['error'] !== UPLOAD_ERR_OK) {
            // No new file uploaded, or an upload error occurred (e.g., size limit from php.ini)
            if ($file_data['error'] === UPLOAD_ERR_NO_FILE) {
                return ['success' => true, 'path' => $old_image_path];
            }
            return ['success' => false, 'message' => 'File Error: ' . $file_data['error']];
        }

        $img = $file_data;
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

        // --- SECURE: MIME TYPE CHECK ---
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $img['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_mimes)) {
            return ['success' => false, 'message' => 'File Error: Invalid image file type detected (Only JPG, PNG, GIF allowed).'];
        }
        // File Size Check
        elseif ($img['size'] > 5000000) { // 5MB limit
            return ['success' => false, 'message' => 'Your file is too large! Max 5MB allowed.'];
        }

        // Check/Create directory
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                return ['success' => false, 'message' => 'File Error: Failed to prepare upload directory.'];
            }
        }

        // Generate a safe, unique filename
        $file_parts = explode('.', $img['name']);
        $ext = strtolower(end($file_parts));
        $safe_name = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $new_name = $safe_name . "-" . time() . "." . $ext;
        $dest = $upload_dir . $new_name;

        if (move_uploaded_file($img['tmp_name'], $dest)) {
            // Success: Delete old file if updating
            if ($old_image_path && file_exists($old_image_path) && $old_image_path !== $dest) {
                unlink($old_image_path);
            }
            return ['success' => true, 'path' => $dest];
        } else {
            error_log("Error moving uploaded file: " . $img['tmp_name'] . " to " . $dest);
            return ['success' => false, 'message' => 'File Error: Failed to move uploaded file.'];
        }
    }

    /**
     * Adds a new product to the database.
     */
    public function addProduct(int $category_id, string $name, string $description, float $price, array $file_data): array
    {

        $conn = get_db_connection();
        $upload_dir = 'img/products/';
        $image_path_for_db = "";
        $errors = [];

        // --- 1. Validation ---
        if ($category_id <= 0)
            $errors[] = 'A valid Category must be selected.';
        if (empty($name))
            $errors[] = 'Product Name is required.';
        if ($price <= 0)
            $errors[] = 'Price must be a positive value.';
        // Image is required for ADD
        if (empty($file_data) || $file_data['error'] === UPLOAD_ERR_NO_FILE)
            $errors[] = 'Product Image is required.';

        if (!empty($errors)) {
            $conn->close();
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        // --- 2. Check for Duplicate Name ---
        $check_sql = "SELECT COUNT(*) FROM `products` WHERE `name` = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $name);
        $stmt_check->execute();
        $row_check = $stmt_check->get_result()->fetch_row();
        $stmt_check->close();

        if ($row_check[0] > 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Error: A product with this name already exists.'];
        }

        // --- 3. Image Handling (Secure) ---
        $image_result = $this->handleImageUpload($file_data, $upload_dir, $name);
        if (!$image_result['success']) {
            $conn->close();
            return $image_result;
        }
        $image_path_for_db = $image_result['path'];

        // --- 4. Execute Insertion ---
        $sql = "INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `image`) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
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

    // --- READ All Products ---
    public function getAllProducts(): array
    {
        $conn = get_db_connection();
        // Join with categories to display the category name
        $sql = "SELECT p.id, p.name, p.description, p.price, p.image, c.name AS category_name
                FROM `products` p
                JOIN `categories` c ON p.category_id = c.id
                ORDER BY p.name ASC";
        $result = $conn->query($sql);
        $products = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        $conn->close();
        return $products;
    }

    // --- READ Single Product ---
    public function getProductById(int $id): ?array
    {
        $conn = get_db_connection();
        $sql = "SELECT id, category_id, name, description, price, image FROM `products` WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $product;
    }

    // --- UPDATE Product ---
    public function updateProduct(int $id, int $category_id, string $name, string $description, float $price, array $file_data, string $old_image_path): array
    {
        $conn = get_db_connection();
        $upload_dir = 'img/products/';
        $image_path_for_db = $old_image_path;
        $errors = [];

        // 1. Validation
        if ($id <= 0)
            $errors[] = 'Missing Product ID.';
        if ($category_id <= 0)
            $errors[] = 'A valid Category must be selected.';
        if (empty($name))
            $errors[] = 'Product Name is required.';
        if ($price <= 0)
            $errors[] = 'Price must be a positive value.';

        if (!empty($errors)) {
            $conn->close();
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        // 2. Check for Duplicate Name (excluding the current product)
        $check_sql = "SELECT COUNT(*) FROM `products` WHERE `name` = ? AND `id` != ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("si", $name, $id);
        $stmt_check->execute();
        $row_check = $stmt_check->get_result()->fetch_row();
        $stmt_check->close();

        if ($row_check[0] > 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Error: Another product already has this name.'];
        }

        // 3. Image Handling (Secure)
        $image_result = $this->handleImageUpload($file_data, $upload_dir, $name, $old_image_path);
        if (!$image_result['success']) {
            $conn->close();
            return $image_result;
        }
        $image_path_for_db = $image_result['path'];

        // 4. Execute Update
        $sql = "UPDATE `products` SET `category_id` = ?, `name` = ?, `description` = ?, `price` = ?, `image` = ? WHERE `id` = ?";
        $stmt = $conn->prepare($sql);
        // 'issdsi' denotes: int, string, string, double, string, int
        $stmt->bind_param("issdsi", $category_id, $name, $description, $price, $image_path_for_db, $id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'Product updated successfully!'];
        } else {
            error_log("Database Update Error: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Database Error: Could not update product.'];
        }
    }

    // --- DELETE Product ---
    public function deleteProduct(int $id): array
    {
        $conn = get_db_connection();

        // 1. Get image path to delete file later
        $image_sql = "SELECT `image` FROM `products` WHERE `id` = ?";
        $stmt_image = $conn->prepare($image_sql);
        $stmt_image->bind_param("i", $id);
        $stmt_image->execute();
        $image_path = $stmt_image->get_result()->fetch_row()[0] ?? '';
        $stmt_image->close();

        // 2. Execute Deletion
        $sql = "DELETE FROM `products` WHERE `id` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // 3. Delete the file from the server
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
            $stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'Product deleted successfully!'];
        } else {
            error_log("Database Delete Error: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Database Error: Could not delete product.'];
        }
    }
}