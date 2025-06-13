<?php
session_start();
$menu = "CASE";
include("../../include/database.php");

// Check user permissions
$stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch();

if ($user['job'] === "Civilian") {
    header("Location: ../index.php");
    exit();
}

// Get all users for dropdown
$stmt = $conn->prepare("SELECT username, job, charactername FROM users ORDER BY charactername");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Case</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
    <style>
        .case-type-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: 2px solid transparent;
        }
        .case-type-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .case-type-card.criminal {
            border-color: #dc3545;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .case-type-card.civil {
            border-color: #17a2b8;
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        .case-type-card.family {
            border-color: #28a745;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        }
        .case-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .user-option {
            padding: 8px 12px;
        }
        .user-icon {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        .hearing-section {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0.375rem;
        }
        .datetime-input {
            position: relative;
        }
        .datetime-input input[type="datetime-local"] {
            min-width: 100%;
        }
        .parties-section {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <!-- <img src="../../images/logo.png" alt="Logo" class="img-fluid" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white">
                        <h3 class="mb-0"><i class='bx bx-file-plus'></i> Create New Case</h3>
                    </div>
                    <div class="card-body">
                        <!-- Case Number Generation Buttons -->
                        <div class="row mb-4">
                            <div class="col-12 mb-3">
                                <h5 class="text-muted"><i class='bx bx-category'></i> Select Case Type</h5>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="case-type-card criminal text-white text-center p-4 rounded">
                                    <div class="case-icon">
                                        <i class='bx bx-shield-x'></i>
                                    </div>
                                    <h6 class="fw-bold mb-2">Criminal Case</h6>
                                    <button type="button" name="generate" class="btn btn-light btn-sm fw-bold">
                                        <i class='bx bx-plus'></i> Generate Case Number
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="case-type-card civil text-white text-center p-4 rounded">
                                    <div class="case-icon">
                                        <i class='bx bx-balance'></i>
                                    </div>
                                    <h6 class="fw-bold mb-2">Civil Case</h6>
                                    <button type="button" name="civil" class="btn btn-light btn-sm fw-bold">
                                        <i class='bx bx-plus'></i> Generate Case Number
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="case-type-card family text-white text-center p-4 rounded">
                                    <div class="case-icon">
                                        <i class='bx bx-home-heart'></i>
                                    </div>
                                    <h6 class="fw-bold mb-2">Family Case</h6>
                                    <button type="button" name="family" class="btn btn-light btn-sm fw-bold">
                                        <i class='bx bx-plus'></i> Generate Case Number
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Case Details Form -->
                        <form method="POST" action="register.php" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-12 mb-3">
                                    <h5 class="text-muted"><i class='bx bx-info-circle'></i> Case Information</h5>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="casenum" name="casenum" placeholder="Case ID Number" required>
                                        <label for="casenum"><i class='bx bx-hash'></i> Case ID Number</label>
                                    </div>
                                </div>

                                <!-- Parties Section -->
                                <div class="col-12 mb-3 mt-4">
                                    <div class="parties-section">
                                        <h5 class="text-muted mb-3"><i class='bx bx-group'></i> Case Parties</h5>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="plaintiff" name="plaintiff" placeholder="Enter plaintiff name">
                                                    <label for="plaintiff"><i class='bx bx-user-check'></i> Name of Plaintiff</label>
                                                    <div class="form-text">The person or entity bringing the case (if applicable)</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="defendent" name="defendent" placeholder="Enter defendant name" required>
                                                    <label for="defendent"><i class='bx bx-user-x'></i> Name of Defendant</label>
                                                    <div class="form-text">The person or entity being accused/sued</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Case Details Section -->
                                <div class="col-12 mb-3 mt-4">
                                    <h5 class="text-muted"><i class='bx bx-file-text'></i> Case Details</h5>
                                    <p class="text-muted small">Provide detailed information about the case, charges, evidence, and other relevant details.</p>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="case_details" name="case_details" style="height: 150px" placeholder="Enter detailed case information, charges, evidence, witness information, etc." required></textarea>
                                        <label for="case_details"><i class='bx bx-file-text'></i> Case Details & Information</label>
                                        <div class="form-text">Include charges, evidence, witness information, case background, and any other relevant details.</div>
                                    </div>
                                </div>

                                <!-- Case Type and Status -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="case_type" name="case_type" required>
                                            <option value="">Select Case Type</option>
                                            <option value="Criminal">Criminal</option>
                                            <option value="Civil">Civil</option>
                                            <option value="Family">Family</option>
                                            <option value="Traffic">Traffic</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <label for="case_type"><i class='bx bx-category'></i> Case Type</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="case_status" name="case_status">
                                            <option value="Open">Open</option>
                                            <option value="Under Investigation">Under Investigation</option>
                                            <option value="Pending Review">Pending Review</option>
                                            <option value="Closed">Closed</option>
                                        </select>
                                        <label for="case_status"><i class='bx bx-flag'></i> Case Status</label>
                                    </div>
                                </div>

                                <!-- Hearing Information (Optional) -->
                                <div class="col-12 mb-3 mt-4">
                                    <h5 class="text-muted"><i class='bx bx-calendar-event'></i> Hearing Information (Optional)</h5>
                                    <p class="text-muted small">Schedule a court hearing for this case if needed.</p>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="datetime-local" class="form-control" id="hearing_date" name="hearing_date" placeholder="Hearing Date">
                                        <label for="hearing_date"><i class='bx bx-calendar'></i> Hearing Date & Time</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="courtroom" name="courtroom">
                                            <option value="">Select Courtroom (Optional)</option>
                                            <option value="Courtroom A">Courtroom A</option>
                                            <option value="Courtroom B">Courtroom B</option>
                                            <option value="Courtroom C">Courtroom C</option>
                                            <option value="Family Court">Family Court</option>
                                            <option value="Civil Court">Civil Court</option>
                                        </select>
                                        <label for="courtroom"><i class='bx bx-building'></i> Courtroom</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="hearing_notes" name="hearing_notes" style="height: 100px" placeholder="Additional notes about the hearing, scheduling preferences, special requirements, etc."></textarea>
                                        <label for="hearing_notes"><i class='bx bx-note'></i> Hearing Notes (Optional)</label>
                                        <div class="form-text">Notes specific to the hearing scheduling, courtroom requirements, or special instructions.</div>
                                    </div>
                                </div>

                                <!-- User Sharing Section -->
                                <div class="col-12 mb-3 mt-4">
                                    <h5 class="text-muted"><i class='bx bx-users'></i> Case Sharing & Judge Assignment</h5>
                                    <p class="text-muted small">Share this case with other users. If assigning a judge, select a user with "Judge" role.</p>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="shared1" name="shared1">
                                            <option value="">Select User 1 (Optional)</option>
                                            <?php foreach($users as $userOption): ?>
                                                <option value="<?php echo htmlspecialchars($userOption['username']); ?>">
                                                    <?php 
                                                    $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                    echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="shared1"><i class='bx bx-user-plus'></i> Share with User 1 (Judge if applicable)</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="shared2" name="shared2">
                                            <option value="">Select User 2 (Optional)</option>
                                            <?php foreach($users as $userOption): ?>
                                                <option value="<?php echo htmlspecialchars($userOption['username']); ?>">
                                                    <?php 
                                                    $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                    echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="shared2"><i class='bx bx-user-plus'></i> Share with User 2</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="shared3" name="shared3">
                                            <option value="">Select User 3 (Optional)</option>
                                            <?php foreach($users as $userOption): ?>
                                                <option value="<?php echo htmlspecialchars($userOption['username']); ?>">
                                                    <?php 
                                                    $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                    echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="shared3"><i class='bx bx-user-plus'></i> Share with User 3</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="shared4" name="shared4">
                                            <option value="">Select User 4 (Optional)</option>
                                            <?php foreach($users as $userOption): ?>
                                                <option value="<?php echo htmlspecialchars($userOption['username']); ?>">
                                                    <?php 
                                                    $displayName = !empty($userOption['charactername']) ? $userOption['charactername'] : $userOption['username'];
                                                    echo htmlspecialchars($displayName . ' - ' . $userOption['job']); 
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="shared4"><i class='bx bx-user-plus'></i> Share with User 4</label>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" name="submit" class="btn btn-primary btn-lg w-100">
                                        <i class='bx bx-save'></i> Create Case File
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../include/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to current date/time for hearing
        document.addEventListener('DOMContentLoaded', function() {
            const hearingDateInput = document.getElementById('hearing_date');
            if (hearingDateInput) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                hearingDateInput.min = now.toISOString().slice(0, 16);
            }
        });

        // Form validation
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Case number generation (existing functionality)
        document.querySelectorAll('button[name="generate"], button[name="civil"], button[name="family"]').forEach(button => {
            button.addEventListener('click', function() {
                const caseType = this.name === 'generate' ? 'CR' : (this.name === 'civil' ? 'CV' : 'FM');
                const randomNum = Math.floor(Math.random() * 90000) + 10000;
                const year = new Date().getFullYear();
                const caseNumber = `${caseType}-${year}-${randomNum}`;
                document.getElementById('casenum').value = caseNumber;
            });
        });
    </script>
</body>
</html>