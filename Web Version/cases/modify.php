<?php
session_start();
$menu = "CASES";
include("../include/database.php");

// Get case ID from URL
$caseId = isset($_GET['id']) ? $_GET['id'] : 0;

// Get all users for sharing dropdown
try {
    $users_stmt = $conn->prepare("SELECT username, charactername, job FROM users WHERE username != ? ORDER BY charactername, username");
    $users_stmt->execute([$_SESSION['username']]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Case</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <link href="../../css/dark-mode.css" rel="stylesheet">
    <script src="../../js/dark-mode.js"></script>
    <style>
        .user-option {
            padding: 8px 12px;
        }
        .user-icon {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        .sharing-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid #dee2e6;
        }
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .form-floating > label {
            font-weight: 500;
        }
        .current-user-badge {
            background-color: #e3f2fd;
            color: #1976d2;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
                <span class="ms-2">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <?php
        $stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch();
        
        if($case):
        ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-edit'></i> Modify Case #<?php echo htmlspecialchars($case['caseid']); ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="modified.php" class="row g-3">
                            <input type="hidden" value="<?php echo $case['id']; ?>" name="id">
                            
                            <!-- Basic Case Information -->
                            <div class="col-12 mb-3">
                                <h5 class="section-title"><i class='bx bx-info-circle'></i> Case Information</h5>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['caseid']); ?>" name="caseid" readonly>
                                    <label>Case Number</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['assigneduser']); ?>" name="assigneduser">
                                    <label>Assigned User</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['assigned']); ?>" name="assigned">
                                    <label>Date Assigned</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['type']); ?>" name="type">
                                    <label>Case Type</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" style="height: 100px" name="details"><?php echo htmlspecialchars($case['details']); ?></textarea>
                                    <label>Case Details</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($case['supervisor']); ?>" name="supervisor">
                                    <label>Supervisor</label>
                                </div>
                            </div>

                            <!-- Case Sharing Section -->
                            <div class="col-12">
                                <div class="sharing-section">
                                    <h5 class="section-title"><i class='bx bx-users'></i> Case Sharing & Collaboration</h5>
                                    <p class="text-muted mb-3">Share this case with other users including judges, attorneys, and staff members.</p>
                                    
                                    <div class="row g-3">
                                        <!-- Shared User 1 -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="shared01" name="shared01">
                                                    <option value="">Select User 1 (Optional)</option>
                                                    <?php foreach($users as $userOption): ?>
                                                        <option value="<?php echo htmlspecialchars($userOption['username']); ?>" 
                                                                <?php echo ($case['shared01'] == $userOption['username']) ? 'selected' : ''; ?>>
                                                            <?php 
                                                            $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                            echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="shared01">
                                                    <i class='bx bx-user-plus'></i> Share with User 1
                                                    <?php if (!empty($case['shared01'])): ?>
                                                        <span class="current-user-badge">Currently Assigned</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Shared User 2 -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="shared02" name="shared02">
                                                    <option value="">Select User 2 (Optional)</option>
                                                    <?php foreach($users as $userOption): ?>
                                                        <option value="<?php echo htmlspecialchars($userOption['username']); ?>"
                                                                <?php echo ($case['shared02'] == $userOption['username']) ? 'selected' : ''; ?>>
                                                            <?php 
                                                            $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                            echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="shared02">
                                                    <i class='bx bx-user-plus'></i> Share with User 2
                                                    <?php if (!empty($case['shared02'])): ?>
                                                        <span class="current-user-badge">Currently Assigned</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Shared User 3 -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="shared03" name="shared03">
                                                    <option value="">Select User 3 (Optional)</option>
                                                    <?php foreach($users as $userOption): ?>
                                                        <option value="<?php echo htmlspecialchars($userOption['username']); ?>"
                                                                <?php echo ($case['shared03'] == $userOption['username']) ? 'selected' : ''; ?>>
                                                            <?php 
                                                            $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                            echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="shared03">
                                                    <i class='bx bx-user-plus'></i> Share with User 3
                                                    <?php if (!empty($case['shared03'])): ?>
                                                        <span class="current-user-badge">Currently Assigned</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Shared User 4 -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="shared04" name="shared04">
                                                    <option value="">Select User 4 (Optional)</option>
                                                    <?php foreach($users as $userOption): ?>
                                                        <option value="<?php echo htmlspecialchars($userOption['username']); ?>"
                                                                <?php echo ($case['shared04'] == $userOption['username']) ? 'selected' : ''; ?>>
                                                            <?php 
                                                            $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                            echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label for="shared04">
                                                    <i class='bx bx-user-plus'></i> Share with User 4
                                                    <?php if (!empty($case['shared04'])): ?>
                                                        <span class="current-user-badge">Currently Assigned</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Current Sharing Status -->
                                    <?php 
                                    $currentShares = array_filter([
                                        $case['shared01'], 
                                        $case['shared02'], 
                                        $case['shared03'], 
                                        $case['shared04']
                                    ]);
                                    
                                    if (!empty($currentShares)): 
                                    ?>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <h6 class="text-muted mb-2"><i class='bx bx-info-circle'></i> Currently Shared With:</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php 
                                            foreach($currentShares as $sharedUser): 
                                                // Get user details
                                                $userStmt = $conn->prepare("SELECT charactername, job FROM users WHERE username = ?");
                                                $userStmt->execute([$sharedUser]);
                                                $userData = $userStmt->fetch();
                                                $displayName = $userData ? ($userData['charactername'] ?: $sharedUser) : $sharedUser;
                                                $userJob = $userData ? $userData['job'] : 'Unknown';
                                            ?>
                                            <span class="badge bg-primary">
                                                <i class='bx bx-user'></i> 
                                                <?php echo htmlspecialchars($displayName . ' (' . $userJob . ')'); ?>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Hearing Information (if exists) -->
                            <?php if (!empty($case['hearing_date'])): ?>
                            <div class="col-12">
                                <div class="sharing-section">
                                    <h5 class="section-title"><i class='bx bx-calendar-event'></i> Hearing Information</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="datetime-local" class="form-control" 
                                                       value="<?php echo date('Y-m-d\TH:i', strtotime($case['hearing_date'])); ?>" 
                                                       name="hearing_date">
                                                <label><i class='bx bx-calendar'></i> Hearing Date & Time</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" 
                                                       value="<?php echo htmlspecialchars($case['courtroom'] ?? ''); ?>" 
                                                       name="courtroom">
                                                <label><i class='bx bx-building'></i> Courtroom</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" name="hearing_status">
                                                    <option value="scheduled" <?php echo ($case['hearing_status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                                    <option value="completed" <?php echo ($case['hearing_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo ($case['hearing_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                    <option value="postponed" <?php echo ($case['hearing_status'] == 'postponed') ? 'selected' : ''; ?>>Postponed</option>
                                                </select>
                                                <label><i class='bx bx-flag'></i> Hearing Status</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <textarea class="form-control" style="height: 80px" name="hearing_notes"><?php echo htmlspecialchars($case['hearing_notes'] ?? ''); ?></textarea>
                                                <label><i class='bx bx-note'></i> Hearing Notes</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="col-12 mt-4">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-secondary btn-lg">
                                        <i class='bx bx-arrow-back'></i> Cancel
                                    </a>
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                        <i class='bx bx-save'></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="alert alert-danger text-center">
                    <i class='bx bx-error-circle' style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Case Not Found</h4>
                    <p>The requested case could not be found or you don't have permission to access it.</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class='bx bx-arrow-back'></i> Back to Cases
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict'
            var forms = document.querySelectorAll('form')
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Custom validation logic
                    var isValid = true;
                    
                    // Check for duplicate shared users
                    var sharedUsers = [
                        document.getElementById('shared01').value,
                        document.getElementById('shared02').value,
                        document.getElementById('shared03').value,
                        document.getElementById('shared04').value
                    ].filter(value => value !== '');
                    
                    var uniqueUsers = [...new Set(sharedUsers)];
                    
                    if (sharedUsers.length !== uniqueUsers.length) {
                        alert('Error: You cannot assign the same user to multiple sharing slots.');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                }, false)
            })
        })();

        // Dynamic user selection feedback
        document.addEventListener('DOMContentLoaded', function() {
            const sharedSelects = ['shared01', 'shared02', 'shared03', 'shared04'];
            
            sharedSelects.forEach(function(selectId) {
                const select = document.getElementById(selectId);
                if (select) {
                    select.addEventListener('change', function() {
                        updateUserSelectionFeedback();
                    });
                }
            });
            
            function updateUserSelectionFeedback() {
                const selectedUsers = [];
                
                sharedSelects.forEach(function(selectId) {
                    const select = document.getElementById(selectId);
                    if (select && select.value) {
                        selectedUsers.push(select.value);
                    }
                });
                
                // Disable already selected users in other dropdowns
                sharedSelects.forEach(function(selectId) {
                    const select = document.getElementById(selectId);
                    if (select) {
                        const currentValue = select.value;
                        const options = select.querySelectorAll('option');
                        
                        options.forEach(function(option) {
                            if (option.value && option.value !== currentValue) {
                                if (selectedUsers.includes(option.value)) {
                                    option.disabled = true;
                                    option.style.color = '#6c757d';
                                } else {
                                    option.disabled = false;
                                    option.style.color = '';
                                }
                            }
                        });
                    }
                });
            }
            
            // Initial call to set up the feedback
            updateUserSelectionFeedback();
        });

        // Auto-save functionality (optional)
        let autoSaveTimer;
        const formInputs = document.querySelectorAll('input, textarea, select');
        
        formInputs.forEach(input => {
            input.addEventListener('change', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    // Show auto-save indicator
                    showAutoSaveIndicator();
                }, 2000);
            });
        });
        
        function showAutoSaveIndicator() {
            // Create or update auto-save indicator
            let indicator = document.getElementById('autosave-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'autosave-indicator';
                indicator.className = 'alert alert-info position-fixed';
                indicator.style.cssText = 'top: 20px; right: 20px; z-index: 9999; padding: 0.5rem 1rem;';
                document.body.appendChild(indicator);
            }
            
            indicator.innerHTML = '<i class="bx bx-save me-1"></i> Changes detected - Remember to save!';
            indicator.style.display = 'block';
            
            // Hide after 3 seconds
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('button[name="submit"]').click();
            }
            
            // Escape to cancel
            if (e.key === 'Escape') {
                if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                    window.location.href = 'view.php?id=<?php echo $case['id']; ?>';
                }
            }
        });

        // Confirmation before leaving with unsaved changes
        let formChanged = false;
        
        formInputs.forEach(input => {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        // Reset form changed flag on successful submit
        document.querySelector('form').addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>

Copy

