<?php
// Start session for any necessary session handling (e.g., flash messages)
// NOTE: Ensure session_start() is run once, likely in connection.php or header.php
// session_start(); 

// Include the database connection
// Assuming connection.php is in the same directory or accessible via header.php's include path
include 'connection.php'; 

// Set the content type to JSON
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (isset($_POST['category_name'])) {
    
    // 1. Sanitize Data
    $name_raw = $_POST['category_name'];
    $name = $conn->real_escape_string($name_raw);
    $description = $conn->real_escape_string($_POST['category_description']);
    $image_path_for_db = "";
    $upload_dir = 'img/categories/';
    $upload_success = true;

    // 2. CHECK FOR DUPLICATE CATEGORY NAME
    $check_sql = "SELECT COUNT(*) FROM `categories` WHERE `name` = '$name'";
    $result = $conn->query($check_sql);
    $row = $result->fetch_row();
    
    if ($row[0] > 0) {
        $response['message'] = 'Error: A category named <strong>' . htmlspecialchars($name_raw) . '</strong> already exists.';
        $upload_success = false;
    }

    // 3. Handle File Upload (Only if no duplicate was found)
    if ($upload_success) {
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $response['message'] = 'Fatal Error: Failed to create upload directory. Check folder permissions.';
                $upload_success = false;
            }
        }

        if ($upload_success && isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
            $img = $_FILES['category_image'];
            $file_parts = explode('.', $img['name']);
            $ext = strtolower(end($file_parts));
            $allowed = array('jpg', 'jpeg', 'png', 'gif');

            if (!in_array($ext, $allowed)) {
                 $response['message'] = 'You cannot upload files of type **' . $ext . '** (Only JPG, JPEG, PNG, GIF allowed).';
                 $upload_success = false;
            } elseif ($img['error'] !== 0) {
                 $response['message'] = 'There was an error uploading your file: ' . $img['error'];
                 $upload_success = false;
            } elseif ($img['size'] > 5000000) {
                 $response['message'] = 'Your file is too large! Max 5MB allowed.';
                 $upload_success = false;
            } else {
                $safe_name = preg_replace('/[^a-z0-9]+/', '-', strtolower($name_raw));
                $new_name = $safe_name . "." . $ext;
                $dest = $upload_dir . $new_name; 
                
                if (move_uploaded_file($img['tmp_name'], $dest)) {
                    $image_path_for_db = $dest; 
                } else {
                    $response['message'] = 'Error moving uploaded file. Check folder permissions.';
                    $upload_success = false;
                }
            }
        }
    }

    // 4. Execute SQL Query
    if ($upload_success) {
        $sql = "INSERT INTO `categories` (`name`, `description`, `image`) VALUES ('$name', '$description', '$image_path_for_db')";
        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
            $response['message'] = 'New category added successfully!';
        } else {
            $response['message'] = 'Database Error: ' . $conn->error;
        }
    }
    
    $conn->close();
}

echo json_encode($response);
exit;
?>