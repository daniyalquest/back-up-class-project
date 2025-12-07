<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SB Admin 2 - Register</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                            <div class="col-lg-7">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
                                    </div>

                                    <div id="auth_response_message"></div> 
                                    
                                    <form id="registerForm" class="user" onsubmit="return false;">
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user" id="exampleInputUser" name="username" placeholder="Username" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user" id="exampleInputEmail" name="email" placeholder="Email Address" required>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-sm-6 mb-3 mb-sm-0">
                                                <input type="password" class="form-control form-control-user" id="exampleInputPassword" name="password" placeholder="Password (min 6 chars)" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <input type="password" class="form-control form-control-user" id="exampleRepeatPassword" placeholder="Repeat Password" required>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block" id="submitButton">
                                            Register Account
                                        </button>
                                        <hr>
                                        </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="login.php">Already have an account? Login!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#registerForm').on('submit', function(e) {
                e.preventDefault(); 

                var form = $(this);
                var submitButton = $('#submitButton');
                var messageBox = $('#auth_response_message');
                
                // Client-side password validation
                if ($('#exampleInputPassword').val() !== $('#exampleRepeatPassword').val()) {
                    messageBox.html('<div class="alert alert-danger" role="alert">Passwords do not match.</div>');
                    return; 
                }

                submitButton.prop('disabled', true).text('Registering...');
                messageBox.html('');

                $.ajax({
                    url: 'process_register_ajax.php', 
                    type: 'POST',
                    data: form.serialize(), 
                    dataType: 'json', 
                    
                    success: function(response) {
                        if (response.success) {
                            messageBox.html('<div class="alert alert-success" role="alert">' + response.message + '</div>');
                            // Redirect to login page after a delay
                            setTimeout(function() {
                                window.location.href = 'login.php'; 
                            }, 2000); 
                        } else {
                            messageBox.html('<div class="alert alert-danger" role="alert">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        messageBox.html('<div class="alert alert-danger" role="alert">Request Failed: Check network connection.</div>');
                    },
                    complete: function() {
                        submitButton.prop('disabled', false).text('Register Account');
                    }
                });
            });
        });
    </script>
</body>

</html>