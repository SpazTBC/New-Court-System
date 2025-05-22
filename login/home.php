<?php
session_start();
$menu = "HOME";
include("../include/database.php");

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Get user information including job
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set a flag for attorney status - only true if job is Attorney AND job is approved
$isAttorney = ($user['job'] === "Attorney" && $user['job_approved'] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;"> -->
                <span class="fw-bold">Blackwood & Associates</span>
                <span class="ms-2">| Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row g-4">
            <!-- Statistics Cards -->
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Cases</h5>
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM cases");
                        $cases_count = $stmt->fetchColumn();
                        ?>
                        <h2 class="card-text"><?php echo $cases_count; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Supervisors</h5>
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE supervisorjob = '1'");
                        $supervisor_count = $stmt->fetchColumn();
                        ?>
                        <h2 class="card-text"><?php echo $supervisor_count ?: 'None'; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <?php
                        $stmt = $conn->query("SELECT COUNT(*) FROM users");
                        $users_count = $stmt->fetchColumn();
                        ?>
                        <h2 class="card-text"><?php echo $users_count; ?></h2>
                    </div>
                </div>
            </div>

            <!-- User Profile Section -->
            <div class="col-12 mt-4">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0">Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                        $stmt->execute([$_SESSION['username']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <tr>
                                        <th>ID</th>
                                        <td><?php echo htmlspecialchars($user['userid']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Character Name</th>
                                        <td><?php echo htmlspecialchars($user['charactername']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Username</th>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Job</th>
                                        <td>
                                            <?php if(isset($user['job_approved']) && $user['job_approved'] == 0 && $user['job'] !== "Civilian"): ?>
                                                Civilian <span class="badge bg-warning">Your Job (<?php echo htmlspecialchars($user['job']); ?>) is Pending Approval</span>
                                            <?php else: ?>
                                                <?php echo $user['job'] === "Civilian" ? "Unemployed" : htmlspecialchars($user['job']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php if ($user['banned'] === "1"): ?>
                                                <span class="badge bg-danger">Banned</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Staff</th>
                                        <td>
                                            <?php
                                                $ip = $_SERVER['REMOTE_ADDR'];
                                                if ($user['staff'] == "1") {
                                                    if ($user['username'] === "Shawn") {
                                                        echo '<span class="badge bg-primary">Creator</span>';
                                                    } else {
                                                        echo '<span class="badge bg-info">Staff</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-secondary">No</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
