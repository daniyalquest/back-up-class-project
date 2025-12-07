<?php
include 'header.php';
require_once 'classes/Category.class.php';

$category_model = new Category();
$categories = $category_model->getAllCategories();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">View All Categories</h1>
        <a href="addcategory.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Category
        </a>
    </div>
    <div id="ajax_response_message"></div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Category Management</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr data-id="<?php echo htmlspecialchars($cat['id']); ?>">
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($cat['description'])); ?></td>
                                <td>
                                    <?php if (!empty($cat['image']) && file_exists($cat['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($cat['image']); ?>"
                                            alt="<?php echo htmlspecialchars($cat['name']); ?>"
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_category.php?id=<?php echo $cat['id']; ?>"
                                        class="btn btn-sm btn-info btn-circle mr-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-circle delete-btn"
                                        data-id="<?php echo $cat['id']; ?>" title="Delete">
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
        // ----------------------------------------------------
        // DELETE Category AJAX Logic
        // ----------------------------------------------------
        $(document).on('click', '.delete-btn', function () {
            var categoryId = $(this).data('id');
            var row = $(this).closest('tr');
            var messageBox = $('#ajax_response_message');

            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                $.ajax({
                    url: 'process_category_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'delete', // Tell the controller what to do
                        id: categoryId
                    },
                    success: function (response) {
                        if (response.success) {
                            messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');

                            // Remove the row from the table
                            // If using DataTables, destroy and redraw might be needed for cleaner removal
                            var table = $('#dataTable').DataTable();
                            table.row(row).remove().draw(false);

                        } else {
                            messageBox.html('<div class="alert alert-danger" role="alert">' + response.message + '</div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        messageBox.html('<div class="alert alert-danger" role="alert">Server Error: Could not process request.</div>');
                    }
                });
            }
        });
    });
</script>

<?php include 'footer.php'; ?>