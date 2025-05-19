<?php
session_start();

if(isset($_SESSION['success']))
{
    header("Location: login/");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <img src="images/logo.png" alt="Logo" class="img-fluid" style="max-height: 50px;">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h1 class="text-center mb-4">Welcome to the First ever <span class="text-primary">FiveM Court System</span></h1>
                        <p class="lead text-center">
                            This court system is built with modern web technologies including HTML, PHP, and CSS. 
                            It features robust functionality including:
                        </p>
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item">Staff Banning System</li>
                            <li class="list-group-item">Database-controlled Bans, Cases, and Jobs</li>
                            <li class="list-group-item">Case sharing between lawyers and judges</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('include/footer.php'); ?>

</body>
</html>
