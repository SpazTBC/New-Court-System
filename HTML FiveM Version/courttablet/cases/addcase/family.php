<?php

include("index.php");
if($_POST['family']){
    error_reporting(0);
    $date = date("Ymd");
    $rand = rand(1, 100000);
    $casenum = "F-" . $date . "-" . $rand;
    //echo "F-" . $date . "-" . $rand;
    echo "<table class='makeacase'><tr><td>Case ID Number:" . "<input type='text' name='casenum' id='casenum' value='$casenum'></td></tr>";
}
?>
    <?php include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));?>