<?php

//require_once '_db.php';
include("./global.php");
$id=$_POST['id'];
    
if($_POST['resource']=="request")
    $query="DELETE FROM darlelJobber_requests where id='$id'";
else if($_POST['resource']=="job")
    $query="DELETE FROM darlelJobber_visits where id='$id'";
else if($_POST['resource']=="ticket")
    $query="DELETE FROM darlelJobber_tickets where id='$id'";
else if($_POST['resource']=="delivery")
    $query="DELETE FROM darlelJobber_material_delivery where id='$id'";

$result=$con->query($query);


class Result {}

$response = new Result();
$response->result = 'OK';
$response->message = 'Delete successful';
header('Content-Type: application/json');
echo json_encode($response);
?>

