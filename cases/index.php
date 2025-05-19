<?php
session_start();
$menu = "CASES";

if(!isset($_SESSION['username'])) {
    header("Location:../index.php");
    exit();
}

include("../include/database.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-brand d-flex align-items-center">
                <img src="../images/logo.png" alt="Logo" class="img-fluid me-2" style="max-height: 40px;">
                <span class="ms-2">Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php include("../include/menu.php"); ?>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Main Cases Section -->
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0">My Cases</h3>
                <a href="addcase/" class="btn btn-primary">
                    <i class='bx bx-plus'></i> New Case
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Supervisor</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM cases WHERE assigneduser = :username");
                            $stmt->execute(['username' => $_SESSION['username']]);
                                                        
                            while($case = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['id']); ?></td>
                                    <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                    <td>
                                        <?php if($case['supervisor']): ?>
                                            <span class="badge bg-success">
                                                <i class='bx bx-check'></i> <?php echo htmlspecialchars($case['supervisor']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No Supervisor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Supervisor Cases Section -->
        <?php if(isSupervisor($_SESSION['username'])): ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">Supervised Cases</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Assigned To</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM cases WHERE supervisor = :username");
                            $stmt->execute(['username' => $_SESSION['username']]);
                            
                            while($case = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['id']); ?></td>
                                    <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Debug section - add this after the table -->
                <?php if ($stmt->rowCount() === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="alert alert-info">
                                No cases found for user: <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- Shared Cases Section -->
        <?php
        $sharedTypes = ['shared01', 'shared02', 'shared03', 'shared04'];
        foreach($sharedTypes as $index => $type):
            $stmt = $conn->prepare("SELECT * FROM cases WHERE $type = :username");
            $stmt->execute(['username' => $_SESSION['username']]);
            if($stmt->rowCount() > 0):
        ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white">
                <h3 class="mb-0">Shared Cases - <?php echo $index + 1; ?></h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Case Number</th>
                                <th>Assigned To</th>
                                <th>Date Assigned</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($case = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($case['id']); ?></td>
                                    <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigneduser']); ?></td>
                                    <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                                    <td><?php echo htmlspecialchars($case['type']); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-info">
                                            <i class='bx bx-show'></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>

    <?php include("../include/footer.php"); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function isSupervisor($username) {
    global $conn;
    $stmt = $conn->prepare("SELECT supervisorjob FROM users WHERE username = ? AND supervisorjob = '1'");
    $stmt->execute([$username]);
    return $stmt->rowCount() > 0;
}
?>