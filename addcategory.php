<?php
include 'header.php'; 
// No need for ob_start(), session_start(), or the old POST logic here anymore!
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Add New Category</h1>
    <div class="row justify-content-center">
        <div class="col-lg-6"> 
            
            <div id="ajax_response_message"></div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Category Details</h6>
                </div>
                <div class="card-body">
                  
                  <form id="addCategoryForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
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
                    <button type="submit" name="submit_category" class="btn btn-primary" id="submitButton">Add Category</button>
                  </form> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // 1. Custom file input display logic
        $('#categoryImage').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });

        // 2. AJAX Form Submission Script
        $('#addCategoryForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submit

            var form = $(this);
            var submitButton = $('#submitButton');
            var messageBox = $('#ajax_response_message');
            
            // Disable button and show loading state
            submitButton.prop('disabled', true).text('Adding...');

            // Clear previous messages
            messageBox.html('');

            $.ajax({
                url: 'process_category_ajax.php', // Target the new dedicated PHP file
                type: 'POST',
                data: new FormData(this), // Required for file uploads via AJAX
                contentType: false,       // Required for file uploads via AJAX
                processData: false,       // Required for file uploads via AJAX
                
                success: function(response) {
                    // Check the response from the PHP script
                    if (response.success) {
                        messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');
                        
                        // === MODIFIED CODE: Hides the success message after 3 seconds ===
                        messageBox.find('.alert-success').delay(3000).slideUp(500, function() {
                            $(this).remove(); // Remove the element from the DOM
                        });
                        // ===============================================================
                        
                        // Optional: Clear the form fields on success
                        form[0].reset(); 
                        $('#categoryImage').next('.custom-file-label').html('Choose file...'); // Reset file input label
                    } else {
                        messageBox.html('<div class="alert alert-danger" role="alert">' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    messageBox.html('<div class="alert alert-danger" role="alert">An error occurred during the request. Please check the network tab.</div>');
                    console.log("AJAX Error:", status, error);
                },
                complete: function() {
                    // Re-enable button
                    submitButton.prop('disabled', false).text('Add Category');
                }
            });
        });
    });
</script>

<?php
include 'footer.php';
?>