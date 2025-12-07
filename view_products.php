<?php
include 'header.php';
require_once 'classes/Product.class.php';

$product_model = new Product();
$products = $product_model->getAllProducts();

// Include the CSRF utility for the AJAX delete token
require_once 'security_helpers.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">View All Products</h1>
        <a href="add_product.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Product
        </a>
    </div>

    <div id="ajax_response_message"></div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Product Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price (PKR)</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prod['id']); ?></td>
                                <td><?php echo htmlspecialchars($prod['name']); ?></td>
                                <td><?php echo htmlspecialchars($prod['category_name']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($prod['price'], 2)); ?></td>
                                <td>
                                    <?php if (!empty($prod['image']) && file_exists($prod['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($prod['image']); ?>"
                                            alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $prod['id']; ?>"
                                        class="btn btn-sm btn-info btn-circle mr-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-circle delete-btn"
                                        data-id="<?php echo $prod['id']; ?>" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<script src="js/demo/datatables-demo.js"></script>

<script>
    $(document).ready(function () {
        // AJAX DELETE Logic
        $(document).on('click', '.delete-btn', function () {
            var productId = $(this).data('id');
            var row = $(this).closest('tr');
            var messageBox = $('#ajax_response_message');

            if (confirm('Are you sure you want to delete this product?')) {
                $.ajax({
                    url: 'process_product_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete',
                        id: productId,
                        // Send the token for the DELETE action
                        csrf_token: '<?php echo generate_csrf_token(); ?>'
                    },
                    success: function (response) {
                        if (response.success) {
                            messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');
                            // Remove the row from the table
                            var table = $('#dataTable').DataTable();
                            table.row(row).remove().draw(false);
                        } else {
                            messageBox.html('<div class="alert alert-danger" role="alert">' + response.message + '</div>');
                        }
                    },
                    error: function () {
                        messageBox.html('<div class="alert alert-danger" role="alert">Server Error: Could not process request.</div>');
                    }
                });
            }
        });
    });
</script>

<?php include 'footer.php'; ?>