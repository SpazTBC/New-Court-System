<?php
session_start();

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 text-center">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <i class='bx bx-check-circle text-success' style='font-size: 4rem;'></i>
                        <h2 class="mt-3">Successfully Logged Out</h2>
                        <p class="text-muted mb-4">Thank you for using our system</p>
                        <div class="d-grid gap-2">
                            <a href="../index.php" class="btn btn-primary btn-lg">
                                Return Home <i class='bx bx-home-alt ms-2'></i>
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                Login Again <i class='bx bx-log-in ms-2'></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent browser back button after logout
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>
