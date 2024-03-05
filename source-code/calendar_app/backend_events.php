<?php

include("./global.php");
$db->exec("SET NAMES 'utf8'");

$startTime=strtotime($_POST['start'])-86400;
$endTime=strtotime($_POST['end'])+86400;

$idToInfoJob=[];
$idToInfoUser=[];
$idToAddress=[];

$query=getAll($con,"select *,concat(first_name,' ',last_name) as fullName,
concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress from darlelJobber_users where role='Client'");
foreach($query as $row){
    $idToInfoUser[$row['id']]=$row;
}
$query=getAll($con,"select *,concat(street1,',',street2,',',city,',',state,',',country,', Zip : ',zip_code) as fullAddress from darlelJobber_properties");
foreach($query as $row){
    $idToAddress[$row['id']]=$row['fullAddress'];
}
$jobs=getAll($con,"select * from darlelJobber_jobs");
foreach($jobs as $row){
    $idToInfoJob[$row['id']]=$row;
}

error_reporting(E_ERROR | E_PARSE);
$start = isset($_POST['start']) ? $_POST['start'] : $_GET['start'];
$end = isset($_POST['end']) ? $_POST['end'] : $_GET['end'];

$show_filter=$_SESSION['showFilter'];
$user_filter=$_SESSION['userFilter'];

//logic of searching the show filter is to check in string of show_filter
if($show_filter=="")//if nothing is string that means that everything needs to be shown
    $show_filter="all";

//agar khali hai user filter to matlab in clause use naheen karna hai warna string banalo jo in clause main daleinge
$showInClause=false;
if($user_filter!=""){
    $showInClause=true;
    $user_filter=explode(",",$user_filter);
    $inClause="";
    foreach($user_filter as $row){
        $inClause=$inClause."'".$row."',";
    }
    $inClause=rtrim($inClause, ",");
}

class Event {}
$events = array();
date_default_timezone_set("UTC");
$now = new DateTime("now");
$today = $now->setTime(0, 0, 0);


$purposes=["requests","jobs","tickets","delivery","tasks"];
//$purposes=["requests"];

foreach($purposes as $purpose){
    $result=[];
    $found=0;    
    
    if($purpose=="delivery" && $session_role=="Installation Crew")
        continue;
    if(($purpose=="requests") && ((strpos($show_filter, "requests") !== false) || (strpos($show_filter, "all") !== false))){ //if(($purpose=="requests" ) && ($search_type=="requests" || $search_type=="all"))
        $found=1;
        if(!$showInClause && ($permission['edit_everyone_schedule']))
            $query="select * from darlelJobber_requests where sendStatus='Request Sent' && scheduleStatus='Schedule Now' and start_date>=$startTime and end_date<=$endTime";
        else if($showInClause && ($permission['edit_everyone_schedule']))
           $query="SELECT distinct(r.id),r.convertStatus,r.request_for,r.title,r.start_time,r.start_date,r.end_time,r.end_date from darlelJobber_requests r inner join darlelJobber_teams t on r.id=t.requestId 
           where r.sendStatus='Request Sent' && r.scheduleStatus='Schedule Now' && t.userId in ($inClause) and r.start_date>=$startTime and r.end_date<=$endTime ";
        else if($permission['view_own_schedule'])
           $query="SELECT r.id,r.request_for,r.convertStatus,r.title,r.start_time,r.start_date,r.end_time,r.end_date from darlelJobber_requests r inner join darlelJobber_teams t on r.id=t.requestId 
           where t.userId='$session_id' && r.sendStatus='Request Sent' && r.scheduleStatus='Schedule Now' and r.start_date>=$startTime and r.end_date<=$endTime";
        $resource="request";
    }else if(($purpose=="jobs") && ((strpos($show_filter, "jobs") !== false) || (strpos($show_filter, "all") !== false))){
        $found=1;
        if(!$showInClause  && ($permission['edit_everyone_schedule']))
            $query = "select * from darlelJobber_visits where type='Schedule Now' and start_date>=$startTime and end_date<=$endTime";
        else if($showInClause && ($permission['edit_everyone_schedule']))
            $query="SELECT distinct(v.id),v.jobId,v.title,v.start_time,v.start_date,v.end_time,v.end_date from darlelJobber_visits v inner join darlelJobber_teams t on v.jobId=t.jobId 
            where type='Schedule Now' && t.userId in ($inClause) and v.start_date>=$startTime and v.end_date<=$endTime ";
        else if($permission['view_own_schedule'] )
            $query="SELECT v.id,v.jobId,v.title,v.start_time,v.start_date,v.end_time,v.end_date from darlelJobber_visits v inner join darlelJobber_teams t on v.id=t.visitId where t.userId='$session_id' 
            && type='Schedule Now' and v.start_date>=$startTime and v.end_date<=$endTime";
        $resource="job";
    }
    else if(($purpose=="tickets") && ((strpos($show_filter, "tickets") !== false) || (strpos($show_filter, "all") !== false))){
        $found=1;
        if(!$showInClause  && ($permission['edit_everyone_schedule']))
            $query = "select * from darlelJobber_tickets where scheduleType='Schedule Now' and start_date>=$startTime and end_date<=$endTime";
        else if($showInClause && ($permission['edit_everyone_schedule']))
            $query="SELECT distinct(t.id),t.title,t.start_time,t.start_date,t.end_time,t.end_date from darlelJobber_tickets t inner join darlelJobber_teams team on t.id=team.ticketId 
            where scheduleType='Schedule Now' && team.userId in ($inClause) and t.start_date>=$startTime and t.end_date<=$endTime";
        else if($permission['view_own_schedule'])
            $query="SELECT t.id,t.title,t.start_time,t.start_date,t.end_time,t.end_date from darlelJobber_tickets t inner join darlelJobber_teams team on t.id=team.ticketId where team.userId='$session_id' 
            && scheduleType='Schedule Now' and t.start_date>=$startTime and t.end_date<=$endTime";
        $resource="ticket";
    }
    else if(($purpose=="delivery" ) && ((strpos($show_filter, "delivery") !== false) || (strpos($show_filter, "all") !== false))){
        $found=1;
        $query = "select * from darlelJobber_material_delivery where schedule_type='Schedule Now' and start_date>=$startTime and end_date<=$endTime";
        $resource="delivery";
    }
    else if(($purpose=="shop") && ((strpos($show_filter, "shop") !== false) || (strpos($show_filter, "all") !== false))){
        $found=1;
        if(!$showInClause  && ($permission['edit_everyone_schedule']))
            $query = "select * from darlelJobber_shop_orders where scheduleType='Schedule Now' and start_date>=$startTime and end_date<=$endTime";
        else if($showInClause)
            $query="SELECT distinct(t.id),t.title,t.start_time,t.start_date,t.end_time,t.end_date,t.status from darlelJobber_shop_orders t inner join darlelJobber_teams team on t.id=team.shopOrderId 
            where scheduleType='Schedule Now' && team.userId in ($inClause) and t.start_date>=$startTime and t.end_date<=$endTime";
        else if($permission['view_own_schedule'])
            $query="SELECT t.id,t.title,t.start_time,t.start_date,t.end_time,t.end_date,t.status from darlelJobber_shop_orders t inner join darlelJobber_teams team on t.id=team.shopOrderId where team.userId='$session_id' 
            && scheduleType='Schedule Now' and t.start_date>=$startTime and t.end_date<=$endTime";
        $resource="shop";
    }
    else if(($purpose=="tasks") && ((strpos($show_filter, "tasks") !== false) || (strpos($show_filter, "all") !== false))){
        $found=1;
        $query="SELECT DISTINCT t.* FROM darlelJobber_tasks t LEFT JOIN darlelJobber_teams tt ON t.id = tt.taskId WHERE t.completionDate between $startTime and $endTime 
        and  t.addedBy = '$session_id' OR tt.userId = '$session_id'";
        if($showInClause)
            $query="SELECT DISTINCT t.* FROM darlelJobber_tasks t LEFT JOIN darlelJobber_teams tt ON t.id = tt.taskId WHERE t.completionDate between $startTime and $endTime and tt.userId in ($inClause)";
        $resource="tasks";
    }
    
    if($found){
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        foreach($result as $row) {
                $completedCheck=0;
                $e = new Event();
                $e->id = $row['id'];
                
                $e->resource = $resource;
                if($purpose=="requests"){
                    $address=$idToAddress[$row['propertyId']];
                    $clientName=$idToInfoUser[$row['request_for']]['fullName']."<br>"."Address : ".$address;
                }
                else if($purpose=="shop"){
                    $customerId=$idToInfoJob[$row['jobId']]['customerId'];
                    $clientName=$idToInfoUser[$customerId]['fullName'];
                }
                else
                    $clientName=$idToInfoUser[$row['customerId']]['fullName'];
                
                if($purpose=="delivery")
                    $row['title']=$row['sales_order'];
                $e->text = $row['title']."<br> Client : " . $clientName;
                if($resource=="job"){
                    $jobId=$row['jobId'];
                    $jobTitle=$idToInfoJob[$jobId]['title'];
                    $e->text = $row['title']."<br> Visit For $jobTitle";
                    
                    if($row['completionStatus']=="Completed"){
                        $completedCheck=1;
                    }
                }
                else if($resource=="shop" && $row['status']=="Completed"){//completed shop order
                    $completedCheck=1;
                }
                else if($resource=="request" && $row['convertStatus']=="Converted"){//completed request order
                    $completedCheck=1;
                }
                else if($resource=="ticket" && $row['completionStatus']=="Completed"){//completed ticket 
                    $completedCheck=1;
                }
                
                $start_date=date("Y-m-d",$row['start_date']);
                $start_time=$row['start_time'];
                
                $start_time = ($start_time=="") ? "11:30" : $row['start_time'];
                $start=$start_date." ".$start_time.":00";
                
                $end_date=date("Y-m-d",$row['end_date']);
                $end_time=$row['end_time'];
                $end_time = ($end_time=="") ? "12:30" : $row['end_time'];
                $end=$end_date." ".$end_time.":00";
                $e->start = $start;
                
                if($resource=="job"){
                    $jobId=$row['jobId'];
                    $jobDeets=getRow($con,"select * from darlelJobber_jobs where id='$jobId'");
                    $visitId=$row['id'];
                    $quoteId=$jobDeets['quoteId'];
                    $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
                    
                    //paid repair meaning that the title has repair in its name  light pink
                    if((strpos($quoteDeets['title'],"repair") !== false) || (strpos($quoteDeets['title'],"Repair") !== false))
                        $e->backColor="#FFB6C1";//pink color
                    //for cannot delay
                    if($jobDeets['cannot_delay']=="Yes")
                        $e->backColor="#008140";//green color
                    //full balance at the end
                    if($quoteDeets['complete_payment']=="Yes")
                        $e->backColor="#d4af37";//gold client pays full at the end
                    //pending payment
                    if($quoteDeets['paidStatus']=="Pending")
                        $e->backColor="#FF0000";//red color if not paid
                    //has paid and normal schedule
                    if(($quoteDeets['paidStatus']=="Paid" || $quoteDeets['complete_payment']=="Yes") && $jobDeets['required_811']=="Yes")
                        $e->backColor="#007bff";//blue has paid and normal schedule
                    //for required 811
                    if(($quoteDeets['paidStatus']=="Paid" || $quoteDeets['complete_payment']=="Yes") && $jobDeets['required_811']=="No")
                        $e->backColor="#000000";//black color
                    
                    //we will check every user that is assigned to this visit and if some user has isColorNeeded active then his color will over power every other color 
                    $sQuery="select u.colorCode,u.isColorNeeded from darlelJobber_users u inner join darlelJobber_teams t on u.id=t.userId where t.visitId='$visitId'";
                    $visitTeam=getAll($con,$sQuery);
                    foreach($visitTeam as $srow){
                        if($srow['isColorNeeded']=="Yes"){
                            $e->backColor=$srow['colorCode'];
                        }
                    }
                    
                }
                //yellow for material delivery
                else if($resource=="delivery")
                    $e->backColor="#ffe302";
                //green for shop order
                else if($resource=="shop"){
                    $shopOrderId=$row['id'];
                    $shopDeets=getRow($con,"select * from darlelJobber_shop_orders where id='$shopOrderId'");
                    $shopAdminAssigned=getRow($con,"select * from darlelJobber_users where role='Shop Admin' && id in (select userId from darlelJobber_teams where shopOrderId='$shopOrderId')");
                    $welderDeets=getRow($con,"select * from darlelJobber_users where role='Welder' && id in (select userId from darlelJobber_teams where shopOrderId='$shopOrderId')");
                    $aluminumWelder=0;$powderCoatingWelder=0;
                    
                    //checking if this order has been delayed
                    $currentTime=time();
                    $end_time=$shopDeets['end_time'];
                    $end_date=$shopDeets['end_date'];
                    
                    $end_time_timestamp = strtotime(date('Y-m-d') . ' ' . $end_time);
                    $end_datetime = date('Y-m-d H:i:s', $end_date);
                    $end_datetime_timestamp = strtotime($end_datetime);

                    if(strpos($welderDeets['name'], "aluminium") !== false)
                        $aluminumWelder=1;
                    else if(strpos($welderDeets['name'], "powder coating") !== false)
                        $powderCoatingWelder=1;
                    $e->backColor="#FF0000";//initially red color is assigned assuming that the drafter is assigned
                    if(count($shopAdminAssigned)>1)//shop admin is assigned so color turns to blue
                        $e->backColor="#0000FF";
                    if($aluminumWelder)//green color if aluminium welder
                        $e->backColor="#228b22";
                    if((!$aluminumWelder) && (count($welderDeets)>1) )//if not aluminium welder then assign the color that is assigned to that welder    
                        $e->backColor=$welderDeets['colorCode'];
                    if($powderCoatingWelder)//if powder coating welder then color changes to sky blue
                        $e->backColor="#87CEEB";
                    if($shopDeets['status']=="Completed")//if completed the assign green color
                        $e->backColor="#009a16";
                    if ($currentTime > $end_datetime_timestamp && $shopDeets['status']=="Not Completed")//this means that this order is delayed meaning past time its end_time
                        $e->backColor="#f70000";
                }
                //lime for tickets
                else if($resource=="ticket"){
                    //default color will be this, but if it's assigned to someone then fetch the color of that crew
                    $e->backColor="#596c1f";
                    
                    //if it's assigned to dayan or ariel then assign their color whichever is fetched first
                    $ticketId=$row['id'];
                    $query="SELECT u.colorCode from darlelJobber_users u inner join darlelJobber_teams t on u.id=t.userId where t.ticketId='$ticketId' and  ( u.id='BBFAZ9HXCC' or u.id='44N1AT3Y9T' )";
                    $colorCode=getRow($con,$query);
                    if(count($colorCode)>0)
                        $e->backColor=$colorCode['colorCode'];
                }
                //purple for delivery
                else if($resource=="delivery")
                    $e->backColor="#b19cd8";
                //orange for requests
                else if($resource=="request"){
                    //use the color code of the estimator who is assigned to that request otherwise use yellow
                    $requestId=$row['id'];
                    $nquery="SELECT colorCode from darlelJobber_users where id = 
                    (select userId from darlelJobber_teams where requestId='$requestId' && userId in (select userId from darlelJobber_users where role='Estimator') limit 1) ";
                    $colorCode=getRow($con,$nquery);
                    if(count($colorCode)!=0)
                        $e->backColor=$colorCode['colorCode'];
                    else if($row['appointmentStatus']=="Active"){
                        $completedCheck=1;
                        $e->backColor="#e84d93";
                    }
                    else
                        $e->backColor="#FFA900";
                }
                
                $e->fontColor="white";
                $e->borderColor="darker";
            
                $e->end = $end;
                $start_time_temp = date("h:i A", strtotime($start_time));
                $end_time_temp = date("h:i A", strtotime($end_time));
                if(!$completedCheck)
                    $e->text=$e->text." ".$start_time_temp." : ".$end_time_temp; 
                else if($completedCheck)
                    $e->text="<del>".$e->text."</del><del>".$start_time_temp." : ".$end_time_temp."</del>"; 
                
                $e->bubbleHtml = ucfirst($resource)." Details: <br>".$e->text;
                
                if($resource=="tasks"){
                    $start_time=$row['start_time'];
                    $start_time_temp = date("h:i A", strtotime($start_time));
                    $end_time_temp = date("h:i A", strtotime($end_time));
                    $start_date=date("Y-m-d",$row['completionDate']);
                    
                    $start_time = ($start_time=="") ? "12:30" : $row['start_time'];
                    $start=$start_date." ".$start_time.":00";
                    $end_date=date("Y-m-d",$row['end_date']);
                    $end_time=$row['end_time'];
                    $end_time = ($end_time=="") ? "12:30" : $row['end_time'];
                    $end=$start_date." ".$end_time.":00";
                    
                    $e->start = $start;
                    $e->end = $end;
                    $e->text=$row['title']." ( ".$row['label']." )";
                    
                    $e->text=$e->text." ".$start_time_temp." : ".$end_time_temp; 
                    $e->backColor="#013220";
                    $e->bubbleHtml = ucfirst($resource)." Details: <br/>".$e->text;
                    if($row['status']=="Completed"){
                        $e->backColor="#c5a12b";
                        $e->text="<del>".$e->text."</del><del>".$start_time_temp." : ".$end_time_temp."</del>"; 
                    }
                }
                $events[] = $e;
            
        }
    }
}


header('Content-Type: application/json');
$jsonData = json_encode($events);
if ($jsonData === false) {
    echo json_last_error_msg();
} else {
    echo $jsonData;
}
?>

