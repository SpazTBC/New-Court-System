<?php
session_start();
$menu = "ADMIN";
include("../include/database.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Staff check using PDO
$stmt = $conn->prepare("SELECT staff FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

if (!$user['staff']) {
    echo '<script>
        alert("Sorry ' . $_SESSION['username'] . ' You must be staff to enter this page");
        window.location.href = "../login/";
    </script>';
    exit();
}

// Process ban/unban action
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    
    // Check if user exists
    $check_stmt = $conn->prepare("SELECT userid FROM users WHERE username = ?");
    $check_stmt->execute([$username]);
    $user_exists = $check_stmt->fetch();
    
    if (!$user_exists) {
        $error_message = "User not found!";
    } else {
        // Process ban/unban
        if (isset($_POST['ban'])) {
            $ban_value = $_POST['ban'];
            $update_stmt = $conn->prepare("UPDATE users SET banned = ? WHERE username = ?");
            $update_stmt->execute([$ban_value, $username]);
            $success_message = "User ban status updated successfully!";
        }
        
        // Process role change
        if (isset($_POST['role']) && !empty($_POST['role'])) {
            $new_role = $_POST['role'];
            $update_stmt = $conn->prepare("UPDATE users SET job = ? WHERE username = ?");
            $update_stmt->execute([$new_role, $username]);
            $success_message = isset($success_message) ? $success_message . " User role updated successfully!" : "User role updated successfully!";
        }
        
        // Process account deletion
        if (isset($_POST['delete']) && $_POST['delete'] == 1) {
            try {
                // Get the user ID before deletion
                $id_stmt = $conn->prepare("SELECT userid FROM users WHERE username = ?");
                $id_stmt->execute([$username]);
                $user_id = $id_stmt->fetch(PDO::FETCH_COLUMN);
                
                if ($user_id) {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Delete the user
                    $delete_stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
                    $delete_stmt->execute([$username]);
                    
                    // Get the maximum ID after deletion
                    $max_id_stmt = $conn->query("SELECT MAX(userid) FROM users");
                    $max_id = $max_id_stmt->fetchColumn();
                    
                    // Update subsequent IDs
                    for ($i = $user_id + 1; $i <= $max_id + 1; $i++) {
                        $conn->exec("UPDATE users SET userid = " . ($i - 1) . " WHERE userid = " . $i);
                    }
                    
                    // Reset auto-increment to the new max ID + 1
                    $new_max_id_stmt = $conn->query("SELECT MAX(userid) FROM users");
                    $new_max_id = $new_max_id_stmt->fetchColumn();
                    $conn->exec("ALTER TABLE users AUTO_INCREMENT = " . ($new_max_id + 1));
                    
                    $conn->commit();
                    $success_message = "User deleted successfully and IDs reorganized!";
                }
            } catch (Exception $e) {
                // Only roll back if a transaction is active
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get available roles for dropdown
try {
    $roles_stmt = $conn->query("SELECT DISTINCT job FROM users WHERE job IS NOT NULL AND job != '' ORDER BY job");
    $roles = $roles_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error_message = "Error fetching roles: " . $e->getMessage();
    $roles = [];
}

// Get user statistics
try {
    $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $banned_users = $conn->query("SELECT COUNT(*) FROM users WHERE banned = 1")->fetchColumn();
    $staff_users = $conn->query("SELECT COUNT(*) FROM users WHERE staff = 1")->fetchColumn();
} catch (PDOException $e) {
    $error_message = "Error fetching statistics: " . $e->getMessage();
    $total_users = $banned_users = $staff_users = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .stat-card {
            border-radius: 15px;
            overflow: hidden;
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }
        .badge {
            font-weight: 500;
            padding: 0.5em 0.8em;
        }
        .action-buttons .btn {
            margin-right: 5px;
            border-radius: 50px;
            padding: 0.25rem 0.75rem;
        }
        .action-buttons .btn i {
            margin-right: 5px;
        }
        .form-floating > label {
            padding-left: 1rem;
        }
        .form-select, .form-control {
            border-radius: 10px;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            padding: 0.75rem 1.25rem;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;">
                <span class="ms-2">Admin Control Panel</span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Alert Messages -->
        <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class='bx bx-check-circle me-2'></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class='bx bx-error-circle me-2'></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class='bx bx-user-circle'></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $total_users; ?></h3>
                            <p class="mb-0">Total Users</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-danger text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class='bx bx-block'></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $banned_users; ?></h3>
                            <p class="mb-0">Banned Users</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon me-3">
                            <i class='bx bx-shield'></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $staff_users; ?></h3>
                            <p class="mb-0">Staff Members</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class='bx bx-list-ul me-2'></i>User Management</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class='bx bx-user-plus'></i> Add User
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- User List Table -->
                        <div class="table-responsive">
                            <table id="userTable" class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $conn->query("SELECT userid, username, job, banned, staff FROM users ORDER BY userid");
                                        if ($stmt->rowCount() > 0) {
                                            while($user = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['userid']); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-2 bg-<?php echo $user['staff'] == 1 ? 'primary' : 'secondary'; ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <?php echo htmlspecialchars($user['username']); ?>
                                                            <?php if($user['staff'] == 1): ?>
                                                                <span class="badge bg-info ms-1">Staff</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($user['job'] ?? 'N/A'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($user['banned'] == 1): ?>
                                                        <span class="badge bg-danger">Banned</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm <?php echo $user['banned'] ? 'btn-success' : 'btn-danger'; ?>"
                                                                onclick="toggleBan('<?php echo $user['username']; ?>', <?php echo $user['banned']; ?>)">
                                                            <i class='bx <?php echo $user['banned'] ? 'bx-check-circle' : 'bx-block'; ?>'></i>
                                                            <?php echo $user['banned'] ? 'Unban' : 'Ban'; ?>
                                                        </button>
                                                        <button class="btn btn-sm btn-warning" 
                                                                onclick="changeRole('<?php echo $user['username']; ?>')">
                                                            <i class='bx bx-transfer'></i> Role
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                onclick="confirmDelete('<?php echo $user['username']; ?>')">
                                                            <i class='bx bx-trash'></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile;
                                        } else {
                                            echo '<tr><td colspan="5" class="text-center">No users found</td></tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="5" class="text-center text-danger">Error fetching users: ' . $e->getMessage() . '</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class='bx bx-cog me-2'></i>User Actions</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="actionTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="ban-tab" data-bs-toggle="tab" data-bs-target="#ban-content" type="button" role="tab" aria-controls="ban-content" aria-selected="true">
                                    <i class='bx bx-block me-1'></i>Ban/Unban
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="role-tab" data-bs-toggle="tab" data-bs-target="#role-content" type="button" role="tab" aria-controls="role-content" aria-selected="false">
                                    <i class='bx bx-transfer me-1'></i>Role
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="delete-tab" data-bs-toggle="tab" data-bs-target="#delete-content" type="button" role="tab" aria-controls="delete-content" aria-selected="false">
                                    <i class='bx bx-trash me-1'></i>Delete
                                </button>
                            </li>
                        </ul>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <label for="username"><i class='bx bx-user me-1'></i>Username</label>
                                </div>
                            </div>

                            <div class="tab-content" id="actionTabsContent">
                                <!-- Ban/Unban Tab -->
                                <div class="tab-pane fade show active" id="ban-content" role="tabpanel" aria-labelledby="ban-tab">
                                    <div class="mb-3">
                                        <div class="form-floating">
                                            <select class="form-select" id="ban" name="ban">
                                                <option value="1">Ban User</option>
                                                <option value="0">Unban User</option>
                                            </select>
                                            <label for="ban"><i class='bx bx-block me-1'></i>Ban Action</label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="submit" class="btn btn-primary">
                                            <i class='bx bx-check-circle me-1'></i>Apply Ban Action
                                        </button>
                                    </div>
                                </div>

                                <!-- Role Tab -->
                                <div class="tab-pane fade" id="role-content" role="tabpanel" aria-labelledby="role-tab">
                                    <div class="mb-3">
                                        <div class="form-floating">
                                            <select class="form-select" id="role" name="role">
                                                <option value="">Select Role</option>
                                                <?php foreach($roles as $role): ?>
                                                    <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($role); ?></option>
                                                <?php endforeach; ?>
                                                <option value="custom">Custom Role</option>
                                            </select>
                                            <label for="role"><i class='bx bx-id-card me-1'></i>New Role</label>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="customRoleInput" style="display:none;">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="customRole" name="customRole">
                                            <label for="customRole"><i class='bx bx-edit me-1'></i>Enter Custom Role</label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="submit" class="btn btn-warning">
                                            <i class='bx bx-transfer me-1'></i>Change Role
                                        </button>
                                    </div>
                                </div>

                                <!-- Delete Tab -->
                                <div class="tab-pane fade" id="delete-content" role="tabpanel" aria-labelledby="delete-tab">
                                    <div class="alert alert-danger mb-3">
                                        <i class='bx bx-error-circle me-1'></i> Warning: This action cannot be undone. The user account will be permanently deleted.
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="delete" name="delete" value="1">
                                            <label class="form-check-label" for="delete">
                                                I confirm that I want to permanently delete this user account.
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="submit" class="btn btn-danger">
                                            <i class='bx bx-trash me-1'></i>Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class='bx bx-bolt-circle me-2'></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../login/home.php" class="btn btn-outline-primary">
                                <i class='bx bx-home me-1'></i>Back to Dashboard
                            </a>
                            <a href="register.php" class="btn btn-outline-success">
                                <i class='bx bx-user-plus me-1'></i>Register New User
                            </a>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#bulkBanModal">
                                <i class='bx bx-block me-1'></i>Bulk Ban Management
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel"><i class='bx bx-user-plus me-2'></i>Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">To add a new user, please use the registration page:</p>
                    <div class="d-grid">
                        <a href="../register/index.php" class="btn btn-primary">
                            <i class='bx bx-user-plus me-1'></i>Go to Registration Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Ban Modal -->
    <div class="modal fade" id="bulkBanModal" tabindex="-1" aria-labelledby="bulkBanModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkBanModalLabel"><i class='bx bx-block me-2'></i>Bulk Ban Management</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This feature allows you to ban or unban multiple users at once.</p>
                    <form action="register.php" method="post">
                        <div class="mb-3">
                            <label for="bulkUsernames" class="form-label">Usernames (one per line)</label>
                            <textarea class="form-control" id="bulkUsernames" name="bulkUsernames" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="bulkAction" class="form-label">Action</label>
                            <select class="form-select" id="bulkAction" name="bulkAction" required>
                                <option value="1">Ban All Listed Users</option>
                                <option value="0">Unban All Listed Users</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="bulkSubmit" class="btn btn-danger">
                                <i class='bx bx-check-circle me-1'></i>Apply Bulk Action
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#userTable').DataTable({
                "pageLength": 10,
                "order": [[ 0, "asc" ]],
                "language": {
                    "search": "Search users:",
                    "lengthMenu": "Show _MENU_ users per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ users",
                    "infoEmpty": "No users found",
                    "infoFiltered": "(filtered from _MAX_ total users)"
                }
            });
            
            // Show active tab based on URL hash
            let hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Update URL hash when tab changes
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                window.location.hash = e.target.hash;
            });
        });
        
        // Show/hide custom role input
        document.getElementById('role').addEventListener('change', function() {
            if (this.value === 'custom') {
                document.getElementById('customRoleInput').style.display = 'block';
            } else {
                document.getElementById('customRoleInput').style.display = 'none';
            }
        });
        
        // Function to handle ban toggle from the table
        function toggleBan(username, currentStatus) {
            document.getElementById('username').value = username;
            document.getElementById('ban-tab').click();
            document.getElementById('ban').value = currentStatus ? '0' : '1';
            
            // Scroll to the form
            document.querySelector('.card-header:contains("User Actions")').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        // Function to handle role change from the table
        function changeRole(username) {
            document.getElementById('username').value = username;
            document.getElementById('role-tab').click();
            
            // Scroll to the form
            document.querySelector('.card-header:contains("User Actions")').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        // Function to handle delete confirmation from the table
        function confirmDelete(username) {
            if (confirm('Are you sure you want to delete the user "' + username + '"? This action cannot be undone.')) {
                document.getElementById('username').value = username;
                document.getElementById('delete-tab').click();
                document.getElementById('delete').checked = true;
                document.querySelector('form').submit();
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(event) {
            const activeTab = document.querySelector('.tab-pane.active');
            const username = document.getElementById('username').value.trim();
            
            if (!username) {
                alert('Please enter a username');
                event.preventDefault();
                return;
            }
            
            if (activeTab.id === 'role-content') {
                const roleSelect = document.getElementById('role');
                if (roleSelect.value === '') {
                    alert('Please select a role');
                    event.preventDefault();
                    return;
                }
                
                if (roleSelect.value === 'custom') {
                    const customRole = document.getElementById('customRole').value.trim();
                    if (customRole === '') {
                        alert('Please enter a custom role name');
                        event.preventDefault();
                        return;
                    }
                    // Set the role value to the custom role input
                    roleSelect.value = customRole;
                }
            }
            
            if (activeTab.id === 'delete-content' && !document.getElementById('delete').checked) {
                alert('Please confirm the deletion by checking the confirmation box');
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
