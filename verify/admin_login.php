<?php
include("config/config.php");

session_start();

if (isset($_SESSION['verified']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header("Location: admin.php");
    exit;
}

//$client_id = '1280933224352190637';
//$redirect_uri = 'http://localhost/verify/admin_callback.php';

$auth_url = "https://discord.com/api/oauth2/authorize?client_id=$client_id&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope=identify";

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Admin Login</h1>
        <div class="text-center">
            <a href="<?php echo $auth_url; ?>" class="btn btn-primary btn-lg">Login with Discord</a>
        </div>
    </div>
</body>
</html>