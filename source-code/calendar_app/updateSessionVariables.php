<?php
//require_once '_db.php';

include("./global.php");

$newDate=$_POST['newDate'];
$show_filter=$_POST['show_filter'];
$user_filter=$_POST['user_filter'];
$_SESSION['calendarStartDate']=$newDate;
$_SESSION['showFilter']=$show_filter;
$_SESSION['userFilter']=$user_filter;
?>

