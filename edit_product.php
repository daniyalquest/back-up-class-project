<?php
include 'header.php';
require_once 'classes/Product.class.php';
require_once 'classes/Category.class.php';
require_once 'security_helpers.php'; // Required for CSRF token

$product_model = new Product();
$category_model = new Category();

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// 1. Fetch the product and categories
if ($product_id) {
    $product = $product_model->getProductById($product_id);
    $categories = $category_model->getAllCategoriesForDropdown();
} else {
    $product = null;
}

if (!$product) {
    echo '<div class="alert alert-danger">Product not found or missing ID.</div>';
    include 'footer.php';
    exit;
}

// XSS Protection: Escape all data fetched from the database
$id = htmlspecialchars($product['id']);
$category_id = htmlspecialchars($product['category_id']);
$name = htmlspecialchars($product['name']);
$description = htmlspecialchars($product['description']);
$price = htmlspecialchars($product['price']);
$image = htmlspecialchars($product['image']);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Edit Product: <?php echo $name; ?></h1>
    <div class="row justify-content-center">
        <div class="col-lg-8"> 
            
            <div id="ajax_response_message"></div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Product Details</h6>
                </div>
                <div class="card-body">
                  
                  <form id="editProductForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                    
                    <?php echo csrf_token_field(); ?>
                    
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="old_image" value="<?php echo $image; ?>">

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select a Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['id']); ?>" 
                                    <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="productName">Product Name</label>
                        <input type="text" class="form-control" id="productName" name="product_name" value="<?php echo $name; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productDescription">Description</label>
                        <textarea class="form-control" id="productDescription" name="product_description" rows="3"><?php echo $description; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="productPrice">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="productPrice" name="product_price" value="<?php echo $price; ?>" required min="0.01">
                    </div>

                    <div class="form-group">
                        <label>Current Image</label><br>
                        <?php if (!empty($image) && file_exists($image)): ?>
                            <img src="<?php echo $image; ?>" style="max-width: 150px; max-height: 150px; margin-bottom: 10px; display: block;">
                        <?php else: ?>
                            <p>No image uploaded.</p>
                        <?php endif; ?>

                        <label for="productImage">Replace Image (Optional)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="productImage" name="product_image" accept="image/*">
                            <label class="custom-file-label" for="productImage">Choose new file...</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success" id="submitButton">Update Product</button>
                    <a href="view_products.php" class="btn btn-secondary">Cancel</a>
                  </form> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Update label for custom file input
        $('#productImage').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });

        // AJAX Form Submission Script for Update
        $('#editProductForm').on('submit', function(e) {
            e.preventDefault(); 

            var form = $(this);
            var submitButton = $('#submitButton');
            var messageBox = $('#ajax_response_message');
            
            submitButton.prop('disabled', true).text('Updating...');
            messageBox.html('');

            $.ajax({
                url: 'process_product_ajax.php', 
                type: 'POST',
                data: new FormData(this), 
                contentType: false,       
                processData: false,       
                dataType: 'json', 
                
                success: function(response) {
                    if (response.success) {
                        messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');
                        $('#productImage').next('.custom-file-label').html('Choose new file...'); 
                        
                        // Optional: Redirect back to the view page after a few seconds
                        setTimeout(function() {
                            window.location.href = 'view_products.php';
                        }, 2000);

                    } else {
                        var errorMessage = response && response.message ? response.message : "Unknown error occurred.";
                        messageBox.html('<div class="alert alert-danger" role="alert">' + errorMessage + '</div>');
                    }
                },
                error: function() {
                    messageBox.html('<div class="alert alert-danger" role="alert">Request Failed: An unexpected server-side error occurred.</div>');
                },
                complete: function() {
                    submitButton.prop('disabled', false).text('Update Product');
                }
            });
        });
    });
</script>

<?php include 'footer.php'; ?>