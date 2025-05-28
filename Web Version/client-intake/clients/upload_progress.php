<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// This file can be used to check upload progress for large files
// PHP's built-in upload progress tracking

if(isset($_GET['progress_key'])) {
    $key = $_GET['progress_key'];
    
    // Check if upload progress tracking is enabled
    if(ini_get('session.upload_progress.enabled')) {
        $progress_key = ini_get('session.upload_progress.prefix') . $key;
        
        if(isset($_SESSION[$progress_key])) {
            $progress = $_SESSION[$progress_key];
            
            $response = [
                'uploaded' => $progress['bytes_processed'],
                'total' => $progress['content_length'],
                'percentage' => round(($progress['bytes_processed'] / $progress['content_length']) * 100, 2),
                'speed' => 0, // Could calculate based on time
                'eta' => 0    // Could calculate estimated time remaining
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['error' => 'No progress data found']);
        }
    } else {
        echo json_encode(['error' => 'Upload progress tracking not enabled']);
    }
} else {
    echo json_encode(['error' => 'No progress key provided']);
}
?>