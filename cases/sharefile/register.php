<?php
session_start();
$menu = "CASE";
include('../../includes/database.php');

if(isset($_POST['submit']))
{
    $casenum = $_POST['casenum'];
    $shared1 = $_POST['shared1'];
    $shared2 = $_POST['shared2'];
    $shared3 = $_POST['shared3'];
    $shared4 = $_POST['shared4'];
    

    $query = "UPDATE cases SET shared01=:shared1, shared02=:shared2, shared03=:shared3, shared04=:shared4 WHERE caseid=:caseid";
    $query_run = $conn->prepare($query);

    $data = [
        ':caseid' => $casenum,
        ':shared01' => $shared1,
        ':shared02' => $shared2,
        ':shared03' => $shared3,
        ':shared04' => $shared4,
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