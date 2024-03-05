<?php

//require_once '_db.php';
include("./global.php");

class Result {}

$id=$_POST['id'];
$newStart=strtotime($_POST['newStart']);
$newEnd=strtotime($_POST['newEnd']);
$resource=$_POST['resource'];

$start_date=strtotime(date('Y-m-d',$newStart));
$start_time = date("H:i",$newStart);
$end_date=strtotime(date('Y-m-d',$newEnd));
$end_time = date("H:i",$newEnd);

if($resource=="ticket")
    $query="update darlelJobber_tickets set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date',scheduleType='Schedule Now' where id='$id' ";
else if($resource=="delivery")
    $query="update darlelJobber_material_delivery set start_time='$start_time',end_time='$end_time',start_date='$start_date',end_date='$end_date',schedule_type='Schedule Now' where id='$id' ";
else if($resource=="visit" || $resource=="job")
    $query="update darlelJobber_visits set start_date='$start_date',start_time='$start_time',end_date='$end_date',end_time='$end_time',type='Schedule Now' where id='$id' ";
else if($resource=="request")
    $query="update darlelJobber_requests set start_date='$start_date',start_time='$start_time',end_date='$end_date',end_time='$end_time',scheduleStatus='Schedule Now' where id='$id' ";
else if($resource=="shop")
    $query="update darlelJobber_shop_orders set start_date='$start_date',start_time='$start_time',end_date='$end_date',end_time='$end_time',scheduleType='Schedule Now' where id='$id' ";

$result=$con->query($query);

$response = new Result();
$response->result = 'OK';
$response->message = 'Update successful';
header('Content-Type: application/json');
echo json_encode($response);
?>

