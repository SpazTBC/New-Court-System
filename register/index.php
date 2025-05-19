<?php
$menu = "REGISTER";
include("../include/database.php");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div id="menu">
        <div class='logo'>
            <img src="../images/logo.png" class="img-fluid"/>
        </div>
        <?php include("../include/menu.php"); ?>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">REGISTER</h1>
                        <form method="POST" action="register.php">
                            <div class="mb-3">
                                <input type="text" class="form-control" name="username" placeholder="Username" required>
                            </div>
                            
                            <div class="mb-3">
                                <input type="text" class="form-control" name="character" placeholder="Character Name(First Last)" required>
                            </div>
                            
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                            
                            <div class="mb-3">
                                <input type="text" class="form-control" name="jobs" id="jobs" placeholder="Civilian, Attorney, Judge" required>
                            </div>
                            
                            <input type="hidden" name="ip" id="ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));?>
</body>
</html>
