<?php
include 'header.php';
// Include the connection file if needed for database operations
// include 'connection.php'; 
?>
<?php
// 1. Include Connection File
// Ensure this path is correct relative to process_category.php
include 'conn.php'; 

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Sanitize and Collect Text Data
    // mysqli_real_escape_string helps prevent SQL injection
    $name = $conn->real_escape_string($_POST['category_name']);
    $description = $conn->real_escape_string($_POST['category_description']);
    
    // Default image path to store in DB
    $image_path_for_db = ""; 

    // 3. Handle Image Upload
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
        
        $image = $_FILES['category_image'];
        $image_name = $image['name'];
        $image_tmp_name = $image['tmp_name'];
        $image_size = $image['size'];
        $image_error = $image['error'];
        $image_type = $image['type'];

        // Get the file extension and convert it to lowercase
        $image_ext = explode('.', $image_name);
        $image_actual_ext = strtolower(end($image_ext));

        // Define allowed file extensions
        $allowed = array('jpg', 'jpeg', 'png', 'gif');

        // Check the file type/extension
        if (in_array($image_actual_ext, $allowed)) {
            
            // Check for upload errors
            if ($image_error === 0) {
                
                // You can set a max file size here (e.g., 5MB)
                if ($image_size < 5000000) { 
                    
                    // Create a unique name for the image to prevent overwrites
                    $image_new_name = uniqid('', true) . "." . $image_actual_ext;
                    
                    // Define the upload destination folder
                    // This path is relative to the PHP script execution location
                    $file_destination = 'img/categories/' . $image_new_name;

                    // Move the uploaded file from the temporary location to the final destination
                    if (move_uploaded_file($image_tmp_name, $file_destination)) {
                        // Store the relative path in the variable to be saved in the database
                        $image_path_for_db = $file_destination; 
                    } else {
                        die("Error moving uploaded file.");
                    }

                } else {
                    die("Your file is too large! Max 5MB allowed.");
                }
            } else {
                die("There was an error uploading your file: " . $image_error);
            }
        } else {
            // Error if file extension is not allowed
            die("You cannot upload files of type **" . $image_actual_ext . "** (Only JPG, JPEG, PNG, GIF allowed).");
        }
    }

    // 4. Construct and Execute SQL Query
    // IMPORTANT: Note that the 'id' column is auto-incremented in the SQL below, 
    // so we omit it from the VALUES list.
    $sql = "INSERT INTO `categories` (`name`, `description`, `image`) 
            VALUES ('$name', '$description', '$image_path_for_db')";

    if ($conn->query($sql) === TRUE) {
        echo "New category added successfully! Image path saved as: " . $image_path_for_db;
        // Redirect to a success page or category list
        // header("Location: index.php?status=success");
        // exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // 5. Close Connection
    $conn->close();

} else {
    // If someone tries to access this script directly without POSTing the form
    echo "Access denied. Please submit the form.";
}

?>
<div class="container-fluid">
    
    <div class="row justify-content-center">

        <div class="col-lg-6"> 
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Category</h6>
                </div>
                <div class="card-body">
                    
                  <form action="process_category.php" method="POST" enctype="multipart/form-data">

    <div class="form-group">
        <label for="categoryName">Category Name</label>
        <input type="text" class="form-control" id="categoryName" name="category_name" required>
        <small class="form-text text-muted">Enter a short, descriptive name for the category.</small>
    </div>

    <div class="form-group">
        <label for="categoryDescription">Description</label>
        <textarea class="form-control" id="categoryDescription" name="category_description" rows="3"></textarea>
    </div>

    <div class="form-group">
        <label for="categoryImage">Image</label>
        <div class="custom-file">
            <input type="file" class="custom-file-input" id="categoryImage" name="category_image" accept="image/*">
            <label class="custom-file-label" for="categoryImage">Choose file...</label>
            <small class="form-text text-muted">Upload an image file (e.g., JPEG, PNG).</small>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Add Category</button>
</form> 
                    </div>
            </div>

        </div>
    </div>
    </div>
<?php
include 'footer.php';
?>