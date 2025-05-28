<?php
require_once '../include/database.php';
require_once '../auth/character_auth.php';

$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header("Location: ../?error=not_found");
    exit();
}

// Validate character access
$auth = validateCharacterAccess($_GET['charactername']);
if (!$auth['valid']) {
    header("Location: ../?error=no_access&charactername=" . urlencode($_GET['charactername']));
    exit();
}

$characterName = $currentCharacter['charactername'];
$characterJob = $currentCharacter['job'];

// Get client ID from URL
$clientId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Get client details
$stmt = $conn->prepare("SELECT * FROM client_intake WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: index.php?charactername=" . urlencode($characterName) . "&error=client_not_found");
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
            <a class="navbar-brand" href="../">
                <i class='bx bx-building'></i> Court System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text">
                    <i class='bx bx-user'></i>
                    <span class="ms-2"><?php echo htmlspecialchars($characterName); ?> (<?php echo htmlspecialchars($characterJob); ?>)</span>
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class='bx bx-user'></i> Client Details</h3>
                        <div>
                            <a href="index.php?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-outline-light">
                                <i class='bx bx-arrow-back'></i> Back to Client Intake
                            </a>
                            <a href="edit_intake.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-warning">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3"><i class='bx bx-user'></i> Personal Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Full Name:</strong></td>
                                        <td>
                                            <?php 
                                            $fullName = trim($client['first_name'] . ' ' . ($client['middle_name'] ? $client['middle_name'] . ' ' : '') . $client['last_name']);
                                            echo htmlspecialchars($fullName); 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php if (!empty($client['dob'])): ?>
                                    <tr>
                                        <td><strong>Date of Birth:</strong></td>
                                        <td><?php echo date('M j, Y', strtotime($client['dob'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($client['ssn_last_four'])): ?>
                                    <tr>
                                        <td><strong>SSN (Last 4):</strong></td>
                                        <td>***-**-<?php echo htmlspecialchars($client['ssn_last_four']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>

                                <h5 class="mb-3 mt-4"><i class='bx bx-phone'></i> Contact Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                    </tr>
                                    <?php if (!empty($client['email'])): ?>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($client['address'])): ?>
                                    <tr>
                                        <td><strong>Address:</strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($client['address']); ?>
                                            <?php if (!empty($client['city']) || !empty($client['state']) || !empty($client['zip'])): ?>
                                                <br>
                                                <?php echo htmlspecialchars($client['city']); ?>
                                                <?php if (!empty($client['state'])): ?>, <?php echo htmlspecialchars($client['state']); ?><?php endif; ?>
                                                <?php if (!empty($client['zip'])): ?> <?php echo htmlspecialchars($client['zip']); ?><?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>

                            <!-- Case Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3"><i class='bx bx-file'></i> Case Information</h5>
                                <table class="table table-borderless">
                                    <?php if (!empty($client['case_type'])): ?>
                                    <tr>
                                        <td><strong>Case Type:</strong></td>
                                        <td><?php echo htmlspecialchars($client['case_type']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($client['referral_source'])): ?>
                                    <tr>
                                        <td><strong>Referral Source:</strong></td>
                                        <td><?php echo htmlspecialchars($client['referral_source']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Intake Date:</strong></td>
                                        <td><?php echo $client['intake_date'] ? date('M j, Y g:i A', strtotime($client['intake_date'])) : 'N/A'; ?></td>
                                    </tr>
                                    <?php if (!empty($client['intake_by'])): ?>
                                    <tr>
                                        <td><strong>Intake By:</strong></td>
                                        <td><?php echo htmlspecialchars($client['intake_by']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>

                                <h5 class="mb-3 mt-4"><i class='bx bx-file-blank'></i> Case Description</h5>
                                <div class="border p-3 bg-light">
                                    <?php echo nl2br(htmlspecialchars($client['case_description'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="index.php?charactername=<?php echo urlencode($characterName); ?>" class="btn btn-secondary">
                                        <i class='bx bx-arrow-back'></i> Back to List
                                    </a>
                                    <a href="edit_intake.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-warning">
                                        <i class='bx bx-edit'></i> Edit Client
                                    </a>
                                    <a href="clients/profile.php?id=<?php echo $client['id']; ?>&charactername=<?php echo urlencode($characterName); ?>" class="btn btn-primary">
                                        <i class='bx bx-folder'></i> Client Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>