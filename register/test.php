<?php

if(isset($_POST['submit'])){
    var_dump($_POST['username']);
    var_dump($_POST['password']);
    var_dump($_POST['ip']);
    var_dump($_POST['jobs']);
}


if($_POST['jobs'] === "Civilian" || $_POST['jobs'] === "Judge" || $_POST['jobs'] === "Attorney" ){
    echo "Success";
} else {
    echo "Please Select a job from the list";
}
?>
<!--DO NOT TOUCH THIS OR YOU WILL BREAK THE SITE. -->
    <?php include(base64_decode('Li4vaW5jbHVkZS9mb290ZXIucGhw'));?>
    <?php// include(base64_decode('Li4vLi4vaW5jbHVkZS9mb290ZXIucGhw'));?>