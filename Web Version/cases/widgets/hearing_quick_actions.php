<?php
// Get count of cases without hearings
$no_hearing_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM cases c 
    WHERE (c.shared1 = ? OR c.shared2 = ? OR c.shared3 = ? OR c.shared4 = ? OR c.creator = ?) 
    AND (c.hearing_date IS NULL OR c.hearing_date = '')
");
$username = $_SESSION['username'];
$no_hearing_stmt->execute([$username, $username, $username, $username, $username]);
$no_hearing_count = $no_hearing_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get count of upcoming hearings in next 7 days
$upcoming_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM cases c 
    WHERE (c.shared1 = ? OR c.shared2 = ? OR c.shared3 = ? OR c.shared4 = ? OR c.creator = ?) 
    AND c.hearing_date IS NOT NULL 
    AND c.hearing_date >= NOW() 
    AND c.hearing_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
");
$upcoming_stmt->execute([$username, $username, $username, $username, $username]);
$upcoming_count = $upcoming_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<div class="card shadow-sm">
    <div class="card-header bg-info text-white">
        <h6 class="mb-0"><i class='bx bx-calendar-star'></i> Hearing Quick Actions</h6>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                    <div class="h4 mb-1 text-warning"><?php echo $no_hearing_count; ?></div>
                    <small class="text-muted">Cases Need Hearing</small>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center p-2 bg-light rounded">
                    <div class="h4 mb-1 text-primary"><?php echo $upcoming_count; ?></div>
                    <small class="text-muted">Upcoming (7 days)</small>
                </div>
            </div>
        </div>
        
        <div class="d-grid gap-2 mt-3">
            <a href="hearings/schedule.php" class="btn btn-primary btn-sm">
                <i class='bx bx-calendar-plus'></i> Schedule Hearing
            </a>
            <a href="hearings/index.php" class="btn btn-outline-secondary btn-sm">
                <i class='bx bx-calendar-event'></i> View All Hearings
            </a>
        </div>
    </div>
</div>