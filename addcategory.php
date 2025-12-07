<?php
include 'header.php';
?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div id="ajax_response_message"></div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Category</h6>
                </div>
                <div class="card-body">

                    <form id="addCategoryForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                        <div class="form-group">
                            <label for="categoryName">Category Name</label>
                            <input type="text" class="form-control" id="categoryName" name="category_name" required>
                            <small class="form-text text-muted">Enter a short, descriptive name for the
                                category.</small>
                        </div>
                        <div class="form-group">
                            <label for="categoryDescription">Description</label>
                            <textarea class="form-control" id="categoryDescription" name="category_description"
                                rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="categoryImage">Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="categoryImage" name="category_image"
                                    accept="image/*">
                                <label class="custom-file-label" for="categoryImage">Choose file...</label>
                                <small class="form-text text-muted">Upload an image file (e.g., JPEG, PNG).</small>
                            </div>
                        </div>
                        <?php echo csrf_token_field(); ?>
                        <button type="submit" name="submit_category" class="btn btn-primary" id="submitButton">Add
                            Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // 1. Custom file input display logic (for Bootstrap's custom-file-input)
        $('#categoryImage').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });

        // 2. AJAX Form Submission Script
        $('#addCategoryForm').on('submit', function (e) {
            e.preventDefault(); // Stop the form from performing a traditional page refresh submit

            var form = $(this);
            var submitButton = $('#submitButton');
            var messageBox = $('#ajax_response_message');

            // Disable button and show loading state
            submitButton.prop('disabled', true).text('Adding...');

            // Clear previous messages
            messageBox.html('');

            $.ajax({
                // This targets the new Controller file for secure processing
                url: 'process_category_ajax.php',
                type: 'POST',
                data: new FormData(this), // Handles all form data, including files
                contentType: false,       // Required for file uploads via AJAX
                processData: false,       // Required for file uploads via AJAX
                dataType: 'json',

                success: function (response) {
                    // Check the JSON response (response.success is a boolean from the server)
                    if (response.success) {
                        messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');

                        // === CODE TO DISPLAY FOR 3 SECONDS ===
                        // Delay for 3000ms (3 seconds) then slide up (hide) over 500ms
                        messageBox.find('.alert-success').delay(3000).slideUp(500, function () {
                            $(this).remove(); // Remove the element from the DOM after hiding
                        });
                        // ======================================

                        // Clear the form fields on success
                        form[0].reset();
                        $('#categoryImage').next('.custom-file-label').html('Choose file...'); // Reset file input label
                    } else {
                        // Display error message
                        messageBox.html('<div class="alert alert-danger" role="alert">' + response.message + '</div>');
                    }
                },
                error: function (xhr, status, error) {
                    // Generic error message for network issues or server errors
                    messageBox.html('<div class="alert alert-danger" role="alert">An error occurred during the request. Please check the network tab.</div>');
                    console.log("AJAX Error:", status, error);
                },
                complete: function () {
                    // Re-enable button regardless of success or failure
                    submitButton.prop('disabled', false).text('Add Category');
                }
            });
        });
    });
</script>

<?php
include 'footer.php';
?>