<?php
include 'header.php';
require_once 'classes/Category.class.php';

$category_model = new Category();
$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 1. Fetch the category data
if ($category_id) {
    $category = $category_model->getCategoryById($category_id);
} else {
    $category = null;
}

if (!$category) {
    echo '<div class="alert alert-danger">Category not found or missing ID.</div>';
    include 'footer.php';
    exit;
}

// Extract data for form display
$name = htmlspecialchars($category['name']);
$description = htmlspecialchars($category['description']);
$image = htmlspecialchars($category['image']);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Category: <?php echo htmlspecialchars($name); ?></h1>
    <div class="row justify-content-center">
        <div class="col-lg-6"> 
            
            <div id="ajax_response_message"></div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Category Details</h6>
                </div>
                <div class="card-body">
                  
                  <form id="editCategoryForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                    
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                    <input type="hidden" name="old_image" value="<?php echo $image; ?>">

                    <div class="form-group">
                        <label for="categoryName">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="category_name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoryDescription">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="category_description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Image</label><br>
                        <?php if (!empty($image) && file_exists($image)): ?>
                            <img src="<?php echo $image; ?>" style="max-width: 150px; max-height: 150px; margin-bottom: 10px; display: block;">
                        <?php else: ?>
                            <p>No image uploaded.</p>
                        <?php endif; ?>

                        <label for="categoryImage">Replace Image (Optional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="categoryImage" name="category_image" accept="image/*">
                            <label class="custom-file-label" for="categoryImage">Choose new file...</label>
                        </div>
                    </div>
                    <?php echo csrf_token_field(); ?>
                    <button type="submit" class="btn btn-success" id="submitButton">Update Category</button>
                    <a href="view_categories.php" class="btn btn-secondary">Cancel</a>
                  </form> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Update label for custom file input
        $('#categoryImage').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });

        // AJAX Form Submission Script for Update
        $('#editCategoryForm').on('submit', function(e) {
            e.preventDefault(); 

            var form = $(this);
            var submitButton = $('#submitButton');
            var messageBox = $('#ajax_response_message');
            
            submitButton.prop('disabled', true).text('Updating...');
            messageBox.html('');

            $.ajax({
                url: 'process_category_ajax.php', 
                type: 'POST',
                data: new FormData(this), // Use FormData for file uploads
                contentType: false,       
                processData: false,       
                dataType: 'json', 
                
                success: function(response) {
                    if (response.success) {
                        messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');
                        // Reset file input label but don't clear the form
                        $('#categoryImage').next('.custom-file-label').html('Choose new file...'); 
                        
                        // Optional: Redirect back to the view page after a few seconds
                        setTimeout(function() {
                            window.location.href = 'view_categories.php';
                        }, 2000);

                    } else {
                        var errorMessage = response && response.message ? response.message : "Unknown error occurred.";
                        messageBox.html('<div class="alert alert-danger" role="alert">' + errorMessage + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    messageBox.html('<div class="alert alert-danger" role="alert">Request Failed: An unexpected server-side error occurred.</div>');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Update Category');
                }
            });
        });
    });
</script>

<?php include 'footer.php'; ?>