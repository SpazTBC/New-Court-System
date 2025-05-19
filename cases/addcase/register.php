<html>
<head>
        <link rel="stylesheet" href="../../css/main.css">
        <title>Add A Case</title>
        </head>
        <div id="menu">
            <div class='logo'>
                <!-- <img src="../../images/logo.png"/> -->
                <span class="fw-bold text-white">Blackwood & Associates</span>
             </div> <!-- logo end -->
             <!-- LINK SEPERATION -->
         <!--    <?php //include("../../include/menu.php"); ?>-->
        </div> <!-- MENU -->
    <body>
<?php
session_start();
$menu = "CASE";
include('../../include/database.php');

if(isset($_POST['submit']))
{
    $casenum = $_POST['casenum'];
    $user = $_SESSION['username'];
    $username2 = $_SESSION['username'];
    $date = $_POST['date'];
    $details = $_POST['details'];
    $shared1 = $_POST['shared1'];
    $shared2 = $_POST['shared2'];
    $shared3 = $_POST['shared3'];
    $shared4 = $_POST['shared4'];
    $defendent = $_POST['defendent'];
    
    if(strpos($casenum, 'CF') !== false) {
        $type = 'CRIMINAL';
    } elseif(strpos($casenum, 'CV') !== false) {
        $type = 'CIVIL';
    } elseif(strpos($casenum, 'F') !== false) {
        $type = 'FAMILY';
    } else {
        $type = 'UNKNOWN';
    }

    $query = "INSERT INTO cases (caseid,assigneduser,assigned,details,shared01,shared02,shared03,shared04,type,defendent) 
    VALUES (:caseid,:assigneduser,:assigned,:details,:shared01,:shared02,:shared03,:shared04,:type,:defendant)";
    $query_run = $conn->prepare($query);

    $data = [
        ':caseid' => $casenum,
        ':assigneduser' => $user,
        ':assigned' => $date,
        ':details' => $details,
        ':shared01' => $shared1,
        ':shared02' => $shared2,
        ':shared03' => $shared3,
        ':shared04' => $shared4,
        ':type' => $type,
        ':defendant' => $defendent,
    ];
    $query_execute = $query_run->execute($data);

    if($query_execute)
    {
        echo "Inserted Successfully Return To <a href='../index.php'>Home</a>";
    }
    else
    {
        echo "Not Inserted";
    }
}

?>
<!--DO NOT TOUCH THIS OR YOU WILL BREAK THE SITE. -->
    <?php// include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));?>
    <?php include(base64_decode('Li4vLi4vaW5jbHVkZS9mb290ZXIucGhw'));?>
</body>
</html>