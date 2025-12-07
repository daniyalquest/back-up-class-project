<?php
include 'header.php';
// Use the updated path fix for included files
require_once 'classes/Category.class.php';

// Instantiate the Category Model to fetch data for the dropdown
$category_model = new Category();
$categories = $category_model->getAllCategories();
?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div id="ajax_response_message"></div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Product</h6>
                </div>
                <div class="card-body">

                    <form id="addProductForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">

                        <div class="form-group">
                            <label for="productCategory">Category</label>
                            <select class="form-control" id="productCategory" name="product_category" required>
                                <option value="">Select a Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['id']); ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="productName">Product Name</label>
                            <input type="text" class="form-control" id="productName" name="product_name" required>
                        </div>

                        <div class="form-group">
                            <label for="productPrice">Price ($)</label>
                            <input type="number" step="0.01" class="form-control" id="productPrice" name="product_price"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="productDescription">Description</label>
                            <textarea class="form-control" id="productDescription" name="product_description"
                                rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="productImage">Image</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="productImage" name="product_image"
                                    accept="image/*">
                                <label class="custom-file-label" for="productImage">Choose file...</label>
                            </div>
                        </div>

                        <button type="submit" name="submit_product" class="btn btn-primary" id="submitButton">Add
                            Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // ... (File input display logic for Bootstrap's custom-file-input - copy from addcategory.php) ...

        $('#productImage').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });

        // AJAX Form Submission Script
        $('#addProductForm').on('submit', function (e) {
            e.preventDefault();

            var form = $(this);
            var submitButton = $('#submitButton');
            var messageBox = $('#ajax_response_message');

            submitButton.prop('disabled', true).text('Adding...');
            messageBox.html('');

            $.ajax({
                // TARGET THE NEW PRODUCT CONTROLLER
                url: 'process_product_ajax.php',
                type: 'POST',
                data: new FormData(this),
                contentType: false,
                processData: false,
                dataType: 'json',

                success: function (response) {
                    if (response.success) {
                        messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');
                        messageBox.find('.alert-success').delay(3000).slideUp(500, function () {
                            $(this).remove();
                        });
                        form[0].reset();
                        $('#productImage').next('.custom-file-label').html('Choose file...'); // Reset file input label
                        $('#productCategory').val(''); // Reset select dropdown
                    } else {
                        var errorMessage = response && response.message ? response.message : "Unknown error occurred.";
                        messageBox.html('<div class="alert alert-danger" role="alert">' + errorMessage + '</div>');
                    }
                },
                error: function (xhr, status, error) {
                    messageBox.html('<div class="alert alert-danger" role="alert">Request Failed: An unexpected server-side error occurred.</div>');
                    console.log("AJAX Error:", status, error, xhr.responseText);
                },
                complete: function () {
                    submitButton.prop('disabled', false).text('Add Product');
                }
            });
        });
    });
</script>

<?php
include 'footer.php';
?>