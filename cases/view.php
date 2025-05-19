<?php
session_start();
$menu = "CASES";
include("../include/database.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Case Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
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
        $caseId = $_GET['id'];
        $stmt = $conn->prepare("
            SELECT c.*, u.job 
            FROM cases c 
            JOIN users u ON u.username = :username 
            WHERE c.id = :caseId
        ");
        $stmt->execute([
            'username' => $_SESSION['username'],
            'caseId' => $caseId
        ]);
        while($case = $stmt->fetch()):
        ?>
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Case File: <?php echo htmlspecialchars($case['caseid']); ?></h3>
                <span class="badge bg-primary"><?php echo htmlspecialchars($case['type']); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th>Case ID:</th>
                                <td><?php echo htmlspecialchars($case['id']); ?></td>
                            </tr>
                            <tr>
                                <th>Case Number:</th>
                                <td><?php echo htmlspecialchars($case['caseid']); ?></td>
                            </tr>
                            <tr>
                                <th>Date Assigned:</th>
                                <td><?php echo htmlspecialchars($case['assigned']); ?></td>
                            </tr>
                            <tr>
                                <th>Details:</th>
                                <td><?php echo htmlspecialchars($case['details']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">File Upload</h5>
                            </div>
                            <div class="card-body">
                                <?php if($case['job'] !== "Civilian" && $_SESSION['username'] !== $case['defendent']): ?>
                                   <form enctype="multipart/form-data" action="upload.php" method="post" class="mb-3">
                                        <div class="mb-3">
                                             <label class="form-label">Select File</label>
                                             <input type="file" class="form-control" name="file" id="file">
                                        </div>
                                        <input type="hidden" value="<?php echo $case['caseid']; ?>" name="caseids">
                                        <input type="hidden" value="<?php echo $case['id']; ?>" name="id">
                                        <button type="submit" name="submit" class="btn btn-primary">
                                             <i class='bx bx-upload'></i> Upload File
                                        </button>
                                   </form>
                                <?php endif; ?>

                                <div class="mt-4">
                                    <h6>Case Files</h6>
                                    <?php
                                    $dir = 'uploads/' . $case['caseid'] . '/';
                                    if (file_exists($dir)):
                                        $files = glob($dir . "*");
                                        if (!empty($files)):
                                    ?>
                                        <div class="list-group">
                                            <?php foreach($files as $file): ?>
                                                <a href="<?php echo $dir . basename($file); ?>" 
                                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span><i class='bx bx-file'></i> <?php echo basename($file); ?></span>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo date("m/d/Y", filemtime($file)); ?>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">No files uploaded yet</div>
                                    <?php 
                                        endif;
                                    endif; 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if($case['job'] !== "Civilian" && $_SESSION['username'] !== $case['defendent']): ?>
                    <div class="mt-4">
                        <div class="d-flex gap-2">
                            <a href="modify.php?id=<?php echo $case['id']; ?>" class="btn btn-warning">
                                <i class='bx bx-edit'></i> Modify
                            </a>
                            <a href="delete.php?id=<?php echo $case['id']; ?>" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this case?')">
                                <i class='bx bx-trash'></i> Delete
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Cases
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Back to Cases
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php include("../include/footer.php"); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
