<?php
session_start();
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

$log_file = __DIR__ . '/upload_debug.log';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Debug Log</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .log { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; white-space: pre-wrap; }
        .clear-btn { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Upload Debug Log</h2>
    
    <?php if (isset($_GET['clear']) && $_GET['clear'] == '1'): ?>
        <?php file_put_contents($log_file, ''); ?>
        <p style="color: green;">Log cleared!</p>
    <?php endif; ?>
    
    <div class="clear-btn">
        <a href="?clear=1" onclick="return confirm('Clear the log file?')">Clear Log</a> | 
        <a href="javascript:location.reload()">Refresh</a>
    </div>
    
    <div class="log">
        <?php
        if (file_exists($log_file)) {
            echo htmlspecialchars(file_get_contents($log_file));
        } else {
            echo "No log file found yet.";
        }
        ?>
    </div>
</body>
</html>