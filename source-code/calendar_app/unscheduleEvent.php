<?php
include("./global.php");


$id=$_POST['id'];
$resource=$_POST['resource'];

if($resource=="request")
    $query="update darlelJobber_requests set scheduleStatus='Schedule Later' where id='$id'";
else if($resource=="job")
    $query="update darlelJobber_visits set type='Schedule Later' where id='$id'";
else if($resource=="ticket")
    $query="update darlelJobber_tickets set scheduleType='Schedule Later' where id='$id'";
else if($resource=="delivery")
    $query="update darlelJobber_material_delivery set schedule_type='Schedule Later' where id='$id'";
else if($resource=="shop")
    $query="update darlelJobber_shop_orders set scheduleType='Schedule Later' where id='$id'";
$result=$con->query($query);
if(!$result){
    echo $con->error;
    exit();
}

?>