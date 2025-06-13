<?php

include("index.php");
if($_POST['generate']){
    $date = date("Y-md");
    $rand = rand(1, 100000);
    $casenum = "CF-" . $date . "-" . $rand;
//    echo "CF-" . $date . $rand;
echo "<table class='makeacase'><tr><td>Case ID Number:" . "<input type='text' name='casenum' id='casenum' value='$casenum'></td></tr></table>";
}
?>
    <?php// include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));?>
    <?php include(base64_decode('Li4vLi4vaW5jbHVkZS9mb290ZXIucGhw'));?>