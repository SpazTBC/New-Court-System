<?php
session_start();
if(isset($_SESSION['username'])) {
    header("Location: /login/home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <img src="../images/logo.png" alt="Logo" class="img-fluid" style="max-height: 40px;">
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0">Login</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
                            <div class="alert alert-danger" role="alert">
                                Invalid username or password
                            </div>
                        <?php endif; ?>

                        <form method="post" action="login.php">
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bx-user'></i></span>
                                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class='bx bx-lock-alt'></i></span>
                                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="login_user" class="btn btn-primary btn-lg">
                                    Login <i class='bx bx-right-arrow-alt'></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <span class="text-muted">Don't have an account?</span>
                        <a href="../register" class="text-decoration-none">Register here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
