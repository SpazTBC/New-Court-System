<?php
session_start();
// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: /login/index.php");
    exit();
}

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_intakes.php");
    exit();
}

$id = $_GET['id'];

// Database connection
require_once "../include/database.php";

// Get client intake details
try {
    $stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: view_intakes.php");
        exit();
    }
    
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client Intake - Court System</title>
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
                    <div class="card-header bg-dark text-white">
                        <h4 class="mb-0">Edit Client Intake</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success']) && $_GET['success'] === 'true'): ?>
                            <div class="alert alert-success" role="alert">
                                Client information has been successfully updated.
                            </div>
                        <?php endif; ?>

                        <form method="post" action="update_intake.php">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2">Personal Information</h5>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($client['first_name']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" name="middleName" value="<?php echo htmlspecialchars($client['middle_name']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($client['last_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" value="<?php echo $client['dob']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ssn" class="form-label">SSN (Last 4 digits)</label>
                                    <input type="text" class="form-control" id="ssn" name="ssn" maxlength="4" pattern="\d{4}" placeholder="XXXX" value="<?php echo htmlspecialchars($client['ssn_last_four']); ?>">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2">Contact Information</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Street Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($client['address']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($client['city']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <select class="form-select" id="state" name="state" required>
                                        <option value="" disabled>Select State</option>
                                        <!-- Common states first -->
                                        <option value="CA" <?php echo ($client['state'] == 'CA') ? 'selected' : ''; ?>>California</option>
                                        <option value="TX" <?php echo ($client['state'] == 'TX') ? 'selected' : ''; ?>>Texas</option>
                                        <option value="FL" <?php echo ($client['state'] == 'FL') ? 'selected' : ''; ?>>Florida</option>
                                        <option value="NY" <?php echo ($client['state'] == 'NY') ? 'selected' : ''; ?>>New York</option>
                                        <option value="IL" <?php echo ($client['state'] == 'IL') ? 'selected' : ''; ?>>Illinois</option>
                                        <!-- All states alphabetically -->
                                        <option value="AL" <?php echo ($client['state'] == 'AL') ? 'selected' : ''; ?>>Alabama</option>
                                        <option value="AK" <?php echo ($client['state'] == 'AK') ? 'selected' : ''; ?>>Alaska</option>
                                        <option value="AZ" <?php echo ($client['state'] == 'AZ') ? 'selected' : ''; ?>>Arizona</option>
                                        <option value="AR" <?php echo ($client['state'] == 'AR') ? 'selected' : ''; ?>>Arkansas</option>
                                        <option value="CO" <?php echo ($client['state'] == 'CO') ? 'selected' : ''; ?>>Colorado</option>
                                        <option value="CT" <?php echo ($client['state'] == 'CT') ? 'selected' : ''; ?>>Connecticut</option>
                                        <option value="DE" <?php echo ($client['state'] == 'DE') ? 'selected' : ''; ?>>Delaware</option>
                                        <option value="DC" <?php echo ($client['state'] == 'DC') ? 'selected' : ''; ?>>District of Columbia</option>
                                        <option value="GA" <?php echo ($client['state'] == 'GA') ? 'selected' : ''; ?>>Georgia</option>
                                        <option value="HI" <?php echo ($client['state'] == 'HI') ? 'selected' : ''; ?>>Hawaii</option>
                                        <option value="ID" <?php echo ($client['state'] == 'ID') ? 'selected' : ''; ?>>Idaho</option>
                                        <option value="IN" <?php echo ($client['state'] == 'IN') ? 'selected' : ''; ?>>Indiana</option>
                                        <option value="IA" <?php echo ($client['state'] == 'IA') ? 'selected' : ''; ?>>Iowa</option>
                                        <option value="KS" <?php echo ($client['state'] == 'KS') ? 'selected' : ''; ?>>Kansas</option>
                                        <option value="KY" <?php echo ($client['state'] == 'KY') ? 'selected' : ''; ?>>Kentucky</option>
                                        <option value="LA" <?php echo ($client['state'] == 'LA') ? 'selected' : ''; ?>>Louisiana</option>
                                        <option value="ME" <?php echo ($client['state'] == 'ME') ? 'selected' : ''; ?>>Maine</option>
                                        <option value="MD" <?php echo ($client['state'] == 'MD') ? 'selected' : ''; ?>>Maryland</option>
                                        <option value="MA" <?php echo ($client['state'] == 'MA') ? 'selected' : ''; ?>>Massachusetts</option>
                                        <option value="MI" <?php echo ($client['state'] == 'MI') ? 'selected' : ''; ?>>Michigan</option>
                                        <option value="MN" <?php echo ($client['state'] == 'MN') ? 'selected' : ''; ?>>Minnesota</option>
                                        <option value="MS" <?php echo ($client['state'] == 'MS') ? 'selected' : ''; ?>>Mississippi</option>
                                        <option value="MO" <?php echo ($client['state'] == 'MO') ? 'selected' : ''; ?>>Missouri</option>
                                        <option value="MT" <?php echo ($client['state'] == 'MT') ? 'selected' : ''; ?>>Montana</option>
                                        <option value="NE" <?php echo ($client['state'] == 'NE') ? 'selected' : ''; ?>>Nebraska</option>
                                        <option value="NV" <?php echo ($client['state'] == 'NV') ? 'selected' : ''; ?>>Nevada</option>
                                        <option value="NH" <?php echo ($client['state'] == 'NH') ? 'selected' : ''; ?>>New Hampshire</option>
                                        <option value="NJ" <?php echo ($client['state'] == 'NJ') ? 'selected' : ''; ?>>New Jersey</option>
                                        <option value="NM" <?php echo ($client['state'] == 'NM') ? 'selected' : ''; ?>>New Mexico</option>
                                        <option value="NC" <?php echo ($client['state'] == 'NC') ? 'selected' : ''; ?>>North Carolina</option>
                                        <option value="ND" <?php echo ($client['state'] == 'ND') ? 'selected' : ''; ?>>North Dakota</option>
                                        <option value="OH" <?php echo ($client['state'] == 'OH') ? 'selected' : ''; ?>>Ohio</option>
                                        <option value="OK" <?php echo ($client['state'] == 'OK') ? 'selected' : ''; ?>>Oklahoma</option>
                                        <option value="OR" <?php echo ($client['state'] == 'OR') ? 'selected' : ''; ?>>Oregon</option>
                                        <option value="PA" <?php echo ($client['state'] == 'PA') ? 'selected' : ''; ?>>Pennsylvania</option>
                                        <option value="RI" <?php echo ($client['state'] == 'RI') ? 'selected' : ''; ?>>Rhode Island</option>
                                        <option value="SC" <?php echo ($client['state'] == 'SC') ? 'selected' : ''; ?>>South Carolina</option>
                                        <option value="SD" <?php echo ($client['state'] == 'SD') ? 'selected' : ''; ?>>South Dakota</option>
                                        <option value="TN" <?php echo ($client['state'] == 'TN') ? 'selected' : ''; ?>>Tennessee</option>
                                        <option value="UT" <?php echo ($client['state'] == 'UT') ? 'selected' : ''; ?>>Utah</option>
                                        <option value="VT" <?php echo ($client['state'] == 'VT') ? 'selected' : ''; ?>>Vermont</option>
                                        <option value="VA" <?php echo ($client['state'] == 'VA') ? 'selected' : ''; ?>>Virginia</option>
                                        <option value="WA" <?php echo ($client['state'] == 'WA') ? 'selected' : ''; ?>>Washington</option>
                                        <option value="WV" <?php echo ($client['state'] == 'WV') ? 'selected' : ''; ?>>West Virginia</option>
                                        <option value="WI" <?php echo ($client['state'] == 'WI') ? 'selected' : ''; ?>>Wisconsin</option>
                                        <option value="WY" <?php echo ($client['state'] == 'WY') ? 'selected' : ''; ?>>Wyoming</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="zip" class="form-label">Zip Code</label>
                                    <input type="text" class="form-control" id="zip" name="zip" value="<?php echo htmlspecialchars($client['zip']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2">Case Information</h5>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="caseType" class="form-label">Case Type</label>
                                    <select class="form-select" id="caseType" name="caseType" required>
                                        <option value="" disabled>Select Case Type</option>
                                        <option value="criminal" <?php echo ($client['case_type'] == 'criminal') ? 'selected' : ''; ?>>Criminal</option>
                                        <option value="civil" <?php echo ($client['case_type'] == 'civil') ? 'selected' : ''; ?>>Civil</option>
                                        <option value="family" <?php echo ($client['case_type'] == 'family') ? 'selected' : ''; ?>>Family</option>
                                        <option value="probate" <?php echo ($client['case_type'] == 'probate') ? 'selected' : ''; ?>>Probate</option>
                                        <option value="other" <?php echo ($client['case_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="referralSource" class="form-label">Referral Source</label>
                                    <select class="form-select" id="referralSource" name="referralSource">
                                        <option value="" disabled>How did you hear about us?</option>
                                        <option value="internet" <?php echo ($client['referral_source'] == 'internet') ? 'selected' : ''; ?>>Internet Search</option>
                                        <option value="friend" <?php echo ($client['referral_source'] == 'friend') ? 'selected' : ''; ?>>Friend/Family</option>
                                        <option value="attorney" <?php echo ($client['referral_source'] == 'attorney') ? 'selected' : ''; ?>>Attorney Referral</option>
                                        <option value="advertisement" <?php echo ($client['referral_source'] == 'advertisement') ? 'selected' : ''; ?>>Advertisement</option>
                                        <option value="other" <?php echo ($client['referral_source'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="caseDescription" class="form-label">Brief Description of Case</label>
                                    <textarea class="form-control" id="caseDescription" name="caseDescription" rows="4" required><?php echo htmlspecialchars($client['case_description']); ?></textarea>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="view_details.php?id=<?php echo $id; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Client Information</button>
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
