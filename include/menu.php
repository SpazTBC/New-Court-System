<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Shawns Court Case System">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand">
<!--            <div class='badge bg-danger blink'>LIVE DEMO</div> -->
        </span>
        
        <?php
        // Security headers
        header_remove('x-powered-by');
        error_reporting(0);

        // Include database connection if not already included
        if (!isset($conn)) {
            include_once("database.php");
        }

        // IP Ban Check
        try {
            $stmt = $conn->prepare("SELECT ip, banned FROM users WHERE banned = 1");
            $stmt->execute();
            $bannedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ip = $_SERVER['REMOTE_ADDR'];
            foreach($bannedUsers as $user) {
                if($user['ip'] === $ip) {
                    die("<div class='alert alert-danger text-center'><img src='../images/warning.png'/><h2>BANNED</h2><p>Please see a staff member if you believe this is a mistake.</p></div>");
                }
            }
        } catch (PDOException $e) {
            // Silently log error without exposing details to user
            error_log("Database error in menu.php: " . $e->getMessage());
        }
        ?>

        <!-- Navigation Toggle Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Main Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php
                // Authenticated User Menu
                if(isset($_SESSION['username'])) {
                    // Common menu items for all authenticated users
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "HOME") ? 'active' : ''; ?>" href="/login/home.php">
                            <i class="bx bx-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "CASES") ? 'active' : ''; ?>" href="/cases/index.php">
                            <i class="bx bx-folder"></i> Cases
                        </a>
                    </li>
                    
                    <?php 
                    // Attorney-specific menu items
                    if(isset($isAttorney) && $isAttorney === true): 
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "CLIENTS") ? 'active' : ''; ?>" href="/client-intake/view_intakes.php">
                            <i class="bx bx-user"></i> Clients
                        </a>
                    </li>
                    <?php 
                    endif; 
                    
                    // Admin-specific menu items
                    if(isset($user) && $user['staff'] == "1"): 
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($menu) && $menu == "ADMIN") ? 'active' : ''; ?>" href="/ban/">
                            <i class="bx bx-shield"></i> Admin Panel
                        </a>
                    </li>
                    <?php 
                    endif;
                    
                    // Additional common menu items
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login/logout.php">
                            <i class="bx bx-log-out"></i> Logout
                        </a>
                    </li>
                    <?php
                } 
                // Guest User Menu
                else {
                    switch(isset($menu) ? $menu : '') {
                        case "INDEX":
                            ?>
                            <li class="nav-item">
                                <a class="nav-link active" href="/index.php">
                                    <i class="bx bx-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/register">
                                    <i class="bx bx-user-plus"></i> Register
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/login">
                                    <i class="bx bx-log-in"></i> Login
                                </a>
                            </li>
                            <?php
                            break;
                            
                        case "REGISTER":
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/index.php">
                                    <i class="bx bx-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="/register">
                                    <i class="bx bx-user-plus"></i> Register
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/login">
                                    <i class="bx bx-log-in"></i> Login
                                </a>
                            </li>
                            <?php
                            break;
                            
                        case "LOGIN":
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/index.php">
                                    <i class="bx bx-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/register">
                                    <i class="bx bx-user-plus"></i> Register
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="/login">
                                    <i class="bx bx-log-in"></i> Login
                                </a>
                            </li>
                            <?php
                            break;
                            
                        default:
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/index.php">
                                    <i class="bx bx-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/register">
                                    <i class="bx bx-user-plus"></i> Register
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/login">
                                    <i class="bx bx-log-in"></i> Login
                                </a>
                            </li>
                            <?php
                    }
                }
                ?>
            </ul>        </div>
    </div>
</nav>

<!-- Make sure this is at the bottom of your body tag, just before the closing </body> -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Add click event to dropdown toggles
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('click', function(event) {
            event.preventDefault();
            var dropdownInstance = bootstrap.Dropdown.getInstance(dropdown);
            if (dropdownInstance) {
                dropdownInstance.toggle();
            }
        });
    });
});
</script>