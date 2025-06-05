<?php
// Get upcoming hearings for dashboard widget
$upcoming_hearings_stmt = $conn->prepare("
    SELECT c.*, u.charactername as creator_name 
    FROM cases c 
    LEFT JOIN users u ON c.creator = u.username 
    WHERE (c.shared1 = ? OR c.shared2 = ? OR c.shared3 = ? OR c.shared4 = ? OR c.creator = ?) 
    AND c.hearing_date IS NOT NULL 
    AND c.hearing_date >= NOW() 
    AND c.hearing_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY c.hearing_date ASC 
    LIMIT 5
");
$username = $_SESSION['username'];
$upcoming_hearings_stmt->execute([$username, $username, $username, $username, $username]);
$upcoming_hearings = $upcoming_hearings_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0"><i class='bx bx-calendar-event'></i> Upcoming Hearings (Next 7 Days)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($upcoming_hearings)): ?>
            <div class="p-3 text-center text-muted">
                <i class='bx bx-calendar-check' style="font-size: 2rem;"></i>
                <p class="mb-0 mt-2">No hearings scheduled</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($upcoming_hearings as $hearing): ?>
                    <?php
                    $hearing_date = new DateTime($hearing['hearing_date']);
                    $now = new DateTime();
                    $is_today = $hearing_date->format('Y-m-d') === $now->format('Y-m-d');
                    ?>
                    <div class="list-group-item <?php echo $is_today ? 'bg-warning bg-opacity-25' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">Case #<?php echo htmlspecialchars($hearing['casenum']); ?></h6>
                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($hearing['defendent']); ?></p>
                                <small class="text-muted">
                                    <i class='bx bx-time'></i> <?php echo $hearing_date->format('M j, g:i A'); ?> - 
                                    <i class='bx bx-building'></i> <?php echo htmlspecialchars($hearing['courtroom']); ?>
                                </small>
                            </div>
                            <?php if ($is_today): ?>
                                <span class="badge bg-warning">Today</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer text-center">
                <a href="hearings/index.php" class="btn btn-sm btn-outline-primary">
                    <i class='bx bx-calendar'></i> View All Hearings
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>