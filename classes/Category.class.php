<?php
// classes/Category.class.php

require_once __DIR__ . '/../connection.php';

class Category
{

    /* --------------------------------------------
     * ADD CATEGORY (with full validation)
     * -------------------------------------------- */
    public function addCategory(string $name, string $description, array $file_data): array
    {

        $conn = get_db_connection();
        $upload_dir = 'img/categories/';
        $image_path_for_db = "";

        /* ------------------------
         * 1. Basic Required Fields
         * ------------------------ */
        if (empty(trim($name))) {
            return ['success' => false, 'message' => 'Category Name is required.'];
        }

        if (empty(trim($description))) {
            return ['success' => false, 'message' => 'Category Description is required.'];
        }

        // Image required for adding
        if (empty($file_data) || $file_data['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Category Image is required.'];
        }

        /* ------------------------
         * 2. Duplicate Name Check
         * ------------------------ */
        $check_sql = "SELECT COUNT(*) FROM categories WHERE name = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        if ($exists > 0) {
            return ['success' => false, 'message' => "A category named <strong>" . htmlspecialchars($name) . "</strong> already exists."];
        }

        /* ------------------------
         * 3. SECURE IMAGE VALIDATION (MIME CHECK)
         * ------------------------ */
        $img = $file_data;

        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $img['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_mimes)) {
            $conn->close();
            return ['success' => false, 'message' => 'File Error: Invalid image file type detected.'];
        } elseif ($img['size'] > 5000000) { // 5MB limit
            $conn->close();
            return ['success' => false, 'message' => 'Your file is too large! Max 5MB allowed.'];
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $safe_name = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $new_name = $safe_name . "-" . time() . ".jpg"; // extension based on real MIME? optional
        $dest = $upload_dir . $new_name;

        if (!move_uploaded_file($img['tmp_name'], $dest)) {
            return ['success' => false, 'message' => "Error saving uploaded image."];
        }

        $image_path_for_db = $dest;

        /* ------------------------
         * 4. Insert Category
         * ------------------------ */
        $sql = "INSERT INTO categories (name, description, image) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $description, $image_path_for_db);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => "Category added successfully!"];
        }

        error_log("Database Insert Error: " . $stmt->error);
        return ['success' => false, 'message' => "Database Error: Could not add category."];
    }


    /* --------------------------------------------
     * GET ALL CATEGORIES (full data)
     * -------------------------------------------- */
    public function getAllCategories(): array
    {
        $conn = get_db_connection();
        $sql = "SELECT id, name, description, image FROM categories ORDER BY name ASC";
        $result = $conn->query($sql);

        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }

        return $data;
    }


    /* --------------------------------------------
     * GET CATEGORY BY ID
     * -------------------------------------------- */
    public function getCategoryById(int $id): ?array
    {
        $conn = get_db_connection();
        $sql = "SELECT id, name, description, image FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        return $res ?: null;
    }


    /* --------------------------------------------
     * UPDATE CATEGORY (with required validation)
     * -------------------------------------------- */
    public function updateCategory(int $id, string $name, string $description, array $file_data, string $old_image): array
    {

        $conn = get_db_connection();
        $upload_dir = "img/categories/";
        $image_path_for_db = $old_image;

        /* ------------------------
         * 1. Required Validation
         * ------------------------ */
        if ($id <= 0) {
            return ['success' => false, 'message' => "Invalid category ID."];
        }

        if (empty(trim($name))) {
            return ['success' => false, 'message' => "Category Name is required."];
        }

        if (empty(trim($description))) {
            return ['success' => false, 'message' => "Category Description is required."];
        }

        /* ------------------------
         * 2. Check Duplicate Name
         * ------------------------ */
        $sql = "SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];

        if ($count > 0) {
            return ['success' => false, 'message' => "Another category already uses this name."];
        }

        /* ------------------------
         * 3. Handle New Image (SECURE MIME CHECK)
         * ------------------------ */
        if (!empty($file_data) && $file_data['error'] === UPLOAD_ERR_OK) {

            $img = $file_data;

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $img['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_mimes)) {
                $conn->close();
                return ['success' => false, 'message' => "File Error: Invalid image file type detected."];
            } elseif ($img['size'] > 5000000) {
                $conn->close();
                return ['success' => false, 'message' => "Your file is too large! Max 5MB allowed."];
            }

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $safe_name = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
            $new_name = $safe_name . "-" . time() . ".jpg";
            $dest = $upload_dir . $new_name;

            if (!move_uploaded_file($img['tmp_name'], $dest)) {
                return ['success' => false, 'message' => "Failed to upload new image."];
            }

            // Delete old file
            if (!empty($old_image) && file_exists($old_image)) {
                unlink($old_image);
            }

            $image_path_for_db = $dest;
        }

        /* ------------------------
         * 4. Update the Database
         * ------------------------ */
        $sql = "UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $description, $image_path_for_db, $id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => "Category updated successfully!"];
        }

        error_log("Database Update Error: " . $stmt->error);
        return ['success' => false, 'message' => "Database Error: Could not update category."];
    }


    /* --------------------------------------------
     * DELETE CATEGORY
     * -------------------------------------------- */
    public function deleteCategory(int $id): array
    {

        $conn = get_db_connection();

        // Block deletion if linked
        $sql = "SELECT COUNT(*) FROM products WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];

        if ($count > 0) {
            return ['success' => false, 'message' => "Cannot delete: Category is linked to $count product(s)."];
        }

        // Get image path
        $sql = "SELECT image FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $image = $stmt->get_result()->fetch_row()[0] ?? "";

        // Delete category
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            if (!empty($image) && file_exists($image)) {
                unlink($image);
            }

            return ['success' => true, 'message' => "Category deleted successfully!"];
        }

        error_log("Delete Error: " . $stmt->error);
        return ['success' => false, 'message' => "Database Error: Could not delete category."];
    }


    /* --------------------------------------------
     * DROPDOWN — ID + Name Only
     * -------------------------------------------- */
    public function getAllCategoriesForDropdown(): array
    {
        $conn = get_db_connection();
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        $result = $conn->query($sql);

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $list[] = $row;
        }

        return $list;
    }
}

?>