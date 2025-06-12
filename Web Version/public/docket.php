<?php
include("../include/database.php");

// Set the correct timezone for your server
date_default_timezone_set('America/Los_Angeles'); // PDT/PST timezone

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
    <link href="../css/dark-mode.css" rel="stylesheet">
    <script src="../js/dark-mode.js"></script>
    <style>
    :root {
        --primary-blue: #1e40af;
        --primary-gold: #f59e0b;
        --dark-blue: #1e3a8a;
        --light-blue: #eff6ff;
        --text-gray: #64748b;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
    }

    * {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    /* Modern Header Design */
    .docket-header {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        padding: 3rem 0;
        position: relative;
        overflow: hidden;
    }

    .docket-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    }

    .docket-header .container {
        position: relative;
        z-index: 2;
    }

    .court-badge {
        background: var(--primary-gold);
        color: var(--dark-blue);
        padding: 1rem;
        border-radius: 50%;
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        box-shadow: var(--shadow-lg);
        margin-bottom: 1rem;
    }

    .court-title {
        font-size: 2.75rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .court-subtitle {
        font-size: 1.25rem;
        font-weight: 500;
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .last-updated {
        font-size: 0.9rem;
        opacity: 0.8;
        font-weight: 400;
    }

    /* Stats Card */
    .stats-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 1.5rem;
        padding: 2rem;
        text-align: center;
        box-shadow: var(--shadow-xl);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .stats-number {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary-blue);
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .stats-label {
        font-size: 0.9rem;
        color: var(--text-gray);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Search Section */
    .search-box {
        background: white;
        border-radius: 1.5rem;
        box-shadow: var(--shadow-xl);
        padding: 2rem;
        margin-top: -3rem;
        position: relative;
        z-index: 10;
        border: 1px solid var(--border-color);
    }

    .search-title {
        color: var(--dark-blue);
        font-weight: 700;
        margin-bottom: 1.5rem;
        font-size: 1.25rem;
    }

    .form-control, .form-select {
        border: 2px solid var(--border-color);
        border-radius: 1rem;
        padding: 0.875rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.1);
        transform: translateY(-1px);
    }

    .input-group-text {
        background: var(--light-blue);
        border: 2px solid var(--border-color);
        border-right: none;
        color: var(--primary-blue);
        border-radius: 1rem 0 0 1rem;
        font-size: 1.1rem;
    }

    /* Disclaimer */
    .disclaimer {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #f59e0b;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 2rem 0;
        position: relative;
        overflow: hidden;
    }

    .disclaimer::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--primary-gold);
    }

    .disclaimer h6 {
        color: #92400e;
        font-weight: 700;
        margin-bottom: 0.75rem;
        font-size: 1.1rem;
    }

    .disclaimer p {
        color: #92400e;
        margin: 0;
        line-height: 1.6;
    }

    /* Modern Cards */
    .modern-card {
        background: white;
        border-radius: 1.5rem;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 2rem;
    }

    .modern-card:hover {
        box-shadow: var(--shadow-xl);
        transform: translateY(-2px);
    }

    .card-header-modern {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        padding: 1.5rem 2rem;
        border: none;
        position: relative;
    }

    .card-header-modern.secondary {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    }

    .card-header-modern h5 {
        font-weight: 700;
        margin: 0;
        font-size: 1.25rem;
    }

    /* Modern Table */
    .modern-table {
        margin: 0;
        font-size: 0.95rem;
    }

    .modern-table thead th {
        background: var(--light-blue);
        color: var(--dark-blue);
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1.25rem 1.5rem;
        border: none;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .modern-table tbody tr {
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .modern-table tbody tr:hover {
        background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
        border-left-color: var(--primary-blue);
        transform: translateX(3px);
        box-shadow: var(--shadow-sm);
    }

    .modern-table tbody tr.today {
        border-left-color: var(--primary-gold);
        background: linear-gradient(90deg, #fffbeb 0%, #ffffff 100%);
    }

    .modern-table tbody tr.completed {
        border-left-color: #10b981;
        opacity: 0.85;
    }

    .modern-table tbody td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-top: none;
    }

    /* Content Styling */
    .case-number {
        font-weight: 800;
        color: var(--dark-blue);
        font-size: 1rem;
    }

    .defendant-name {
        font-weight: 600;
        color: #111827 !important; /* Very dark gray */
        font-size: 0.95rem;
        font-weight: 700 !important;
    }

    .hearing-date {
        font-weight: 700;
        color: var(--primary-blue);
        font-size: 0.95rem;
    }

    .hearing-time {
        font-size: 0.8rem;
        color: var(--text-gray);
        font-weight: 500;
    }

    .courtroom-info, .judge-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #111827 !important; /* Very dark gray */
        font-size: 0.9rem;
        font-weight: 700 !important;
    }

    .status-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .today-badge {
        background: var(--primary-gold);
        color: var(--dark-blue);
        font-weight: 800;
        padding: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.7rem;
        margin-left: 0.5rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-gray);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        opacity: 0.5;
        color: var(--primary-blue);
    }

    .empty-state h6 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #374151;
        font-size: 1.25rem;
    }

    /* Dark Mode Toggle */
    .dark-mode-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 0.75rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .dark-mode-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
        color: white;
        transform: translateY(-1px);
    }

    /* Footer */
    .modern-footer {
        background: var(--dark-blue);
        color: white;
        padding: 3rem 0 2rem;
        margin-top: 4rem;
        position: relative;
    }

    .footer-section h6 {
        color: var(--primary-gold);
        font-weight: 700;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .footer-section p {
        opacity: 0.9;
        line-height: 1.7;
        font-weight: 400;
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
        font-weight: 400;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .court-title {
            font-size: 2rem;
        }
        
        .court-subtitle {
            font-size: 1rem;
        }
        
        .search-box {
            margin-top: -2rem;
            padding: 1.5rem;
        }
        
        .stats-number {
            font-size: 2rem;
        }
        
        .modern-table thead th,
        .modern-table tbody td {
            padding: 1rem;
            font-size: 0.85rem;
        }
    }

    /* Dark Mode Styles */
    [data-theme="dark"] {
        --primary-blue: #60a5fa;
        --primary-gold: #fbbf24;
        --dark-blue: #3b82f6;
        --light-blue: #1e3a8a;
        --text-gray: #9ca3af;
        --border-color: #374151;
    }

    [data-theme="dark"] body {
        background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
        color: #f9fafb;
    }

    [data-theme="dark"] .stats-container {
        background: rgba(31, 41, 55, 0.95);
        border-color: #374151;
        color: #f9fafb;
    }

    [data-theme="dark"] .search-box {
        background: #1f2937;
        border-color: #374151;
        color: #f9fafb;
    }

    [data-theme="dark"] .search-title {
        color: #f9fafb;
    }
    [data-theme="dark"] .form-control, 
    [data-theme="dark"] .form-select {
        background-color: #111827 !important;
        border-color: #374151 !important;
        color: #f9fafb !important;
    }

    [data-theme="dark"] .form-control::placeholder {
        color: #9ca3af !important;
        opacity: 1;
    }

    [data-theme="dark"] .form-control:focus, 
    [data-theme="dark"] .form-select:focus {
        background-color: #111827 !important;
        border-color: #60a5fa !important;
        color: #f9fafb !important;
        box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
    }

    [data-theme="dark"] .input-group-text {
        background-color: #1e3a8a !important;
        border-color: #374151 !important;
        color: #60a5fa !important;
    }

    [data-theme="dark"] .disclaimer {
        background: linear-gradient(135deg, #451a03 0%, #78350f 100%);
        border-color: #92400e;
    }

    [data-theme="dark"] .disclaimer h6,
    [data-theme="dark"] .disclaimer p {
        color: #fbbf24;
    }

    [data-theme="dark"] .modern-card {
        background: #1f2937;
        border-color: #374151;
    }

    [data-theme="dark"] .modern-table thead th {
        background: #1e3a8a;
        color: #f9fafb;
    }

    [data-theme="dark"] .modern-table tbody tr {
        border-bottom-color: #374151;
    }

    [data-theme="dark"] .modern-table tbody tr:hover {
        background: linear-gradient(90deg, #111827 0%, #1f2937 100%);
        border-left-color: var(--primary-blue);
    }

    [data-theme="dark"] .modern-table tbody tr.today {
        background: linear-gradient(90deg, #451a03 0%, #1f2937 100%);
        border-left-color: var(--primary-gold);
    }

    [data-theme="dark"] .case-number {
        color: #60a5fa;
    }

    [data-theme="dark"] .defendant-name {
        color: #ffffff !important; /* Pure white in dark mode */
    }

    [data-theme="dark"] .hearing-date {
        color: var(--primary-blue);
    }

    [data-theme="dark"] .hearing-time {
        color: var(--text-gray);
    }

    [data-theme="dark"] .courtroom-info, 
    [data-theme="dark"] .judge-info {
        color: #ffffff !important; /* Pure white in dark mode */
    }

    [data-theme="dark"] .empty-state {
        color: var(--text-gray);
    }

    [data-theme="dark"] .empty-state h6 {
        color: #d1d5db;
    }

    [data-theme="dark"] .empty-state i {
        color: var(--primary-blue);
    }

    [data-theme="dark"] .modern-footer {
        background: #111827;
    }

    [data-theme="dark"] .footer-section h6 {
        color: var(--primary-gold);
    }

    [data-theme="dark"] .alert-warning {
        background: #451a03;
        border-color: #92400e;
        color: #fbbf24;
    }

    /* Loading Animation */
    .loading-spinner {
        border: 3px solid rgba(96, 165, 250, 0.3);
        border-top: 3px solid var(--primary-blue);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 2rem auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Print Styles */
    @media print {
        .dark-mode-btn,
        .search-box,
        .disclaimer {
            display: none !important;
        }
        
        .docket-header {
            background: white !important;
            color: black !important;
            -webkit-print-color-adjust: exact;
        }
        
        .modern-card {
            box-shadow: none !important;
            border: 1px solid #000 !important;
        }
    }

    /* Dark Mode Badge Styles */
    [data-theme="dark"] .badge.bg-light {
        background-color: #374151 !important;
        color: #f9fafb !important;
        border: 1px solid #4b5563;
    }

    [data-theme="dark"] .badge.bg-light.text-dark {
        background-color: #374151 !important;
        color: #f9fafb !important;
    }

    /* Alternative approach - more specific targeting */
    [data-theme="dark"] .card-header-modern .badge.bg-light,
    [data-theme="dark"] .card-header-modern .badge.text-dark {
        background-color: #374151 !important;
        color: #f9fafb !important;
        border: 1px solid #4b5563;
    }

    /* Ensure proper contrast and visibility */
    [data-theme="dark"] .badge.bg-light:hover {
        background-color: #4b5563 !important;
        color: #ffffff !important;
    }

    /* Dark Mode Table Cell Content Styles - Fixed */
    [data-theme="dark"] .defendant-name {
        color: #ffffff !important; /* Pure white in dark mode */
    }

    [data-theme="dark"] .courtroom-info {
        color: #ffffff !important; /* Pure white in dark mode */
    }

    [data-theme="dark"] .judge-info {
        color: #ffffff !important; /* Pure white in dark mode */
    }

    /* Target the specific span elements containing the text */
    [data-theme="dark"] .courtroom-info span {
        color: #ffffff !important; /* Pure white for courtroom text */
    }

    [data-theme="dark"] .judge-info span {
        color: #ffffff !important; /* Pure white for judge text */
    }

    /* More specific targeting if needed */
    [data-theme="dark"] .modern-table .courtroom-info span,
    [data-theme="dark"] .modern-table .judge-info span {
        color: #ffffff !important;
    }

    /* Keep icons styled differently */
    [data-theme="dark"] .courtroom-info i,
    [data-theme="dark"] .judge-info i {
        color: #60a5fa !important; /* Light blue for icons */
    }

    /* Ultra-specific targeting if the above doesn't work */
    [data-theme="dark"] .modern-table tbody td .courtroom-info span,
    [data-theme="dark"] .modern-table tbody td .judge-info span,
    [data-theme="dark"] .modern-table tbody td .defendant-name {
        color: #ffffff !important;
    }

    /* Dark Mode Card Background Fix */
    [data-theme="dark"] .modern-card {
        background: #1f2937 !important;
        border-color: #374151 !important;
        color: #f9fafb;
    }

    /* Dark Mode Table Background */
    [data-theme="dark"] .modern-table {
        background: #1f2937 !important;
        color: #f9fafb;
    }

    /* Dark Mode Table Body */
    [data-theme="dark"] .modern-table tbody {
        background: #1f2937 !important;
    }

    /* Dark Mode Table Rows */
    [data-theme="dark"] .modern-table tbody tr {
        background: #1f2937 !important;
        border-bottom-color: #374151 !important;
    }

    /* Dark Mode Table Cells */
    [data-theme="dark"] .modern-table tbody td {
        background: #1f2937 !important;
        color: #f9fafb !important;
        border-color: #374151 !important;
    }

    /* Now the text should be visible */
    [data-theme="dark"] .defendant-name,
    [data-theme="dark"] .courtroom-info,
    [data-theme="dark"] .judge-info {
        color: #ffffff !important;
    }

    [data-theme="dark"] .courtroom-info span,
    [data-theme="dark"] .judge-info span {
        color: #ffffff !important;
    }
</style>
</head>
<body class="bg-light">
    <!-- Header -->
    <div class="docket-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="court-badge">
                        <i class='bx bx-shield-alt-2'></i>
                    </div>
                    <h1 class="court-title">Blackwood County Court</h1>
                    <div class="court-subtitle">Superior Court of California</div>
                    <p class="last-updated">
                        <i class='bx bx-time-five me-2'></i>
                        Last Updated: <?php echo date('l, F j, Y \a\t g:i A'); ?>
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-container">
                        <div class="stats-number"><?php echo count($upcoming_hearings); ?></div>
                        <div class="stats-label">Upcoming Hearings</div>
                        <button class="dark-mode-btn mt-3" id="darkModeToggle">
                            <i class='bx bx-moon me-2'></i>Dark Mode
                        </button>
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
        <div class="modern-card">
            <div class="card-header-modern">
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
                        <table class="table modern-table mb-0" id="upcomingTable">
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
                                    // Method 2 from our test - String comparison (most reliable)
                                    $hearing_date_only = substr($hearing['hearing_date'], 0, 10);
                                    $today_date_only = date('Y-m-d');
                                    $is_today = ($hearing_date_only === $today_date_only);
                                    
                                    // Create DateTime for display formatting
                                    $hearing_date = new DateTime($hearing['hearing_date']);
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
        <div class="modern-card">
            <div class="card-header-modern secondary">
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
    // Sync JavaScript timezone with PHP server timezone
    const serverTimezone = 'America/Los_Angeles'; // Match your PHP timezone
    const serverDate = '<?php echo date('Y-m-d'); ?>';
    const serverDateTime = '<?php echo date('c'); ?>'; // ISO format
    
    // Function to get server-synced date
    function getServerSyncedDate() {
        // Use the server date directly instead of client date
        return new Date(serverDateTime);
    }

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
                    const tableCard = table.closest('.modern-card');
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
                const tableCard = table.closest('.modern-card');
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
        document.querySelectorAll('.modern-table tbody tr').forEach(row => {
            row.addEventListener('click', function() {
                const caseNumber = this.querySelector('.case-number').textContent.trim();
                console.log(`Case ${caseNumber} row clicked`);
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

    // Print functionality - Use server date
    function printDocket() {
        const printWindow = window.open('', '_blank');
        const serverDateFormatted = '<?php echo date('m/d/Y'); ?>';
        const serverDateTimeFormatted = '<?php echo date('F j, Y g:i A T'); ?>';
        
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Blackwood County Court Docket - ${serverDateFormatted}</title>
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
                    <div class="court-title">Blackwood County Superior Court</div>
                    <div class="subtitle">Public Court Calendar</div>
                    <div class="subtitle">Generated: ${serverDateTimeFormatted}</div>
                </div>
                ${document.querySelector('#upcomingTable') ? document.querySelector('#upcomingTable').parentElement.outerHTML : ''}
                <div class="footer">
                    <p>This document was generated from the Blackwood County Superior Court public docket system.</p>
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
        document.querySelectorAll('.modern-table').forEach(table => {
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

    // Dark Mode Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        
        if (darkModeToggle) {
            // Load saved theme
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateDarkModeIcon();
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateDarkModeIcon();
            });
            
            function updateDarkModeIcon() {
                const theme = document.documentElement.getAttribute('data-theme') || 'light';
                const icon = darkModeToggle.querySelector('i');
                const text = darkModeToggle.querySelector('span');
                
                if (theme === 'dark') {
                    icon.className = 'bx bx-sun';
                    if (text) text.textContent = 'Light Mode';
                    darkModeToggle.style.background = 'rgba(255,255,255,0.2)';
                } else {
                    icon.className = 'bx bx-moon';
                    if (text) text.textContent = 'Dark Mode';
                    darkModeToggle.style.background = 'rgba(255,255,255,0.1)';
                }
            }
        }
    });
</script>
</body>
</html>