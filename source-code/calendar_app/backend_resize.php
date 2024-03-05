<?php

//require_once '_db.php';
include("./global.php");

class Result {}

$id=$_POST['id'];
$resource=$_POST['resource'];
$newStart=strtotime($_POST['newStart']);
$newEnd=strtotime($_POST['newEnd']);

$tableName=array(
    "request"=>"darlelJobber_requests",
    "job"=>"darlelJobber_visits",
    "ticket"=>"darlelJobber_tickets",
    "delivery"=>"darlelJobber_material_delivery",
    "shop"=>"darlelJobber_shop_orders",
);

$tableName=$tableName[$resource];
$query="select * from $tableName where id='$id'";
$deets=getRow($con,$query);
$responseMessage="Update successful";

$start_date=strtotime(date('Y-m-d',$newStart));
$start_time = date("H:i",$newStart);
$end_date=strtotime(date('Y-m-d',$newEnd));
$end_time = date("H:i",$newEnd);

if($resource=="request")
    $query="update darlelJobber_requests set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date' where id='$id'";
else if($resource=="job")
    $query="update darlelJobber_visits set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date' where id='$id'";
else if($resource=="ticket")
    $query="update darlelJobber_tickets set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date' where id='$id'";
else if($resource=="delivery")
    $query="update darlelJobber_material_delivery set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date' where id='$id'";
else if($resource=="shop")
    $query="update darlelJobber_shop_orders set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date' where id='$id'";

$result=$con->query($query);

$response = new Result();
$response->result = 'OK';
$response->message = $responseMessage;
header('Content-Type: application/json');
echo json_encode($response);
?>

