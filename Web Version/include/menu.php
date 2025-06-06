<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Shawns Court Case System">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">
            <!-- <div class='badge bg-danger blink'>LIVE DEMO</div> -->
        </span>
        
        <?php
        // Security Configuration - Only if headers haven't been sent
        if (!headers_sent()) {
            header_remove('x-powered-by');
        }
        error_reporting(0);

        // Database Connection
        if (!isset($conn)) {
            include_once("database.php");
        }

        // IP Ban Security Check
        try {
            $stmt = $conn->prepare("SELECT ip, banned FROM users WHERE banned = 1");
            $stmt->execute();
            $bannedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ip = $_SERVER['REMOTE_ADDR'];
            foreach($bannedUsers as $bannedUser) {
                if($bannedUser['ip'] === $ip) {
                    die("<div class='alert alert-danger text-center'>
                            <img src='../images/warning.png' alt='Warning'/>
                            <h2>BANNED</h2>
                            <p>Please see a staff member if you believe this is a mistake.</p>
                         </div>");
                }
            }
        } catch (PDOException $e) {
            error_log("Database error in menu.php: " . $e->getMessage());
        }

        // Get Current User Job for Menu Logic
        if (isset($_SESSION['username']) && !isset($current_user_job)) {
            try {
                $menu_user_stmt = $conn->prepare("SELECT job, staff, job_approved FROM users WHERE username = ?");
                $menu_user_stmt->execute([$_SESSION['username']]);
                $menu_user_data = $menu_user_stmt->fetch(PDO::FETCH_ASSOC);
                $current_user_job = strtolower($menu_user_data['job'] ?? '');
                $is_staff = $menu_user_data['staff'] ?? 0;
                $job_approved = $menu_user_data['job_approved'] ?? 1;
            } catch (PDOException $e) {
                error_log("Error fetching user data in menu.php: " . $e->getMessage());
                $current_user_job = '';
                $is_staff = 0;
                $job_approved = 0;
            }
        }
        ?>

        <!-- Mobile Navigation Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Main Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['username'])): ?>
                    <!-- AUTHENTICATED USER MENU -->
                    
                    <!-- Dashboard - Available to All Users -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "HOME") ? 'active' : ''; ?>" 
                           href="/login/home.php">
                            <i class="bx bx-home"></i> Dashboard
                        </a>
                    </li>

                    <?php
                    // ROLE-BASED MENU ITEMS
                    
                    // For Legal Professionals (Attorney, AG, Judge, Police) - Show Cases and Hearings
                    if (in_array($current_user_job, ['attorney', 'ag', 'judge', 'police']) || $is_staff == 1):
                    ?>
                    <!-- Cases - For Legal Professionals -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "CASES") ? 'active' : ''; ?>" 
                           href="/cases/index.php">
                            <i class="bx bx-folder"></i> Cases
                        </a>
                    </li>

                    <!-- Hearings - For Legal Professionals -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "HEARINGS") ? 'active' : ''; ?>" 
                           href="/cases/hearings/index.php">
                            <i class="bx bx-calendar-event"></i> Hearings
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // For Assistants - Show Appointments (Primary Function)
                    if ($current_user_job === 'assistant'):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "APPOINTMENTS") ? 'active' : ''; ?>" 
                           href="/appointments/">
                            <i class="bx bx-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // For Attorneys - Show My Appointments and Client Intake
                    if (in_array($current_user_job, ['attorney', 'ag']) && $job_approved == 1):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "APPOINTMENTS") ? 'active' : ''; ?>" 
                           href="/appointments/">
                            <i class="bx bx-calendar-check"></i> My Appointments
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "CLIENTS") ? 'active' : ''; ?>" 
                           href="/client-intake/view_intakes.php">
                            <i class="bx bx-user"></i> Client Intake
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // Public Docket - Available to All Authenticated Users
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/docket.php" target="_blank">
                            <i class="bx bx-calendar-alt"></i> Public Docket
                        </a>
                    </li>

                    <?php
                    // Admin Panel - For Staff Only
                    if ($is_staff == 1):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "ADMIN") ? 'active' : ''; ?>" 
                           href="/ban/">
                            <i class="bx bx-shield"></i> Admin Panel
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // AG Approvals - For Approved AG Only
                    if ($current_user_job === 'ag' && $job_approved == 1):
                        try {
                            $pendingStmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM cases WHERE status = 'pending'");
                            $pendingStmt->execute();
                            $pendingCount = $pendingStmt->fetch()['pending_count'];
                            
                            if ($pendingCount > 0):
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/cases/approve/">
                            <i class="bx bx-bell"></i> Approvals
                            <span class="badge bg-danger"><?php echo $pendingCount; ?></span>
                        </a>
                    </li>
                    <?php 
                            endif;
                        } catch (PDOException $e) {
                            error_log("Error fetching pending cases count: " . $e->getMessage());
                        }
                    endif; 
                    ?>

                    <!-- Dark Mode Toggle -->
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" id="navbarDarkModeToggle" 
                                style="border: none; background: none;">
                            <i class="bx bx-moon"></i> 
                            <span class="d-none d-md-inline">Dark Mode</span>
                        </button>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <a class="nav-link" href="/login/logout.php">
                            <i class="bx bx-log-out"></i> Logout
                        </a>
                    </li>

                <?php else: ?>
                    <!-- GUEST USER MENU -->
                    <?php
                    $current_page = isset($menu) ? $menu : '';
                    $menu_items = [
                        ['page' => 'INDEX', 'url' => '/index.php', 'icon' => 'bx-home', 'text' => 'Home'],
                        ['page' => 'REGISTER', 'url' => '/register', 'icon' => 'bx-user-plus', 'text' => 'Register'],
                        ['page' => 'LOGIN', 'url' => '/login', 'icon' => 'bx-log-in', 'text' => 'Login']
                    ];

                    foreach ($menu_items as $item):
                        $active_class = ($current_page === $item['page']) ? 'active' : '';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $active_class; ?>" href="<?php echo $item['url']; ?>">
                            <i class="bx <?php echo $item['icon']; ?>"></i> <?php echo $item['text']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>

                    <!-- Public Docket for Guests -->
                    <li class="nav-item">
                        <a class="nav-link" href="/public/docket.php">
                            <i class="bx bx-calendar-alt"></i> Court Docket
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- JavaScript Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap Dropdowns
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    const dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Handle Dropdown Click Events
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('click', function(event) {
            event.preventDefault();
            const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown);
            if (dropdownInstance) {
                dropdownInstance.toggle();
            }
        });
    });

    // Dark Mode Toggle Functionality
    const navToggle = document.getElementById('navbarDarkModeToggle');
    if (navToggle) {
        // Handle navbar dark mode toggle click
        navToggle.addEventListener('click', function() {
            const mainToggle = document.getElementById('darkModeToggle');
            if (mainToggle) {
                mainToggle.click();
            } else {
                // Fallback dark mode toggle if main toggle doesn't exist
                toggleDarkMode();
            }
        });
        
        // Update navbar toggle icon when theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    updateDarkModeIcon();
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });

        // Initialize icon on page load
        updateDarkModeIcon();
    }

    // Dark Mode Helper Functions
    function updateDarkModeIcon() {
        const theme = document.documentElement.getAttribute('data-theme') || 
                     localStorage.getItem('theme') || 
                     'light';
        const icon = navToggle.querySelector('i');
        const text = navToggle.querySelector('span');
        
        if (theme === 'dark') {
            icon.className = 'bx bx-sun';
            if (text) text.textContent = 'Light Mode';
        } else {
            icon.className = 'bx bx-moon';
            if (text) text.textContent = 'Dark Mode';
        }
    }

    function toggleDarkMode() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateDarkModeIcon();
    }

    // Apply saved theme on page load
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
});
</script>

<style>
/* Dark Mode Styles */
[data-theme="dark"] {
    --bs-body-bg: #1a1a1a;
    --bs-body-color: #ffffff;
    --bs-card-bg: #2d2d2d;
    --bs-border-color: #404040;
}

[data-theme="dark"] .bg-light {
    background-color: #1a1a1a !important;
}

[data-theme="dark"] .card {
    background-color: var(--bs-card-bg);
    border-color: var(--bs-border-color);
    color: var(--bs-body-color);
}

[data-theme="dark"] .table {
    --bs-table-bg: #2d2d2d;
    --bs-table-color: #ffffff;
}

[data-theme="dark"] .form-control,
[data-theme="dark"] .form-select {
    background-color: #2d2d2d;
    border-color: #404040;
    color: #ffffff;
}

[data-theme="dark"] .form-control:focus,
[data-theme="dark"] .form-select:focus {
    background-color: #2d2d2d;
    border-color: #0d6efd;
    color: #ffffff;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Navbar Dark Mode Toggle Button Styling */
#navbarDarkModeToggle {
    transition: all 0.3s ease;
}

#navbarDarkModeToggle:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-radius: 0.375rem;
}

/* Mobile Responsive Adjustments */
@media (max-width: 768px) {
    .navbar-nav .nav-link {
        padding: 0.75rem 1rem;
    }
    
    #navbarDarkModeToggle {
        text-align: left;
        justify-content: flex-start;
    }
}
</style>