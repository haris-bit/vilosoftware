<?php

$host='localhost';
$username='anomozco_octcric';
$user_pass='rWg#M$vFYk]+';
$database_in_use='anomozco_octcric';

$g_projectTitle = "smsCampaigner";
$g_projectSlug = "smsCampaigner";
$g_primaryColor = "#007ed3";
$g_secondaryColor = "#007ed3";


$con = mysqli_connect($host,$username,$user_pass,$database_in_use);

if (!$con)
{
    echo"not connected";
}
if (!mysqli_select_db($con,$database_in_use))
{
    echo"database not selected";
}
$con->set_charset("utf8");

?>