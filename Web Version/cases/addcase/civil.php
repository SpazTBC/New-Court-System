<?php

include("index.php");
if($_POST['civil']){
    $date = date("Y-md");
    $rand = rand(1, 100000);
    $casenum = "CV-" . $date . "-" . $rand;
//    echo "CV-" . $date . $rand;
echo "<table class='makeacase'><tr><td>Case ID Number:" . "<input type='text' name='casenum' id='casenum' value='$casenum'></td></tr></table>";
}
?>
    <?php include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));?>