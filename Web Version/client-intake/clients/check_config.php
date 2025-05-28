<?php
session_start();
// Check if user is logged in and is admin
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../../include/database.php";

// Check if user is admin
$stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['job'] !== "Admin") {
    header("Location: /login/home.php");
    exit();
}

// Get PHP configuration values
$upload_max_filesize = ini_get('upload_max_filesize');
$post_max_size = ini_get('post_max_size');
$max_execution_time = ini_get('max_execution_time');
$max_input_time = ini_get('max_input_time');
$memory_limit = ini_get('memory_limit');

// Convert to bytes for comparison
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

$upload_bytes = convertToBytes($upload_max_filesize);
$post_bytes = convertToBytes($post_max_size);
$memory_bytes = convertToBytes($memory_limit);
$target_size = 20 * 1024 * 1024; // 20MB
$menu = "CLIENTS";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Configuration Check - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class='bx bx-cog'></i> System Configuration Check</h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">This page shows the current PHP configuration for file uploads. For optimal performance with large files (up to 50MB), ensure all values meet the recommended settings.</p>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Setting</th>
                                        <th>Current Value</th>
                                        <th>Recommended</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>upload_max_filesize</strong></td>
                                        <td><?php echo $upload_max_filesize; ?></td>
                                        <td>50M or higher</td>
                                        <td>
                                            <?php if($upload_bytes >= $target_size): ?>
                                                <span class="badge bg-success"><i class='bx bx-check'></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class='bx bx-x'></i> Too Low</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>post_max_size</strong></td>
                                        <td><?php echo $post_max_size; ?></td>
                                        <td>52M or higher</td>
                                        <td>
                                            <?php if($post_bytes >= ($target_size + 2*1024*1024)): ?>
                                                <span class="badge bg-success"><i class='bx bx-check'></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class='bx bx-x'></i> Too Low</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>max_execution_time</strong></td>
                                        <td><?php echo $max_execution_time; ?> seconds</td>
                                        <td>300 seconds or higher</td>
                                        <td>
                                            <?php if($max_execution_time >= 300 || $max_execution_time == 0): ?>
                                                <span class="badge bg-success"><i class='bx bx-check'></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class='bx bx-error'></i> Low</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>max_input_time</strong></td>
                                        <td><?php echo $max_input_time; ?> seconds</td>
                                        <td>300 seconds or higher</td>
                                        <td>
                                            <?php if($max_input_time >= 300 || $max_input_time == -1): ?>
                                                <span class="badge bg-success"><i class='bx bx-check'></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class='bx bx-error'></i> Low</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>memory_limit</strong></td>
                                        <td><?php echo $memory_limit; ?></td>
                                        <td>256M or higher</td>
                                        <td>
                                            <?php if($memory_bytes >= (256*1024*1024)): ?>
                                                <span class="badge bg-success"><i class='bx bx-check'></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class='bx bx-error'></i> Low</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <h5>Configuration Notes:</h5>
                            <ul class="list-unstyled">
                                <li><i class='bx bx-info-circle text-info'></i> <strong>upload_max_filesize:</strong> Maximum size for uploaded files</li>
                                <li><i class='bx bx-info-circle text-info'></i> <strong>post_max_size:</strong> Should be slightly larger than upload_max_filesize</li>
                                <li><i class='bx bx-info-circle text-info'></i> <strong>max_execution_time:</strong> Time limit for script execution (0 = unlimited)</li>
                                <li><i class='bx bx-info-circle text-info'></i> <strong>max_input_time:</strong> Time limit for input parsing (-1 = unlimited)</li>
                                <li><i class='bx bx-info-circle text-info'></i> <strong>memory_limit:</strong> Maximum memory a script can use</li>
                            </ul>
                        </div>

                        <?php 
                        $all_good = ($upload_bytes >= $target_size) && 
                                   ($post_bytes >= ($target_size + 2*1024*1024)) && 
                                   ($max_execution_time >= 300 || $max_execution_time == 0) && 
                                   ($max_input_time >= 300 || $max_input_time == -1) && 
                                   ($memory_bytes >= (256*1024*1024));
                        ?>

                        <?php if(!$all_good): ?>
                            <div class="alert alert-warning mt-4" role="alert">
                                <h6><i class='bx bx-error-circle'></i> Configuration Issues Detected</h6>
                                <p>Some PHP settings may prevent large file uploads from working properly. Contact your system administrator to update the following in your php.ini file:</p>
                                <pre class="bg-light p-3 rounded">
upload_max_filesize = 50M
post_max_size = 52M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M</pre>
                                <p class="mb-0"><small>After making changes, restart your web server for the settings to take effect.</small></p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mt-4" role="alert">
                                <h6><i class='bx bx-check-circle'></i> Configuration Optimal</h6>
                                <p class="mb-0">Your server is properly configured for large file uploads up to 50MB.</p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Client Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>