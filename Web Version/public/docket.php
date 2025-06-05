<?php
include("../include/database.php");

// Get all scheduled hearings for public display with judge information
try {
    $public_hearings_stmt = $conn->prepare("
        SELECT c.caseid, c.defendent, c.hearing_date, c.courtroom, c.hearing_status,
               COALESCE(u1.charactername, u2.charactername, u3.charactername, u4.charactername, 'TBD') as judge_name
        FROM cases c 
        LEFT JOIN users u1 ON c.shared01 = u1.username AND u1.job = 'Judge'
        LEFT JOIN users u2 ON c.shared02 = u2.username AND u2.job = 'Judge'  
        LEFT JOIN users u3 ON c.shared03 = u3.username AND u3.job = 'Judge'
        LEFT JOIN users u4 ON c.shared04 = u4.username AND u4.job = 'Judge'
        WHERE c.hearing_date IS NOT NULL 
        AND c.hearing_status IN ('scheduled', 'completed') 
        ORDER BY c.hearing_date ASC
    ");
    $public_hearings_stmt->execute();
    $public_hearings = $public_hearings_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $public_hearings = [];
    $error_message = "Unable to load court docket at this time.";
}

// Separate upcoming and past hearings
$upcoming_hearings = [];
$past_hearings = [];
$now = new DateTime();

foreach ($public_hearings as $hearing) {
    $hearing_date = new DateTime($hearing['hearing_date']);
    if ($hearing_date >= $now) {
        $upcoming_hearings[] = $hearing;
    } else {
        $past_hearings[] = $hearing;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Court Docket - Los Angeles County Superior Court</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --la-blue: #003f7f;
            --la-gold: #ffb81c;
            --la-dark-blue: #002a5c;
            --la-light-blue: #e6f2ff;
            --la-gray: #6c757d;
            --shadow-light: 0 2px 15px rgba(0, 63, 127, 0.08);
            --shadow-medium: 0 4px 25px rgba(0, 63, 127, 0.12);
            --shadow-heavy: 0 8px 40px rgba(0, 63, 127, 0.15);
        }

        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .court-header {
            background: linear-gradient(135deg, var(--la-blue) 0%, var(--la-dark-blue) 100%);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .court-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .court-header .container {
            position: relative;
            z-index: 2;
        }

        .court-seal {
            width: 80px;
            height: 80px;
            background: var(--la-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--la-dark-blue);
            font-weight: bold;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(255, 184, 28, 0.3);
        }

        .court-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .court-subtitle {
            font-size: 1.25rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .last-updated {
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 300;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-light);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--la-blue);
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            color: var(--la-gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-section {
            background: white;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-medium);
            padding: 2rem;
            margin-top: -3rem;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(0, 63, 127, 0.1);
        }

        .search-title {
            color: var(--la-dark-blue);
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--la-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 63, 127, 0.1);
        }

        .input-group-text {
            background: var(--la-light-blue);
            border: 2px solid #e2e8f0;
            border-right: none;
            color: var(--la-blue);
            border-radius: 0.75rem 0 0 0.75rem;
        }

        .disclaimer-section {
            background: linear-gradient(135deg, #fff7e6 0%, #fef3e2 100%);
            border: 1px solid #fed7aa;
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 2rem 0;
            position: relative;
        }

        .disclaimer-section::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--la-gold);
            border-radius: 2px 0 0 2px;
        }

        .disclaimer-title {
            color: #92400e;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .disclaimer-text {
            color: #92400e;
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .docket-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-light);
            border: 1px solid rgba(0, 63, 127, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .docket-card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--la-blue) 0%, var(--la-dark-blue) 100%);
            color: white;
            padding: 1.5rem 2rem;
            border: none;
            position: relative;
        }

        .card-header-custom h5 {
            font-weight: 600;
            margin: 0;
            font-size: 1.2rem;
        }

        .hearing-table {
            margin: 0;
        }

        .hearing-table thead th {
            background: var(--la-light-blue);
            color: var(--la-dark-blue);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            border: none;
        }

        .hearing-table tbody tr {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .hearing-table tbody tr:hover {
            background-color: #f8fafc;
            border-left-color: var(--la-blue);
            transform: translateX(2px);
        }

        .hearing-table tbody tr.today {
            border-left-color: var(--la-gold);
            background-color: #fffbeb;
        }

        .hearing-table tbody tr.completed {
            border-left-color: #10b981;
            opacity: 0.8;
        }

        .hearing-table tbody td {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .case-number {
            font-weight: 700;
            color: var(--la-dark-blue);
            font-size: 1rem;
        }

        .defendant-name {
            font-weight: 600;
            color: #374151;
        }

        .hearing-date {
            font-weight: 600;
            color: var(--la-blue);
        }

        .hearing-time {
            font-size: 0.85rem;
            color: var(--la-gray);
            font-weight: 500;
        }

        .courtroom-info, .judge-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .status-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .today-badge {
            background: var(--la-gold);
            color: var(--la-dark-blue);
            font-weight: 700;
            padding: 0.3rem 0.7rem;
            border-radius: 0.4rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--la-gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }

        .court-footer {
            background: var(--la-dark-blue);
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-section h6 {
            color: var(--la-gold);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-section p, .footer-section small {
            opacity: 0.9;
            line-height: 1.6;
        }

        .footer-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 2rem 0 1rem;
        }

        .footer-bottom {
            text-align: center;
            opacity: 0.7;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .court-title {
                font-size: 2rem;
            }
            
            .court-subtitle {
                font-size: 1.1rem;
            }
            
            .search-section {
                margin-top: -2rem;
                padding: 1.5rem;
            }
            
            .hearing-table thead th,
            .hearing-table tbody td {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 63, 127, 0.3);
            border-radius: 50%;
            border-top-color: var(--la-blue);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="court-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="court-seal me-3">
                            <i class='bx bx-shield-alt-2'></i>
                        </div>
                        <div>
                            <h1 class="court-title mb-0">Los Angeles County</h1>
                            <div class="court-subtitle">Superior Court of California</div>
                        </div>
                    </div>
                    <h2 class="h3 mb-2" style="font-weight: 500;">Public Court Calendar & Docket</h2>
                    <p class="last-updated mb-0">
                        <i class='bx bx-time-five me-2'></i>
                        Last Updated: <?php echo date('l, F j, Y \a\t g:i A'); ?>
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($upcoming_hearings); ?></div>
                        <div class="stats-label">Upcoming Hearings</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Search & Filter Section -->
        <div class="search-section fade-in">
            <h5 class="search-title">
                <i class='bx bx-search-alt me-2'></i>
                Search Court Calendar
            </h5>
            <div class="row align-items-end">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class='bx bx-search'></i>
                        </span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Search by case number, defendant name, or presiding judge...">
                    </div>
                </div>
                <div class="col-lg-4">
                    <select class="form-select" id="filterStatus">
                        <option value="">All Hearing Types</option>
                        <option value="scheduled">Scheduled Hearings</option>
                        <option value="completed">Completed Hearings</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Legal Disclaimer -->
        <div class="disclaimer-section fade-in">
            <h6 class="disclaimer-title">
                <i class='bx bx-info-circle me-2'></i>
                Important Legal Notice
            </h6>
            <p class="disclaimer-text">
                This calendar is provided for public information purposes only and is subject to change without notice. 
                Hearing times, dates, and courtroom assignments may be modified by the Court. For the most current 
                information, please contact the Clerk's Office at (213) 830-0800 or visit the courthouse in person. 
                Case details are limited in accordance with privacy regulations and California Rules of Court.
            </p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning border-0 rounded-3 shadow-sm fade-in">
                <i class='bx bx-error-circle me-2'></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Upcoming Hearings -->
        <div class="docket-card mb-4 fade-in">
            <div class="card-header-custom">
                <h5>
                    <i class='bx bx-calendar-event me-2'></i>
                    Upcoming Court Hearings
                    <span class="badge bg-light text-dark ms-2"><?php echo count($upcoming_hearings); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcoming_hearings)): ?>
                    <div class="empty-state">
                        <i class='bx bx-calendar-x'></i>
                        <h6>No Upcoming Hearings</h6>
                        <p class="mb-0">There are currently no hearings scheduled for the coming days.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table hearing-table" id="upcomingTable">
                            <thead>
                                <tr>
                                    <th>Case Number</th>
                                    <th>Defendant</th>
                                    <th>Date & Time</th>
                                    <th>Courtroom</th>
                                    <th>Presiding Judge</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_hearings as $hearing): ?>
                                    <?php
                                    $hearing_date = new DateTime($hearing['hearing_date']);
                                    $today = new DateTime('today');
                                    $is_today = $hearing_date->format('Y-m-d') === $today->format('Y-m-d');
                                    ?>
                                    <tr class="<?php echo $is_today ? 'today' : ''; ?>" data-status="<?php echo $hearing['hearing_status']; ?>">
                                        <td>
                                            <div class="case-number">
                                                <?php echo htmlspecialchars($hearing['caseid']); ?>
                                                <?php if ($is_today): ?>
                                                    <span class="today-badge">Today</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="defendant-name">
                                                <?php echo htmlspecialchars($hearing['defendent']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="hearing-date">
                                                <?php echo $hearing_date->format('M j, Y'); ?>
                                            </div>
                                            <div class="hearing-time">
                                                <?php echo $hearing_date->format('g:i A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="courtroom-info">
                                                <i class='bx bx-building'></i>
                                                <span><?php echo htmlspecialchars($hearing['courtroom'] ?? 'TBD'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="judge-info">
                                                <i class='bx bx-user-circle'></i>
                                                <span><?php echo htmlspecialchars($hearing['judge_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?php echo $hearing['hearing_status'] === 'scheduled' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($hearing['hearing_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Past Hearings -->
        <div class="docket-card mb-4 fade-in">
            <div class="card-header-custom" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">
                <h5>
                    <i class='bx bx-history me-2'></i>
                    Recent Court Proceedings
                    <span class="badge bg-light text-dark ms-2"><?php echo count($past_hearings); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($past_hearings)): ?>
                    <div class="empty-state">
                        <i class='bx bx-calendar-check'></i>
                        <h6>No Recent Hearings</h6>
                        <p class="mb-0">No completed hearings found in recent records.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table hearing-table" id="pastTable">
                            <thead>
                                <tr>
                                    <th>Case Number</th>
                                    <th>Defendant</th>
                                    <th>Date & Time</th>
                                    <th>Courtroom</th>
                                    <th>Presiding Judge</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($past_hearings, 0, 50) as $hearing): ?>
                                    <tr class="completed" data-status="<?php echo $hearing['hearing_status']; ?>">
                                        <td>
                                            <div class="case-number">
                                                <?php echo htmlspecialchars($hearing['caseid']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="defendant-name">
                                                <?php echo htmlspecialchars($hearing['defendent']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $past_date = new DateTime($hearing['hearing_date']);
                                            ?>
                                            <div class="hearing-date">
                                                <?php echo $past_date->format('M j, Y'); ?>
                                            </div>
                                            <div class="hearing-time">
                                                <?php echo $past_date->format('g:i A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="courtroom-info">
                                                <i class='bx bx-building'></i>
                                                <span><?php echo htmlspecialchars($hearing['courtroom'] ?? 'TBD'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="judge-info">
                                                <i class='bx bx-user-circle'></i>
                                                <span><?php echo htmlspecialchars($hearing['judge_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?php echo $hearing['hearing_status'] === 'completed' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($hearing['hearing_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($past_hearings) > 50): ?>
                        <div class="card-footer bg-light text-center py-3">
                            <small class="text-muted">
                                <i class='bx bx-info-circle me-1'></i>
                                Displaying most recent 50 completed hearings
                            </small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="court-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-section">
                        <h6>Los Angeles County Superior Court</h6>
                        <p>
                            The Superior Court of California, County of Los Angeles, serves the largest 
                            county in the United States with over 10 million residents across 38 courthouse locations.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="footer-section">
                        <h6>Contact Information</h6>
                        <p>
                            <i class='bx bx-phone me-2'></i>(213) 830-0800<br>
                            <i class='bx bx-map me-2'></i>111 N. Hill Street<br>
                            Los Angeles, CA 90012<br>
                            <i class='bx bx-time me-2'></i>Monday - Friday: 8:30 AM - 4:30 PM
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="footer-section">
                        <h6>Important Resources</h6>
                        <p>
                            <i class='bx bx-globe me-2'></i>Official Website: lacourt.org<br>
                            <i class='bx bx-help-circle me-2'></i>Self-Help Center Available<br>
                            <i class='bx bx-accessibility me-2'></i>ADA Accommodations Provided<br>
                            <i class='bx bx-translate me-2'></i>Interpreter Services Available
                        </p>
                    </div>
                </div>
            </div>
            <div class="footer-divider"></div>
            <div class="footer-bottom">
                <p class="mb-0">
                    © <?php echo date('Y'); ?> Superior Court of California, County of Los Angeles. 
                    All rights reserved. | Last Updated: <?php echo date('F j, Y g:i A'); ?>
                </p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced search functionality with loading states
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tables = ['upcomingTable', 'pastTable'];
            
            // Show loading state
            this.style.background = 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23666\' d=\'M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z\'%3E%3CanimateTransform attributeName=\'transform\' dur=\'0.75s\' repeatCount=\'indefinite\' type=\'rotate\' values=\'0 12 12;360 12 12\'/%3E%3C/path%3E%3C/svg%3E") no-repeat right 12px center';
            
            setTimeout(() => {
                tables.forEach(tableId => {
                    const table = document.getElementById(tableId);
                    if (table) {
                        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                        let visibleCount = 0;
                        
                        for (let row of rows) {
                            const text = row.textContent.toLowerCase();
                            const isVisible = text.includes(searchTerm);
                            row.style.display = isVisible ? '' : 'none';
                            if (isVisible) visibleCount++;
                        }
                        
                        // Update table visibility
                        const tableCard = table.closest('.docket-card');
                        if (tableCard) {
                            const badge = tableCard.querySelector('.badge');
                            if (badge) badge.textContent = visibleCount;
                        }
                    }
                });
                
                // Remove loading state
                this.style.background = '';
            }, 200);
        });

        // Enhanced status filter with smooth transitions
        document.getElementById('filterStatus').addEventListener('change', function() {
            const filterValue = this.value;
            const tables = ['upcomingTable', 'pastTable'];
            
            tables.forEach(tableId => {
                const table = document.getElementById(tableId);
                if (table) {
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    let visibleCount = 0;
                    
                    for (let row of rows) {
                        const status = row.getAttribute('data-status');
                        const shouldShow = !filterValue || status === filterValue;
                        
                        if (shouldShow) {
                            row.style.display = '';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.style.transition = 'opacity 0.3s ease';
                                row.style.opacity = '1';
                            }, visibleCount * 50);
                            visibleCount++;
                        } else {
                            row.style.transition = 'opacity 0.2s ease';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.style.display = 'none';
                            }, 200);
                        }
                    }
                    
                    // Update badge count
                    const tableCard = table.closest('.docket-card');
                    if (tableCard) {
                        const badge = tableCard.querySelector('.badge');
                        if (badge) badge.textContent = visibleCount;
                    }
                }
            });
        });

        // Auto-refresh with user notification
        let refreshTimer;
        let refreshCountdown = 300; // 5 minutes

        function startRefreshCountdown() {
            refreshTimer = setInterval(() => {
                refreshCountdown--;
                
                if (refreshCountdown <= 30 && refreshCountdown > 0) {
                    // Show countdown notification
                    if (!document.getElementById('refresh-notification')) {
                        const notification = document.createElement('div');
                        notification.id = 'refresh-notification';
                        notification.className = 'alert alert-info position-fixed';
                        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                        notification.innerHTML = `
                            <div class="d-flex align-items-center">
                                <div class="loading-spinner me-2"></div>
                                <div>
                                    <strong>Auto-refresh in ${refreshCountdown}s</strong><br>
                                    <small>Page will update automatically</small>
                                </div>
                                <button type="button" class="btn-close ms-auto" onclick="cancelRefresh()"></button>
                            </div>
                        `;
                        document.body.appendChild(notification);
                    } else {
                        document.querySelector('#refresh-notification strong').textContent = `Auto-refresh in ${refreshCountdown}s`;
                    }
                }
                
                if (refreshCountdown <= 0) {
                    location.reload();
                }
            }, 1000);
        }

        function cancelRefresh() {
            clearInterval(refreshTimer);
            const notification = document.getElementById('refresh-notification');
            if (notification) {
                notification.remove();
            }
            refreshCountdown = 300; // Reset timer
            setTimeout(startRefreshCountdown, 60000); // Restart in 1 minute
        }

        // Start the refresh countdown
        setTimeout(startRefreshCountdown, 1000);

        // Smooth scroll for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling to all anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add intersection observer for fade-in animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all fade-in elements
            document.querySelectorAll('.fade-in').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });

            // Add click tracking for analytics (optional)
            document.querySelectorAll('.hearing-table tbody tr').forEach(row => {
                row.addEventListener('click', function() {
                    const caseNumber = this.querySelector('.case-number').textContent.trim();
                    console.log(`Case ${caseNumber} row clicked`); // Replace with actual analytics
                });
            });

            // Keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                }
            });

            // Add tooltips for better accessibility
            const tooltipElements = document.querySelectorAll('[title]');
            tooltipElements.forEach(el => {
                el.setAttribute('data-bs-toggle', 'tooltip');
            });

            // Initialize Bootstrap tooltips if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });

        // Print functionality
        function printDocket() {
            const printWindow = window.open('', '_blank');
            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>LA County Court Docket - ${new Date().toLocaleDateString()}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #003f7f; padding-bottom: 20px; }
                        .court-title { color: #003f7f; font-size: 24px; font-weight: bold; margin: 0; }
                        .subtitle { color: #666; margin: 5px 0; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; font-weight: bold; }
                        .case-number { font-weight: bold; }
                        .today { background-color: #fff3cd; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="court-title">Los Angeles County Superior Court</div>
                        <div class="subtitle">Public Court Calendar</div>
                        <div class="subtitle">Generated: ${new Date().toLocaleString()}</div>
                    </div>
                    ${document.querySelector('#upcomingTable') ? document.querySelector('#upcomingTable').parentElement.outerHTML : ''}
                    <div class="footer">
                        <p>This document was generated from the LA County Superior Court public docket system.</p>
                        <p>For the most current information, please visit the courthouse or call (213) 830-0800.</p>
                    </div>
                </body>
                </html>
            `;
            
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        // Add print button (optional)
        const printButton = document.createElement('button');
        printButton.className = 'btn btn-outline-primary btn-sm position-fixed';
        printButton.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000;';
        printButton.innerHTML = '<i class="bx bx-printer me-1"></i> Print';
        printButton.onclick = printDocket;
        document.body.appendChild(printButton);

        // Mobile menu improvements
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.hearing-table').forEach(table => {
                table.style.fontSize = '0.85rem';
            });
        }

        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`Page loaded in ${Math.round(loadTime)}ms`);
            
            // Optional: Send performance data to analytics
            if (loadTime > 3000) {
                console.warn('Page load time is slow, consider optimization');
            }
        });
    </script>
</body>
</html>