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
    <title>Client Details - Court System</title>
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
                        <h4 class="mb-0">Client Details</h4>
                        <div>
                            <a href="edit_intake.php?id=<?php echo $id; ?>" class="btn btn-warning me-2"><i class='bx bx-edit'></i> Edit</a>
                            <a href="view_intakes.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Back to List</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Personal Information</h5>
                            </div>
                            <div class="col-md-4">
                                <p class="fw-bold mb-1">Full Name:</p>
                                <p><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['middle_name'] . ' ' . $client['last_name']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="fw-bold mb-1">Date of Birth:</p>
                                <p><?php echo date('M d, Y', strtotime($client['dob'])); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="fw-bold mb-1">SSN (Last 4):</p>
                                <p><?php echo !empty($client['ssn_last_four']) ? 'XXX-XX-' . htmlspecialchars($client['ssn_last_four']) : 'Not provided'; ?></p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Contact Information</h5>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-bold mb-1">Email Address:</p>
                                <p><?php echo htmlspecialchars($client['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-bold mb-1">Phone Number:</p>
                                <p><?php echo htmlspecialchars($client['phone']); ?></p>
                            </div>
                            <div class="col-12">
                                <p class="fw-bold mb-1">Address:</p>
                                <p>
                                    <?php echo htmlspecialchars($client['address']); ?><br>
                                    <?php echo htmlspecialchars($client['city'] . ', ' . $client['state'] . ' ' . $client['zip']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Case Information</h5>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-bold mb-1">Case Type:</p>
                                <p><?php echo htmlspecialchars(ucfirst($client['case_type'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-bold mb-1">Referral Source:</p>
                                <p><?php echo !empty($client['referral_source']) ? htmlspecialchars(ucfirst($client['referral_source'])) : 'Not specified'; ?></p>
                            </div>
                            <div class="col-12">
                                <p class="fw-bold mb-1">Case Description:</p>
                                <p><?php echo nl2br(htmlspecialchars($client['case_description'])); ?></p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Intake Information</h5>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-bold mb-1">Intake Date:</p>
                                <p><?php echo date('M d, Y h:i A', strtotime($client['intake_date'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-bold mb-1">Intake By:</p>
                                <p><?php echo htmlspecialchars($client['intake_by']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>