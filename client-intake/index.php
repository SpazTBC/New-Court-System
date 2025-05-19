<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Database connection
require_once "../include/database.php";

// Check if user is an attorney
$stmt = $conn->prepare("SELECT job FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user['job'] !== "Attorney") {
    // Redirect non-attorneys to the dashboard
    header("Location: /login/home.php");
    exit();
}

$menu = "CLIENTS"; // Set the active menu item
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Intake Form - Court System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand">
                <!-- <img src="../images/logo.png" alt="Logo" class="img-fluid" style="max-height: 40px;"> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">New Client Intake</h4>
                        <div>
                            <a href="view_intakes.php" class="btn btn-info me-2"><i class='bx bx-list-ul'></i> View All Intakes</a>
                            <a href="/login/home.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success']) && $_GET['success'] === 'true'): ?>
                            <div class="alert alert-success" role="alert">
                                Client intake has been successfully recorded.
                            </div>
                        <?php endif; ?>

                        <form method="post" action="process_intake.php">
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2">Personal Information</h5>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" name="middleName">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ssn" class="form-label">SSN (Last 4 digits)</label>
                                    <input type="text" class="form-control" id="ssn" name="ssn" maxlength="4" pattern="\d{4}" placeholder="XXXX">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2">Contact Information</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Street Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <select class="form-select" id="state" name="state" required>
                                        <option value="" selected disabled>Select State</option>
                                        <!-- Common states first -->
                                        <option value="CA">California</option>
                                        <option value="TX">Texas</option>
                                        <option value="FL">Florida</option>
                                        <option value="NY">New York</option>
                                        <option value="IL">Illinois</option>
                                        <!-- All states alphabetically -->
                                        <option value="AL">Alabama</option>
                                        <option value="AK">Alaska</option>
                                        <option value="AZ">Arizona</option>
                                        <option value="AR">Arkansas</option>
                                        <option value="CO">Colorado</option>
                                        <option value="CT">Connecticut</option>
                                        <option value="DE">Delaware</option>
                                        <option value="DC">District of Columbia</option>
                                        <option value="GA">Georgia</option>
                                        <option value="HI">Hawaii</option>
                                        <option value="ID">Idaho</option>
                                        <option value="IN">Indiana</option>
                                        <option value="IA">Iowa</option>
                                        <option value="KS">Kansas</option>
                                        <option value="KY">Kentucky</option>
                                        <option value="LA">Louisiana</option>
                                        <option value="ME">Maine</option>
                                        <option value="MD">Maryland</option>
                                        <option value="MA">Massachusetts</option>
                                        <option value="MI">Michigan</option>
                                        <option value="MN">Minnesota</option>
                                        <option value="MS">Mississippi</option>
                                        <option value="MO">Missouri</option>
                                        <option value="MT">Montana</option>
                                        <option value="NE">Nebraska</option>
                                        <option value="NV">Nevada</option>
                                        <option value="NH">New Hampshire</option>
                                        <option value="NJ">New Jersey</option>
                                        <option value="NM">New Mexico</option>
                                        <option value="NC">North Carolina</option>
                                        <option value="ND">North Dakota</option>
                                        <option value="OH">Ohio</option>
                                        <option value="OK">Oklahoma</option>
                                        <option value="OR">Oregon</option>
                                        <option value="PA">Pennsylvania</option>
                                        <option value="RI">Rhode Island</option>
                                        <option value="SC">South Carolina</option>
                                        <option value="SD">South Dakota</option>
                                        <option value="TN">Tennessee</option>
                                        <option value="UT">Utah</option>
                                        <option value="VT">Vermont</option>
                                        <option value="VA">Virginia</option>
                                        <option value="WA">Washington</option>
                                        <option value="WV">West Virginia</option>
                                        <option value="WI">Wisconsin</option>
                                        <option value="WY">Wyoming</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip" class="form-label">Zip Code</label>
                                    <input type="text" class="form-control" id="zip" name="zip" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2">Case Information</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="caseType" class="form-label">Case Type</label>
                                    <select class="form-select" id="caseType" name="caseType" required>
                                        <option value="" selected disabled>Select Case Type</option>
                                        <option value="criminal">Criminal</option>
                                        <option value="civil">Civil</option>
                                        <option value="family">Family</option>
                                        <option value="probate">Probate</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="referralSource" class="form-label">Referral Source</label>
                                    <select class="form-select" id="referralSource" name="referralSource">
                                        <option value="" selected disabled>How did you hear about us?</option>
                                        <option value="internet">Internet Search</option>
                                        <option value="friend">Friend/Family</option>
                                        <option value="attorney">Attorney Referral</option>
                                        <option value="advertisement">Advertisement</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="caseDescription" class="form-label">Brief Description of Case</label>
                                    <textarea class="form-control" id="caseDescription" name="caseDescription" rows="4" required></textarea>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/login/home.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Submit Client Intake</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
