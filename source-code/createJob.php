<?
require("./global.php");

if($logged==0 || (!$permission['view_jobs']))
    header("Location:./index.php");

if((isset($_GET['viewFromShop']) && ($permission['edit_jobs']))){
    $entryId=clear($_GET['entryId']);
    header("Location:?entryId=$entryId");
    exit();
}

$edit=0;
$view=0;
$timeAdded=time();
    
if(isset($_GET['view']))
    $view=1;
else if(isset($_GET['entryId']))
    $edit=1;

$users=getAll($con,"select * from darlelJobber_users");
foreach($users as $row)
{$idToInfo[$row['id']]=$row;}

$properties=getAll($con,"select * from darlelJobber_properties");

$entryId=clear($_GET['entryId']);
$jobId=$entryId;
$jobDeets=getRow($con,"select * from darlelJobber_jobs where id='$entryId'");
$quoteId=$jobDeets['quoteId'];

$quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");

//getting the end_date of the first visit
$firstVisitDeets=getRow($con,"select * from darlelJobber_visits where jobId='$jobId' order by timeAdded asc limit 1");

//If a shop order finish date is moved to after the first visit on the job then a error message should pop up confirming they understand this
$displayAlertShop = ($firstVisitDeets==null || $firstVisitDeets['completionStatus']=="Completed") ? 0 : 1;

$userDeetsId=$jobDeets['customerId'];
$propertyDeetsId=$quoteDeets['propertyId'];

$userDeets=getRow($con,"select * from darlelJobber_users where id='$userDeetsId'");
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");

$services=getAll($con,"select * from darlelJobber_services where localUseId='None' || localUseId='$quoteId' order by timeAdded desc");
$allServices=[];
foreach($services as $row){
    $index=$row['name']." SKU =".$row['sku'];
    $allServices[$index]=$row;
}

if(isset($_GET['labor_delete'])){
    $query="delete from darlelJobber_quote_details where quoteId='$quoteId' && service='Labor Fees'";
    runQuery($query);
    updateQuote($quoteId);
    header("Location:?entryId=$jobId");
}

if((isset($_POST['create_job'])) || (isset($_POST['convertInvoice']))){
    //job main entry
    $title = clear($_POST['title']);
    $job_number = clear($_POST['job_number']);
    $start_date = strtotime(clear($_POST['start_date']));
    $end_date = strtotime(clear($_POST['end_date']));
    $start_time = clear($_POST['start_time']);
    $end_time = clear($_POST['end_time']);
    $propertyId = clear($_POST['propertyId']);
    $internal_notes = clear($_POST['internal_notes']);
    $job_for=clear($_POST['job_for']);
    $final_total=clear($_POST['final_total']);
    $required_811=clear($_POST['required_811']);
    $cannot_delay=clear($_POST['cannot_delay']);
    
    $required_811 = ($required_811=="on") ? "Yes" : "No";
    $cannot_delay = ($cannot_delay=="on") ? "Yes" : "No";
    
    $query="update darlelJobber_jobs set customerId='$job_for',cannot_delay='$cannot_delay',required_811='$required_811',title='$title',propertyId='$propertyId',internal_notes='$internal_notes',
    total='$final_total' where id='$jobId'";
    runQuery($query);
    
    //updating the line items started
    runQuery("delete from darlelJobber_quote_details where quoteId='$quoteId' && service!='Labor Fees'");
    $service_inp=$_POST['service'];
    $qty_inp=$_POST['qty'];
    $unit_price_inp=$_POST['unit_price'];
    $total_inp=$_POST['total'];
    $description_inp=$_POST['description'];
    $helperFile_inp=$_POST['helperFile'];
    $type_inp=$_POST['type'];
    $serviceId_inp=$_POST['serviceId'];
    $optionalStatus_inp=$_POST['optionalStatus'];
    $optionalApproveStatus_inp=$_POST['optionalApproveStatus'];
    $target_dir = "./servicesImages/";
    
    for($i=0;$i<count($service_inp);$i++){
        
        $service=$service_inp[$i];
        $qty=$qty_inp[$i];
        $service=clear($service_inp[$i]);
        $unit_price=round($unit_price_inp[$i],2);
        $total=round($total_inp[$i], 2);
        $description=clear($description_inp[$i]);
        $helperFile=clear($helperFile_inp[$i]);
        $type=clear($type_inp[$i]);
        $optionalStatus=clear($optionalStatus_inp[$i]);
        $optionalApproveStatus=clear($optionalApproveStatus_inp[$i]);
        $serviceId=clear($serviceId_inp[$i]);
        
        if($type=="Labor Fees"){
            $unit_price=$unit_price_inp[$i];
            $qty=$qty_inp[$i];
            $total=round($qty*$unit_price,2);
            runQuery("update darlelJobber_quote_details set unit_price='$unit_price',qty='$qty',total='$total' where quoteId='$quoteId' and service='Labor Fees'");
            continue;
        }
        
        if(!empty( $_FILES[ 'images' ][ 'error' ][ $i ] ) )
            $image=$helperFile;
        else{
            $image = clear($_FILES[ 'images' ]['name'][$i]);
            $tmpName = $_FILES[ 'images' ][ 'tmp_name' ][ $i ];
            $target_file = $target_dir.$image;
            move_uploaded_file( $tmpName, $target_file ); 
        }
        $random=random();
        $query="insert into darlelJobber_quote_details set id='$random',serviceId='$serviceId',quoteId='$quoteId',optionalApproveStatus='$optionalApproveStatus',
        optionalStatus='$optionalStatus',service='$service',type='$type',qty='$qty',unit_price='$unit_price',total='$total',description='$description',image='$image'";
        runQuery($query);
    }
    updateQuote($quoteId);
    //updating the line items finished
    
    if(isset($_POST['convertInvoice'])){
        $invoiceId=random();
        $invoice_number=getRow($con,"select invoice_number from darlelJobber_invoices order by timeAdded desc")['invoice_number']+1;
        
        //getting propertyId from jobs
        $propertyId=$jobDeets['propertyId'];
        
        $query="update darlelJobber_jobs set convertStatus='Converted',invoiceId='$invoiceId' where id='$jobId'";
        runQuery($query);
        
        $quoteId=$jobDeets['quoteId'];
        $requestId=$jobDeets['requestId'];
        
        
        //update notes section means that ke jo jobs ke notes the woh ab job ke invoices bhi hoonge
        $query="update darlelJobber_notes set invoiceId='$invoiceId' where jobId='$jobId'";
        runQuery($query);
    
        //update request and quote with this invoiceId
        runQuery("update darlelJobber_requests set invoiceId='$invoiceId' where id='$requestId'");
        runQuery("update darlelJobber_quotes set invoiceId='$invoiceId' where id='$quoteId'");
        
        $issued_date=time();
        $startTime=$issued_date;
        $finishTime=$startTime+259200;
        $timePeriodStatus="start";
        $expiryStatus="Valid";
        
        $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
        $discount=$quoteDeets['discount'];
        $discountType=$quoteDeets['discountType'];
        
        $query="insert into darlelJobber_invoices set id='$invoiceId',propertyId='$propertyId',issued_date='$issued_date',invoice_number='$invoice_number',
        customerId='$job_for',subject='$title',timeAdded='$timeAdded',subtotal='$final_total',discount='$discount',discountType='$discountType',total='$final_total',
        jobId='$jobId',quoteId='$quoteId',requestId='$requestId',startTime='$startTime',finishTime='$finishTime',timePeriodStatus='$timePeriodStatus',expiryStatus='$expiryStatus'";
        runQuery($query);
        
        header("Location:./createInvoice.php?entryId=$invoiceId&updateInvoice=1");
        exit();
    }
    else
        header("Location:?entryId=$jobId");
}

require("./notes/notes.php");

$visits=getAll($con,"select * from darlelJobber_visits where jobId='$jobId' order by timeAdded desc");
$shopOrders=getAll($con,"select * from darlelJobber_shop_orders where jobId='$jobId' order by timeAdded desc");


if(isset($_GET['delete-crew-entry'])){
    $id=$_GET['delete-crew-entry'];
    runQuery("delete from darlelJobber_installation where id='$id'");
    runQuery("delete from darlelJobber_installation_images where installationId='$id'");
    header("Location:?entryId=$entryId&m=Installation entry has been deleted successfully");
}

/*add visit task code started*/

if(isset($_GET['createVisitTask'])){
    $visitId=clear($_GET['visitId']);
    $taskId=random();
    
    $visitDeets=getRow($con,"select * from darlelJobber_visits where id='$visitId'");
    $end_date=$visitDeets['end_date'];
    //updating visit with the taskId
    runQuery("update darlelJobber_visits set taskId='$taskId' where id='$visitId'");
    
    //creating visit task
    $title="Visit Task";$description="Visit Task";$label="Visit";$completionDate=$end_date;
    
    //inserting task
    $query="insert into darlelJobber_tasks set id='$taskId',jobId='$jobId',visitId='$visitId',title='$title',description='$description',label='$label',
    completionDate='$completionDate',timeAdded='$timeAdded',addedBy='$session_id'";
    runQuery($query);
    
    //inserting visit team
    $visitTeam=getAll($con,"select userId from darlelJobber_teams where visitId='$visitId'");
    foreach($visitTeam as $row){
        $userId=$row['userId'];
    
        $title="Assigned To a Visit Task";$description="You have been added as a member in a visit task";$url=$projectUrl."detailedTaskView.php?taskId=$taskId";
        setNotification($title,$description,$userId,$url);
        
        $random=random();
        $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
        runQuery($query);
    }
    header("Location:./detailedTaskView.php?taskId=$taskId");
}
/*add visit task code ending*/


/*visit php code*/
if(isset($_POST['addVisit'])){
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $type=clear($_POST['type']);
    $start_date=strtotime(clear($_POST['start_date']));
    $start_time=$_POST['start_time'];
    $end_date=strtotime(clear($_POST['end_date']));
    $end_time=$_POST['end_time'];
    $actionId=clear($_POST['actionId']);
    
    if($actionId=="" && $start_date==$end_date){
        $visitId=random();
        $taskId="None";
        $query="insert into darlelJobber_visits set id='$visitId',title='$title',description='$description',start_date='$start_date',start_time='$start_time'
        ,end_date='$end_date',end_time='$end_time',addedBy='$session_id',timeAdded='$timeAdded',jobId='$jobId',type='$type'";
        runQuery($query);
    }
    else{
        $visitId=$actionId;
        $query="update darlelJobber_visits set title='$title',description='$description',start_date='$start_date',start_time='$start_time'
        ,end_date='$start_date',end_time='$end_time',jobId='$jobId',type='$type' where id='$actionId'";
        runQuery($query);
        
        $taskId=getRow($con,"select * from darlelJobber_visits where id='$visitId'")['taskId'];
    }
    
    //create multiple visits if start and end date are not same and assign the team here then redirect
    if($start_date!=$end_date){
        $visitIdArr=[];
        $seconds_in_a_day = 24 * 60 * 60;
        $time_difference = $end_date - $start_date;
        $num_days = floor($time_difference / $seconds_in_a_day);
        
        for ($i = 0; $i <= $num_days; $i++) {
            if($i==0 && $actionId!="")//if the visit is being edited then do not insert the first iteration
                continue;
            $visitIdTemp=random();
            $visitIdArr[]=$visitIdTemp;
            $current_timestamp = $start_date + ($i * $seconds_in_a_day);
            
            $query="insert into darlelJobber_visits set id='$visitIdTemp',title='$title',description='$description',start_date='$current_timestamp',start_time='$start_time'
            ,end_date='$current_timestamp',end_time='$end_time',addedBy='$session_id',timeAdded='$timeAdded',jobId='$jobId',type='$type'";
            runQuery($query);
        }
        
        //assigning team to each visit
        foreach($visitIdArr as $visitIdTemp){
            $newteam=$_POST['team'];
            foreach($newteam as $userId){
                $random=random();
                $query="insert into darlelJobber_teams set id='$random',visitId='$visitIdTemp',jobId='$jobId',userId='$userId',timeAdded='$timeAdded'";
                runQuery($query);
               
                $title="Assigned To a Visit";$description="You have been added as a member in a visit";$url=$projectUrl."createJob.php?entryId=$jobId&view=1";
                setNotification($title,$description,$userId,$url);
            }
        }
        if($actionId==""){//redirect only when the visit creation modal is triggered
            header("Location:?entryId=$entryId&m=Visits have been created successfully");
            exit();
        }
    }
    
    if($taskId=="None")
        $taskId="random";
    
    //adding team members for this request
    $newteam=$_POST['team'];
    $oldteamfetch=getAll($con,"select userId from darlelJobber_teams where visitId='$visitId'");
    
    $oldteam=[];
    $oldteam = array_map(function($item) {
        return $item["userId"];
    }, $oldteamfetch);
    
    //adding team members started
    $allWorkers=getAll($con,"select * from darlelJobber_users where role!='Client'");
    foreach($allWorkers as $row){
        $userId=$row['id'];
        if((in_array($userId,$newteam)) && (!in_array($userId,$oldteam))){
            //adding this person in the visit team
            $random=random();
            $query="insert into darlelJobber_teams set id='$random',visitId='$visitId',jobId='$jobId',userId='$userId',timeAdded='$timeAdded'";
            runQuery($query);
            
            //adding this person in the visit task team
            if($taskId!="random"){
                $random=random();
                $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
                runQuery($query);
                
                $title="Assigned To a Visit Task";$description="You have been added as a member in a visit task";$url=$projectUrl."detailedTaskView.php?taskId=$taskId";
                setNotification($title,$description,$userId,$url);
            }
            
            $title="Assigned To a Visit";$description="You have been added as a member in a visit";$url=$projectUrl."createJob.php?entryId=$jobId&view=1";
            setNotification($title,$description,$userId,$url);
            
        }
        else if((!in_array($userId,$newteam)) && (in_array($userId,$oldteam))){
            $query="delete from darlelJobber_teams where userId='$userId' && (visitId='$visitId' or taskId='$taskId' )";
            runQuery($query);
        }
    }
    //adding team members finished
    header("Location:?entryId=$entryId&m=Visits data has been updated successfully");
}
/*visit php code ending*/

/*shop php code*/ 
if(isset($_POST['addShopOrder'])){
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $type=clear($_POST['type']);
    $start_date=strtotime(clear($_POST['start_date']));
    $start_time=$_POST['start_time'];
    $end_date=strtotime(clear($_POST['end_date']));
    $end_time=$_POST['end_time'];
    $actionId=clear($_POST['actionId']);
    $jobId=$_GET['entryId'];
    $image=clear(htmlspecialchars( basename( $_FILES["image"]["name"])));
    
    $start_time = ($start_time=="") ? "14:30" : $start_time;
    $end_time = ($end_time=="") ? "14:30" : $end_time;
    
    if($actionId==""){
        $shopOrderId=random();
        $taskId=random();
        $query="insert into darlelJobber_shop_orders set id='$shopOrderId',title='$title',description='$description',start_date='$start_date',start_time='$start_time'
        ,end_date='$end_date',end_time='$end_time',addedBy='$session_id',timeAdded='$timeAdded',jobId='$jobId',scheduleType='$type'";
        runQuery($query);
        $taskId="None";
        
        $notesId=random();
        $title=clear($_POST['title']);
        $query="insert into darlelJobber_notes set id='$notesId',title='$title',description='Shop order images',addedBy='$session_id',timeAdded='$timeAdded',jobId='$jobId',shopOrderId='$shopOrderId'";
        runQuery($query);
    }
    else{
        $shopOrderId=$actionId;
        $query="update darlelJobber_shop_orders set title='$title',description='$description',start_date='$start_date',start_time='$start_time'
        ,end_date='$end_date',end_time='$end_time',jobId='$jobId',scheduleType='$type' where id='$shopOrderId'";
        runQuery($query);
        $taskId=getRow($con,"select * from darlelJobber_shop_orders where id='$shopOrderId'")['taskId'];
        $notesId=getRow($con,"select * from darlelJobber_notes where shopOrderId='$shopOrderId'")['id'];
    }
    if($taskId=="None")
        $taskId="random";
    
    
    //adding team members for this shop order
    $newteam=$_POST['team'];
    $oldteamfetch=getAll($con,"select userId from darlelJobber_teams where shopOrderId='$shopOrderId'");
    $oldteam=[];
    $oldteam = array_map(function($item) {
        return $item["userId"];
    }, $oldteamfetch);
    
    //adding team members started
    $allWorkers=getAll($con,"select * from darlelJobber_users where role!='Client'");
    foreach($allWorkers as $row){
        $userId=$row['id'];
        if((in_array($userId,$newteam)) && (!in_array($userId,$oldteam))){
            //adding this person in the task team
            if($taskId!="random"){
                $random=random();
                $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
                runQuery($query);
                
                $title="Assigned To a Shop Task";
                $description="You have been added as a member in a task";
                $url=$projectUrl."detailedTaskView.php?taskId=$taskId";
                setNotification($title,$description,$userId,$url);
            }
            
            //adding this person in the shop team
            $random=random();
            $query="insert into darlelJobber_teams set id='$random',shopOrderId='$shopOrderId',userId='$userId',timeAdded='$timeAdded'";
            runQuery($query);
            
            $title="Assigned To a Shop Order";$description="You have been added as a member in a shop order";
            $url=$projectUrl."createJob.php?entryId=$jobId&view=1";
            setNotification($title,$description,$userId,$url);
        }
        else if((!in_array($userId,$newteam)) && (in_array($userId,$oldteam))){
            $query="delete from darlelJobber_teams where userId='$userId' &&  ( taskId='$taskId' or shopOrderId='$shopOrderId' )";
            runQuery($query);
        }
    }
    //adding team members finished
    
    //multiple file upload
    $total = count($_FILES['fileToUpload']['name']);
    
    for( $i=0 ; $i < $total ; $i++ ){
      $tmpFilePath = $_FILES['fileToUpload']['tmp_name'][$i];
      if ($tmpFilePath != ""){
        $newFilePath = "./uploads/" . $_FILES['fileToUpload']['name'][$i];
        if(move_uploaded_file($tmpFilePath, $newFilePath)) {
            $image=$_FILES['fileToUpload']['name'][$i];
            $sameEntryId=random();
            //adding in shop images
            $query="insert into darlelJobber_shop_images set id='$sameEntryId',shopOrderId='$shopOrderId',title='$image',image='$image',addedBy='$session_id'";
            runQuery($query);
            
            if($taskId!="random"){
                //adding in task images
                $query="insert into darlelJobber_task_images set id='$sameEntryId',taskId='$taskId',image='$image',timeAdded='$timeAdded',addedBy='$session_id'";
                runQuery($query);
            }
            
            //addding in notes
            $random=random();
            $query="insert into darlelJobber_notes_images set id='$random',notesId='$notesId',image='$image'";
            runQuery($query);
        }
      }
    }
    header("Location:?entryId=$jobId&m=Shop Order data has been updated successfully");
}

/*add shop task started*/
if(isset($_GET['createShopTask'])){
    
    $shopOrderId=clear($_GET['shopOrderId']);
    $taskId=random();
    $shopOrderDeets=getRow($con,"select * from darlelJobber_shop_orders where id='$shopOrderId'");
    //creating task for this shop order automatically
    $title="Shop Task";$description="Shop Task";$label="Shop";
    $completionDate=$shopOrderDeets['end_date'];
    
    //inserting task for this shop order
    $query="insert into darlelJobber_tasks set id='$taskId',shopOrderId='$shopOrderId',title='$title',description='$description',label='$label',
    completionDate='$completionDate',timeAdded='$timeAdded',addedBy='$session_id'";
    runQuery($query);
    
    //updating taskId of the shop order with the taskId
    runQuery("update darlelJobber_shop_orders set taskId='$taskId' where id='$shopOrderId'");
    
    //inserting shop team in task team
    $shopOrderTeam=getAll($con,"select userId from darlelJobber_teams where shopOrderId='$shopOrderId'");
    foreach($shopOrderTeam as $row){
        $userId=$row['userId'];
    
        $title="Assigned To a Shop Task";$description="You have been added as a member in a shop task";$url=$projectUrl."detailedTaskView.php?taskId=$taskId";
        setNotification($title,$description,$userId,$url);
        
        $random=random();
        $query="insert into darlelJobber_teams set id='$random',taskId='$taskId',userId='$userId',timeAdded='$timeAdded'";
        runQuery($query);
    }
    
    //inserting shop images in task images
    $shopImages=getAll($con,"select * from darlelJobber_shop_images where shopOrderId='$shopOrderId'");
    foreach($shopImages as $row){
        $image=$row['image'];
        $random=random();
        $query="insert into darlelJobber_task_images set id='$random',taskId='$taskId',image='$image',timeAdded='$timeAdded',addedBy='$session_id'";
        runQuery($query);
    }
    header("Location:./detailedTaskView.php?taskId=$taskId");
}
/*add shop task code ending*/


/*shop php code ending*/
if(isset($_GET['delete-visit'])){
    $id=clear($_GET['delete-visit']);
    runQuery("delete from darlelJobber_visits where id='$id'");
    runQuery("delete from darlelJobber_tasks where visitId='$id'");
    header("Location:?entryId=$entryId&m=Visit Deleted");
}
if(isset($_GET['delete-shop-order'])){
    $id=$_GET['delete-shop-order'];
    $query="delete from darlelJobber_shop_orders where id='$id'";
    runQuery($query);
    $query="delete from darlelJobber_teams where shopOrderId='$id'";
    runQuery($query);
    header("Location:?entryId=$entryId&m=Shop Order Deleted");
}

if(isset($_GET['complete-visit'])){
    $visitId=clear($_GET['complete-visit']);
    $query="update darlelJobber_visits set completionStatus='Completed' where id='$visitId'";
    runQuery($query);
    $taskId=getRow($con,"select * from darlelJobber_visits where id='$visitId'")['taskId'];
    if($taskId!="None")
        runQuery("update darlelJobber_tasks set status='Completed' where id='$taskId'");
    header("Location:?entryId=$jobId&m=Visit has been marked as completed successfully");
}

//remove shop image
if(isset($_GET['removeShopImage'])){
    $id=clear($_GET['removeShopImage']);
    runQuery("delete from darlelJobber_shop_images where id='$id'");
    runQuery("delete from darlelJobber_task_images where id='$id'");
    header("Location:?entryId=$jobId&m=Image Deleted");
}

if(isset($_GET['completedShopOrder'])){
    $shopOrderId=clear($_GET['completedShopOrder']);
    $query="update darlelJobber_shop_orders set status='Completed' where id='$shopOrderId'";
    runQuery($query);
    header("Location:?entryId=$jobId&m=Shop Order completed successfully");
}


/*notifying client of the visit schedule started*/
if(isset($_GET['notifyClient'])){
    $visitId=clear($_GET['notifyClient']);
    $customerId=$jobDeets['customerId'];
    $customerDeets=getRow($con,"select * from darlelJobber_users where id='$customerId'");
    $customerName=$customerDeets['first_name']." ".$customerDeets['last_name'];
    $visitDeets=getRow($con,"select * from darlelJobber_visits where id='$visitId'");
    $installDate=date("d M Y",$visitDeets['start_date'])." ".date("h:i A",strtotime($visitDeets['start_time']));
    
    $subject="Confirmation of Your Fence Installation Appointment";
    $emailDescription="Dear $customerName,
    <br><br>We are excited to inform you that your fence installation project with Vilo Fence is all set and ready to begin. We truly appreciate your trust in us and the opportunity to serve you.
    <br><br>Your installation date is scheduled for [$installDate - Approximate]. Our team is eager to get started and create a beautiful fence that meets your expectations.
    <br><br>Please note that the installation date is approximate, as weather conditions and other unforeseen circumstances can impact our schedule. However, rest assured that our office will call you the day before your installation to reconfirm the exact time of arrival.
    <br><br>In the meantime, our estimator will be giving you a call shortly to go over all the details of your contract, ensuring that everything aligns perfectly with your preferences.
    <br><br>We understand that communication is vital, and we want to ensure that your experience with Vilo Fence is seamless from start to finish. If you have any questions or concerns at any point, please feel free to reach out to Leo, our Customer Success Manager, at customerservice@vilofence.com or call our customer service team at 813-270-5746.
    <br><br>Once again, we are grateful for choosing Vilo Fence for your fencing needs. We are dedicated to delivering top-notch service and an exceptional final result.
    <br><br>We look forward to providing you with a stunning fence that enhances your property and exceeds your expectations.
    <br><br>Best Regards,
    <br><br>Leo
    <br><br>Client success manager
    <br><br>Vilo Fence Company";
    $emails=explode("*",$customerDeets['email']);
    foreach($emails as $email)
        sendEmailNotification_mailjet($subject, $emailDescription, $email);
            
    $smsDescription="Hi $customerName,
    Your fence installation with Vilo Fence is confirmed for [$installDate - Approximate]. Our team is excited to start!
    Remember, the date is approximate, and our office will call you the day before to confirm the exact time.
    The estimator will call shortly to go over the contract details.
    For any questions, reach us at 813-270-5746 or customerservice@vilofence.com.
    Thank you,
    Leo";
    $phones=explode("*",$customerDeets['phone']);
    foreach($phones as $phone)
        sendansms($phone,$smsDescription);
        
    header("Location:?entryId=$jobId&m=Client has been notified via email and sms successfully");
}
/*notifying client of the visit schedule finished*/
?>
<html lang="en">
	<head>
		<?require("./includes/views/head.php");?>
	    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <link href="includes/autocompletecss.css" rel="stylesheet" type="text/css"/>
    </head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<!--begin::Container-->
							<div id="kt_content_container" class="container-xxl">
								
								<?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <span class="svg-icon svg-icon-2hx svg-icon-light me-4 mb-5 mb-sm-0"></span>
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
								<form action="" method="post" enctype="multipart/form-data" id="jobForm">
									
									<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
										<div class="row">
										    <div class="col-12">
        										<ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-bold mb-n2">
        											<?if($jobDeets['requestId']!="None" && $permission['view_requests'] ){?>
        											<li class="nav-item">
        												<a href="createRequest.php?entryId=<?echo $jobDeets['requestId']?>&view=1" class="nav-link text-active-primary pb-4 ">Request</a>
        											</li>
        											<?}?>
        											<?if($permission['view_quotes']){?>
        											<li class="nav-item">
        												<a href="createQuote.php?entryId=<?echo $jobDeets['quoteId']?>&view=1" class="nav-link text-active-primary pb-4 ">Quote</a>
        											</li>
        											<?}?>
        											<li class="nav-item">
        												<a href="createJob.php?entryId=<?echo $jobId?>&view=1" class="nav-link text-active-primary pb-4 active">Job</a>
        											</li>
        											<?if($jobDeets['invoiceId']!="None" && $permission['view_invoices']){?>
        											<li class="nav-item">
        												<a href="createInvoice.php?entryId=<?echo $jobDeets['invoiceId']?>&view=1" class="nav-link text-active-primary pb-4 ">Invoice</a>
        											</li>
        											<?}?>
        											<?if($permission['view_client']){?>
        											<li class="nav-item">
        												<a href="view_client.php?id=<?echo $jobDeets['customerId']?>" class="nav-link text-active-primary pb-4 ">View Client</a>
        											</li>
        											<?}?>
        										</ul>
    										</div>
    									</div>
										<div class="tab-content">
											<div class="tab-pane fade active show" role="tab-panel" style="margin-bottom: 20px;">
												<div class="d-flex flex-column gap-7 gap-lg-10">
													<div class="card card-flush py-4">
														<div class="card-header">
															<div class="card-title">
																<h2><?echo "# ".$jobDeets['job_number']?> Job For
											                        <a href="view_client.php?id=<?echo $jobDeets['customerId']?>">
											                            <p style="display: inline;color: #73141d;" id="clientName">
											                                <?
											                                if((isset($_GET['entryId'])) || (isset($_GET['customerId']))){
										                                        if($userDeets['showCompanyName']=="Yes")
                                    								                echo $userDeets['company_name']." (".$userDeets['first_name']." ".$userDeets['last_name'].")";
                                                                                else
											                                        echo $userDeets['first_name']." ".$userDeets['last_name'];
                                    									    } 
											                                else
											                                    echo "Client Name";?>
											                            </p>
										                            </a>
											                    </h2>
											                    <input type="text" name="job_for" value="<?echo $jobDeets['customerId']?>" hidden>
															</div>
															<div class="card-toolbar">
															    <?if((isset($_GET['start'])) && (isset($_GET['end']))){
                                                                    $start_time = date("h:i A",strtotime($_GET['start']));
                                                                    $end_time = date("h:i A",strtotime($_GET['end']));?>
															    <a class="btn btn-primary btn-sm">Time Slot :  <?echo $start_time."---".$end_time?></a>
															    <?}?>
															    
															    <?if($permission['edit_jobs'] && $view){?>
                        										<a class="btn btn-warning btn-sm" style="margin-right:10px" href="?entryId=<?echo $entryId?>">Edit Job</a>
                    									        <?}
                    									        if($edit){?>
                    									        <a class="btn btn-warning btn-sm" href="tasks.php?jobId=<?echo $jobId?>&userId=<?echo $session_id?>&error=1">Create Job Error</a>
                    									        <?}?>
                    									    </div>
														</div>
														<div class="card-body pt-0">
														    
															<div class="row mb-10">
															    <div class="col-xs-12 col-md-8">
														        	<label class="required form-label">Job Title</label>
    																<input type="text" name="title" class="form-control mb-2" value="<?echo $jobDeets['title']?>" placeholder="Job Title" >
    														    </div>
        														<div class="col-xs-6 col-md-2 mt-10 text-center">
        														    <input name="required_811" class="form-check-input" type="checkbox" <?if($jobDeets['required_811']=="Yes"){echo "checked";}?> />
                                                                    <label class="form-check-label">Required 811 ? </label> 
        														</div>
        														<div class="col-xs-6 col-md-2 mt-10 text-center">
        														    <input name="cannot_delay" class="form-check-input" type="checkbox" <?if($jobDeets['cannot_delay']=="Yes"){echo "checked";}?> />
                                                                    <label class="form-check-label">Cannot Delay ? </label> 
        														</div>
															</div>
															<div class="row">
														        <div class="col-6">
														            <h4>Property Address</h4>
														            <p id="street1"><?echo $propertyDeets['street1']?></p>
														            <p id="street2"><?echo $propertyDeets['street2']?></p>
														            <p id="city"><?echo $propertyDeets['city']?></p>
														            <p id="state"><?echo $propertyDeets['state']?></p>
														            <p id="zip_code"><?echo $propertyDeets['zip_code']?></p>
														            <p id="country"><?echo $propertyDeets['country']?></p>
														            <input type="text" name="propertyId" value="<?echo $jobDeets['propertyId']?>" hidden>
														            
														            <?//google map address button when job is viewed
														            if($view){
														                $zipCode = ($propertyDeets['zip_code']!="") ? " (Zip Code : ".$propertyDeets['zip_code'].")" : "";
                                                                        $googleMapAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'];?>
										                            <a target="_blank" class="btn btn-warning btn-sm" href="https://www.google.com/maps/search/?api=1&query=<?echo $googleMapAddress?>">View Address</a>
										                            <?}?>
														        </div>
														        <?if($permission['view_client']){?>
														        <div class="col-6">
														            <h4>Contact Details</h4>
														            <div id="contactDetails">
														                <?$userEmails=explode("*",$userDeets['email']);
                        											    $userPhones=explode("*",$userDeets['phone']);
                        											    foreach($userPhones as $row)
                        											        echo "<p>".$row."</p>";
                        											    foreach($userEmails as $row)
                    											            echo "<p>".$row."</p>";?>
                        											</div>
														        </div>
														        <?}?>
														    </div>
														    
													        <?if($session_role!="Installation Crew"){?>
														    <!--visits section-->
													        <div class="row" style="margin-top: 30px;">
    														        <div class="col-12">
        														        <div class="card shadow-sm">
                                                                            <div class="card-header">
                                                                                <h3 class="card-title">Visits</h3>
                                                                                <div class="card-toolbar">
                                                                                    <?if(!$view){?>
                                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_visit" class="btn btn-primary btn-sm">Add Visit</a>
                                                                                    <?}?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-rounded table-striped border gs-7" >
                                        									        <thead>
                                        									            <tr>
                                        									                <th>Title</th>
                                        									                <th>Scheduled Date</th>
                                        									                <th>Start - End</th>
                                        									                <th>Completion Status</th>
                                        									                <th>Team</th>
                                        									                <?if(!$view){?>
                                                                                            <th>Actions</th>
                                        									                <?}?>
                                        									            </tr>
                                        									        </thead>
                                        									        <tbody>
                                        									            <?foreach($visits as $row){
                                        									            $visitId=$row['id'];
                                                                                        $tempDate=$row['end_date']+172800;
                                                                                        if((time() > $tempDate) && ($row['completionStatus']=="Not Completed") && ($row['type']=="Schedule Now")){
                                                                                            runQuery("update darlelJobber_visits set visitStatus='Late' where id='$visitId'");
                                                                                        }
                                        									            ?>
                                        									            <tr>
                                        									                <td><?echo $row['title']?></td>
                                        									                <td><?if($row['type']=="Schedule Later"){echo "Schedule Later";}else{echo date("d M y",$row['start_date']);}?></td>
                                        									                <td><?if($row['type']=="Schedule Later"){echo "Schedule Later";}else{echo date("h:i A",strtotime($row['start_time']))." -- ".date("h:i A",strtotime($row['end_time']));}?></td>
                                        									                <td><?echo $row['completionStatus']?></td>
                                        									                <td id="<?echo $row['id']?>">
                                        									                    <!--team display-->
                                        									                    <?$visitId=$row['id'];
                                        									                    $teams=getAll($con,"select * from darlelJobber_teams where visitId='$visitId'");
            														                            $random=random();
            														                            $assignedTeam=[];
                                                                                                foreach($teams as $nrow){
                                                                                                $assignedTeam[]=$nrow['userId'];?>
            														                            <span class="badge badge-success">
            														                                <?echo $idToInfo[$nrow['userId']]['name']." (".$idToInfo[$nrow['userId']]['role'].")"?>
            														                            </span>
            														                            <?}?>
                                        									                </td>
                                        									                <?if(!$view){?>
                                                                                            <td>
                                                                                                <div class="btn-group">
                                        									                    <?if($row['taskId']!="None"){
                                        									                        $taskId=$row['taskId'];
                                        									                        $taskHref="detailedTaskView.php?taskId=$taskId";
                                        									                    }
                                        									                    else{
                                        									                        $visitId=$row['id'];
                                        									                        $taskHref="?entryId=$jobId&visitId=$visitId&createVisitTask=1";
                                        									                    }?>
                                        									                    <a class="btn btn-success btn-sm" href="<?echo $taskHref?>">View Reminder</a>
                                        									                    
                                        									                    <?if($row['completionStatus']!="Completed"){?>
                                        									                    <a class="btn btn-warning btn-sm" href="?entryId=<?echo $jobId?>&notifyClient=<?echo $row['id']?>">Notify Client</a>
                                        									                    <a class="btn btn-success btn-sm" href="?entryId=<?echo $jobId?>&complete-visit=<?echo $row['id']?>">Complete Visit</a>
                                        									                    <?}?>
                                        									                    <?$row['start_date']=date("Y-m-d",$row['start_date']);
                                        									                    $row['end_date']=date("Y-m-d",$row['end_date']);
                                        									                    $row['assignedTeam']=$assignedTeam;
                                        									                    ?>
                                        									                    <a class="btn btn-warning btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#add_visit" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
                                        									                    <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-visit=<?echo $row['id']?>&entryId=<?echo $entryId?>">Delete</a>
                                        									                    </div>
                                        									                </td>
                                        									                <?}?>
                                        									            </tr>
                                        									            <?}?>
                                        									        </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    </div>
                                                                </div>
														    <!--visits section ending-->
														    <?}?>
														    
														    
														    <!--shop order section-->
													        <div id="shopTable" style="margin-top: 30px;" class="row">
    														        <div class="col-12">
        														        <div class="card shadow-sm">
                                                                            <div class="card-header">
                                                                                <h3 class="card-title">Shop</h3>
                                                                                <div class="card-toolbar">
                                                                                    <?if(!$view){?>
                                                                                    <a id="add_shop_order_btn" href="#" data-bs-toggle="modal" data-bs-target="#add_shop_order" class="btn btn-primary btn-sm">Add Order</a>
                                                                                    <?}?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="card-body">
                                                                                <div class="table-responsive">
                                                                                    <table class="table table-rounded table-striped border gs-7">
                                            									        <thead>
                                            									            <tr>
                                            									                <th>Title</th>
                                            									                <th>Status</th>
                                            									                <th>Scheduled Date</th>
                                            									                <th>Start-End</th>
                                            									                <th>Team</th>
                                            									                <th>Images</th>
                                            									                <?if(!$view){?>
                                                                                                <th>Actions</th>
                                            									                <?}?>
                                            									            </tr>
                                            									        </thead>
                                            									        <tbody>
                                            									            <?
                                            									            foreach($shopOrders as $row){
                                            									            $shopOrderId=$row['id'];?>
                                            									            <tr>
                                            									                <td><?echo $row['title']?></td>
                                            									                <td><?echo $row['status']?></td>
                                            									                <td><?if($row['scheduleType']=="Schedule Later"){echo "Schedule Later";}else{echo date("d M y",$row['start_date']);}?></td>
                                            									                <td><?if($row['scheduleType']=="Schedule Later"){echo "Schedule Later";}else{echo date("h:i A",$row['start_time'])." -- ".date("h:i A",strtotime($row['end_time']));}?></td>
                                            									                <td id="<?echo $row['id']?>">
                                            									                    <?$assignedTeam=[];
                                            									                    $teams=getAll($con,"select * from darlelJobber_teams where shopOrderId='$shopOrderId'");
                														                            foreach($teams as $nrow){
                														                            $assignedTeam[]=$nrow['userId'];?>
                														                            <span class="badge badge-success">
                														                                <?echo $idToInfo[$nrow['userId']]['name']." (".$idToInfo[$nrow['userId']]['role'].")"?>
                														                            </span>
                                                                            					    <?}?>
                                            									                </td>
                                            									                <td>
                                            									                    <?$shopImages=getAll($con,"select * from darlelJobber_shop_images where shopOrderId='$shopOrderId'");
                														                            foreach($shopImages as $nrow){?>
                														                            <p class="btn btn-light-success btn-sm" style="margin-right:3px;">
                														                                <a class="gallery" href="uploads/<?echo $nrow['image']?>">
        														                                            <img class="example-image" style="max-height: 2.5rem;" src="uploads/<?echo $nrow['image']?>"/>    
                                                                                                        </a>
                														                                <?if(!$view){?>
                														                                <a onclick="return confirm('Are you sure you want to delete this image?');" href="?entryId=<?echo $jobId?>&removeShopImage=<?echo $nrow['id']?>" style="margin-left: 10px;color: red;">X</a>
                                                                            					        <?}?>
                                                                            					    </p>
                                                                            					    <?}?>
                                            									                </td>
                                            									                <?if(!$view){?>
                                                                                                <td>
                                                                                                    <div class="btn-group">
                                                                                					<?$row['start_date']=date("Y-m-d",$row['start_date']);
                                            									                    $row['end_date']=date("Y-m-d",$row['end_date']);
                                            									                    $row['assignedTeam']=$assignedTeam;?>
                                            									                    
                                            									                    <?if($row['taskId']!="None"){
                                            									                        $taskId=$row['taskId'];
                                            									                        $taskHref="detailedTaskView.php?taskId=$taskId";
                                            									                    }
                                            									                    else{
                                            									                        $shopOrderId=$row['id'];
                                            									                        $taskHref="?shopOrderId=$shopOrderId&createShopTask=1&entryId=$jobId";
                                            									                    }?>
                                            									                    <a class="btn btn-success btn-sm" href="<?echo $taskHref?>">View Reminder</a>
                                        					                                        <?if($row['status']!="Completed"){?>
                                            									                    <a class="btn btn-success btn-sm" href="?entryId=<?echo $entryId?>&completedShopOrder=<?echo $row['id']?>" >Mark As Completed</a>
                                            									                    <?}?>
                                            									                    <a class="btn btn-warning btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#add_shop_order" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>'>Edit</a>
                                            									                    <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-shop-order=<?echo $row['id']?>&entryId=<?echo $entryId?>">Delete</a>
                                            									                    </div>
                                            									                </td>
                                            									                <?}?>
                                            									            </tr>
                                            									            <?}?>
                                            									        </tbody>
                                            									</table>
                                                                                </div>
                                                                            </div>
                                                                    </div>
                                                                    </div>
                                                                </div>
														    <!--shop order section ending-->
														        
														    </div>
														    <div class="card-body pt-0">
														        <div class="table-responsive">
                                                                    <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                        <thead class="text-center">
                                                                            <tr>
                                                                                <th >PRODUCT / SERVICE</th>
                                                                                <th >QTY</th>
                                                                                <th >Image</th>
                                                                                <?if($session_role!="Installation Crew"){?>
                                                                                <th >UNIT PRICE</th>
                                                                                <th >TOTAL</th>
                                                                                <th>
                                                                                    <a style="white-space: nowrap;" onclick="addRow()" class="btn btn-primary btn-sm">
                                                                                        <i style="font-size: x-large;" class="las la-plus"></i>
                                                                                        Line Item
                                                                                    </a>
                                                                                </th>
                                                                                <?}?>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="quote_section">
                                                                            <?
													                        $query="select * from darlelJobber_quote_details where quoteId='$quoteId' order by entryNo asc";
                													        $quoteDeetsDetailed=getAll($con,$query);
                													        foreach($quoteDeetsDetailed as $nrow){$random=random();?>
                													        <tr id="<?echo $random?>" class="<?echo $random?>" 
                													        <?if($nrow['optionalStatus']=="Yes" && $nrow['optionalApproveStatus']=="No"){echo "style='display:none'";}?>>
                                                                                <td >
                                                                                    <input name="serviceId[]" value="<?echo $nrow['serviceId']?>" hidden>
    	                                                                            <input name="optionalApproveStatus[]" value="<?echo $nrow['optionalApproveStatus']?>" hidden>
    	                                                                            <input name="optionalStatus[]" value="<?echo $nrow['optionalStatus']?>" hidden>
    	                                                                            <input onfocusout="fillPerUnitCost('<?echo $random?>')" type="text" class="form-control" value="<?echo htmlspecialchars($nrow['service'])?>"  name="service[]" style="width: 550;">
        														                    <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"><?echo htmlspecialchars($nrow['description'])?></textarea>
        														                </td>
        														                <td >
        														                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="<?echo $nrow['qty']?>" style="width: 150;">
                														            <?
                														            $text = ($nrow['optionalApproveStatus']=="Yes") ? "Included" : "Excluded";
                														            $color = ($nrow['optionalApproveStatus']=="Yes") ? "success" : "warning";
                														            
                														            if($nrow['optionalStatus']=="Yes")
        														                        echo "<a class='btn btn-$color btn-sm mt-10'>Optional Line Item $text</a>"?>
                														        </td>
                														        <td >
            														                <img style="height: 100px;width:180px;margin: 10px;" src="./servicesImages/<?echo $nrow['image']?>" alt="img" name="showImage[]">
                														            <a onclick="removeImage('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            														                <input type="file" name="images[]" class="form-control" <?if($nrow['image']!=""){?>style="display:none;"<?}?> >
            														                <input type="text" name="helperFile[]" class="form-control" value="<?echo $nrow['image']?>" hidden>
            														                <input type="text" name="type[]" value="<?echo $nrow['type']?>" hidden>
        														                </td>
        														                <?if($session_role!="Installation Crew"){?>
                                                                                <td >
        														                    <input onkeyup="calculateTotal('<?echo $random?>')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="<?echo $nrow['unit_price']?>" style="width: 200;">
                														            <?if($nrow['type']=="TD"){?>
                														                <a class="btn btn-light-warning btn-sm mt-2">Tear Down</a>
                														            <?}?>
        														                </td>
        														                <td >
    														                        <input class="form-control" type="number" step="0.01" name="total[]" placeholder="Total" value="<?echo $nrow['unit_price']*$nrow['qty']?>" style="width: 200;" readonly>
        													                    </td>
        													                    <td class="text-center">
        													                        <?if($nrow['type']!="Labor Fees"){?>
                													                    <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('<?echo $random?>')"><i style="font-size: x-large;" class="las la-trash"></i></a>
                													                <?}else{?>
                													                    <a href="?entryId=<?echo $jobId?>&labor_delete=1" style="padding: 20px;" class="btn btn-danger btn-sm"><i style="font-size: x-large;" class="las la-trash"></i></a>
                													                <?}?>
        													                    </td>
        													                    <?}?>
                                                                            </tr>
                                                                            <?}?>
														                </tbody>
                                                                    </table>
														        </div>
    														    <?if($session_role!="Installation Crew"){?>
                                                                <div class="row">
    														        <div class="col-12" style="text-align: right;">
    											                        <p>
    											                            Total($): 
    											                            <input class="form-control" style="width: 30%;display: inline;" type="number" step="0.01" name="final_total" value="<?echo round($jobDeets['total'],2)?>" readonly required>
    											                        </p>
    											                    </div>
    													        </div>
    													        <?}?>
    													        
    													        <?if(!$view){?>
    													        <div class="row">
    													            <div class="col-12" style="text-align: right;margin-top:20px;">
    													                <input type="submit" class="btn btn-primary" name="create_job" style="width: 35%;" value="Save Job">
    													                <?if($jobDeets['convertStatus']!="Converted"){?>
        														        <input id="convertToInvoice" class="btn btn-primary" type="submit" name="convertInvoice" value="Convert To Invoice">
        														        <?}?>
    													            </div>
    													        </div>
    													        <?}?>
														    </div>
														    <hr>
													        <div class="card-body pt-0">
													                
    											                <!--notes section-->
    									                        <?include("./notes/notes_table.php");?>
													            <!--install crew functionalities-->
													                <div class="card shadow-sm mb-20">
                                    									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                    										<div class="card-title">
                                    										    Installation Section
                                    										</div>
                                    										<div class="card-toolbar">
                                    										    <?if($permission['add_installation']){?>
                                                                                <a target="_blank" href="addInstallation.php?jobId=<?echo $jobId?>&new=<?echo random();?>" class="btn btn-primary btn-sm">Add Installation Images</a>
                                    										    <?}?>
                                										    </div>
                                    									</div>
                                    									<div class="card-body pt-0">
                                    									    <div class="table-responsive">
                                                                                    <table class="table table-rounded table-striped border gy-7 gs-7 mb-5">
                                    										    <thead>
                                    										        <tr>
                                    										            <th style="text-align: center;">Title</th>
                                    										            <th>Attachment</th>
                                    										            <th>Added By</th>
                                    										            <th>Time Added</th>
                                    										            <?if(!$view){?>
                                                                                        <th>Actions</th>
                                                                                        <?}?>
                                    										        </tr>
                                    										    </thead>
                                    										    <tbody>
                                    										        <?
                                    										        $crewImages=getAll($con,"select * from darlelJobber_installation where jobId='$jobId'");
                                    										        foreach($crewImages as $row){$installationId=$row['id'];?>
                                    										        <tr>
                                    										            <td style="text-align: center;">
                                    										                <?echo $row['title']."<br>"?>
                                    										                <a class="badge badge-<?if($row['timeline']=="Before Installation"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['timeline']?></a>
                                										                </td>
                                    										            <td>
                                    										                <?$installationImages=getAll($con,"select * from darlelJobber_installation_images where installationId='$installationId'");
                                    										                foreach($installationImages as $nrow){?>
                                    										                <a class="badge badge-success btn-sm gallery"  href="uploads/<?echo $nrow['image']?>">
                                										                        <img class="example-image" style="max-height: 2.5rem;" src="uploads/<?echo $nrow['image']?>"/>    
                                                                                            </a>
                                                                	                        <?}?>
                                										                </td>
                                    										            <td><?echo $idToInfo[$row['addedBy']]['name']?></td>
                                    										            <td><?echo date("d M Y",$row['timeAdded'])?></td>
                                    										            <td>
                                                                                            <div class="btn-group">
                                                                                			<a href="addInstallation.php?edit=<?echo $row['id']?>&jobId=<?echo $jobId?>" class="btn btn-warning btn-sm" >Edit</a>
                                    									                    <?if($isAdmin){?>
                                										                    <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-crew-entry=<?echo $row['id']?>&entryId=<?echo $entryId?>">Delete</a>
                                    									                    <?}?>
                                									                    </td>
                                    									            </tr>
                                    										        <?}?>
                                    										    </tbody>
                                    										</table>
                                    									    </div>
                                    									</div>
                                    								</div>
                                								
                            								<?if($session_role!="Installation Crew"){?>
                                                            <!--job tasks section-->
                            								<div class="card shadow-sm mt-4" >
                                                                    <div class="card-header">
                                                                        <h3 class="card-title">Job Reminders</h3>
                                                                        <div class="card-toolbar">
                                                                            <a class="btn btn-primary btn-sm" href="tasks.php?jobId=<?echo $jobId?>&userId=<?echo $session_id?>">Create Reminder</a>
                                                                        </div>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                                <thead>
                                    										        <tr>
                                    										            <th class="text-center">Title</th>
                                    										            <th>Completion Date</th>
                                    										            <th>Actions</th>
                                    										        </tr>
                                    										    </thead>
                                    										    <tbody>
                                    										        <?
                                    										        $queryTasks="select * from darlelJobber_tasks where jobId='$jobId' order by completionDate asc";
                                    									            $tasks=getAll($con,$queryTasks);
                                    										        foreach($tasks as $row){
                                    									            
                                    									            $taskId=$row['id'];
                                    										        //checking whether to mark it as red or not (if the user has not read the comments/file upload)
                                    									            $commentRead=0;
                                    									            $query="select * from darlelJobber_task_comment_status where taskId='$taskId' && status='Not Read' && userId='$session_id'";
                                    									            $result=runQueryReturn($query);
                                    									            if(mysqli_num_rows($result)==0)
                                    									                $commentRead=1;
                                    									            
                                    									            //checking if this task is due soon in order to update its status
                                    										        if($row['status']!="Completed" && $row['status']!="Due Soon" && $row['status']!="Over Due"){
                                    										            $currentTime=time();
                                    										            $timeDifference=abs($row['completionDate']-$currentTime);
                                    										            
                                    										            if($currentTime > $row['completionDate']){
                                    									                    runQuery("update darlelJobber_tasks set status='Over Due' where id='$taskId'");
                                    									                    $row['status']="Over Due";
                                    									                }
                                    									                //either half day more or less
                                    										            else if($timeDifference <= 43200){
                                    										                runQuery("update darlelJobber_tasks set status='Due Soon' where id='$taskId'");
                                    										                $row['status']="Due Soon";
                                    									                }
                                    										        }?>
                                    										        <tr style="<?if(!$commentRead){echo "background-color: #ff0018 !important;";}?>">
                                    										            <td class="text-center">
                                    										                <?$colorStatus= ($row['status']=="Completed") ? "success" : "warning";
										                                                    echo $row['title'];?>
										                                                    <a class="badge badge-<?echo $colorStatus?> btn-sm" style="margin-left: 5px;"><?echo $row['status']?></a>
										                                                </td>
                                    										            <td><?echo date("d M y",$row['completionDate']);$row['completionDate']=date("Y-m-d",$row['completionDate']);?></td>
                                    										            <td>
                                    										                <div class="btn-group">
                                        										                <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="btn btn-success btn-sm" >Detailed View</a>
                                        													</div>
                                        											    </td>
                                                                                    </tr>
                                                                                    <?}?>
                                    										    </tbody>
										    
										
                                                                            </table>
								                                    </div>
                                                                </div>
                                                            </div>
                                                            <!--job section completed-->
                                                        
                                                            
                                                            <!--job tickets section started-->
                            								<?if($session_role!="Installation Crew"){?>
                            								<div class="card shadow-sm mt-4" >
                                                                <div class="card-header">
                                                                    <h3 class="card-title">Job Tickets</h3>
                                                                    <div class="card-toolbar">
                                                                        <a class="btn btn-primary btn-sm" href="create_ticket.php?new=1&jobId=<?echo $jobId?>">Create Ticket</a>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-rounded table-striped border gy-7 gs-7">
                                                                            <thead>
                                										        <tr>
                                										            <th>Client</th>
                                										            <th>Title</th>
                                										            <th>Completion Status</th>
                                										            <th>Actions</th>
                                										        </tr>
                                										    </thead>
                                										    <tbody>
                                										        <?$tickets=getAll($con,"select * from darlelJobber_tickets where jobId='$jobId'");
                                										        foreach($tickets as $row){?>
                            										            <tr>
                            										                <td><?echo $idToInfo[$row['customerId']]['first_name']." ".$idToInfo[$row['customerId']]['last_name']?></td>
                            										                <td><?echo $row['title']?></td>
                            										                <td><?echo $row['completionStatus']?></td>
                            										                <td class="btn-group">
                            										                    <a href="create_ticket.php?ticketId=<?echo $row['id']?>&view=1" class="btn btn-primary btn-sm">View</a>
                            										                    <a href="create_ticket.php?ticketId=<?echo $row['id']?>" class="btn btn-warning btn-sm">Edit</a>
                            										                </td>
                            										            </tr>
                                										        <?}?>
                                                                            </tbody>
    								                                    </table>
    						                                        </div>
                                                                </div>
                                                            </div>
                                                            <?}?>
                                                            <!--job tickets section finished-->
                                                        
                                                                
                                                            
                                                            
                                                            <?}?>
                                                        </div>
												    </div>
												</div>
											</div>
										</div>
									</div>
								<div></div>
							</form>
							</div>
						</div>
					</div>
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
			<script>var hostUrl = "assets/";</script>
    		<script src="assets/js/scripts.bundle.js"></script>
    		  <div class="modal fade" tabindex="-1" id="delete_record">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Entry</h5>
            
                            <!--begin::Close-->
                            <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                                <span class="svg-icon svg-icon-2x"></span>
                            </div>
                            <!--end::Close-->
                        </div>
            
                        <div class="modal-body">
                            <p>Are You Sure You Want To Delete This Entry ?</p>
                        </div>
            
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <a href="#sd" id="delete-project" class="btn btn-danger">
                                Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                $(document).ready(function(){
                    
                    
                    <?if(isset($_GET['convert'])){?>
                        $("#convertToInvoice")[0].click();
                    <?}
                    if($view){?>
						$("#jobForm :input").prop("readonly", true);						        
        	        <?}?>
                    
                  $("#delete_record").on('show.bs.modal', function (e) {
                    //get data-id attribute of the clicked element
                    var url = $(e.relatedTarget).data('url');
                    console.log("modal opened", name)
                    //populate the textbox
                     $("#delete-project").attr("href", url);
                
                  });
                });
            </script>  
	
	
		</div>
	</body>
	
	<script>
	
    var allServices=<?echo json_encode($allServices);?>;
	
	function fillPerUnitCost(divId){
        
         var serviceId=$("."+divId+" input[name='service[]']").val()
         var unitPrice=allServices[serviceId]['price'];
         var filledUnitPrice=$("."+divId+" input[name='unit_price[]']").val();
         var description=allServices[serviceId]['description'];
         var image=allServices[serviceId]['image'];
         
         $("."+divId+" input[name='images[]']").hide();
         $("."+divId+" img[name='showImage[]']").attr("src", "./servicesImages/"+image);
         $("."+divId+" input[name='helperFile[]']").val(image);
         var helper=$("."+divId+" input[name='helperFile[]']").val();
         
         $("."+divId+" textarea[name='description[]']").val(description);
         if(filledUnitPrice=="" || filledUnitPrice==null || filledUnitPrice==0){
            $("."+divId+" input[name='unit_price[]']").val(unitPrice);
            $("."+divId+" input[name='qty[]']").val("0");
            $("."+divId+" input[name='total[]']").val("0");
         }
         
        calculateFinalTotal()
    }
    
    function calculateTotal(divId){
        var unit_price=$("."+divId+" input[name='unit_price[]']").val()
        var qty=$("."+divId+" input[name='qty[]']").val();
        var total=unit_price*qty;
        $("."+divId+" input[name='total[]']").val(total);
        
        calculateFinalTotal()
    }
    function calculateFinalTotal(){
        var totalPrice=0;
        $('input[name^="total"]').each( function() {
            var divId = $(this).closest('tr').attr('class');
            var optionalApproveStatus=$("."+divId+" input[name='optionalApproveStatus[]']").val();
            var optionalStatus=$("."+divId+" input[name='optionalStatus[]']").val();
            if(optionalApproveStatus=="Yes" || optionalStatus=="No" )
                totalPrice += parseFloat(this.value);
        });
        totalPrice = totalPrice.toFixed(2);
        $("input[name='final_total']").val(totalPrice);
    }
    
    function removeImage(divId){
        $("."+divId+" img[name='showImage[]']").attr("src", "");
        $("."+divId+" input[name='helperFile[]']").val("");
        $("."+divId+" input[name='images[]']").show();
    }
    var availableTags = [
    <?foreach($services as $row){?>
      `<?echo $row['name']." SKU =".$row['sku']?>`,
      <?}?>
    ];
    function addRow(){
        var id=makeid(5);
        var string=`
	    <tr id="`+id+`" class="`+id+`">
		    <td>
		        <input name="serviceId[]" value="None" hidden>
    	        <input name="optionalApproveStatus[]" value="No" hidden>
    	        <input name="optionalStatus[]" value="No" hidden>
                <input onfocusout="fillPerUnitCost('`+id+`')" type="text" class="form-control" name="service[]"  style="width: 550;">
                <textarea class="form-control" placeholder="Description" name="description[]" rows="3" style="width: 550;"></textarea>
            </td>   
            <td>
                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="qty[]" placeholder="Quantity" value="0" style="width: 150;">
	        </td>
	        <td>
                <img style="height: 100px;width:180px;margin: 10px;" src="" alt="img" name="showImage[]">
	            <a onclick="removeImage('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
                <input type="file" name="images[]" class="form-control" >
                <input type="text" name="helperFile[]" class="form-control" hidden>
                <input type="text" name="type[]" hidden>
			</td>
			<td>
                <input onkeyup="calculateTotal('`+id+`')" class="form-control" type="number" step="0.01" name="unit_price[]" placeholder="Unit Price" value="0" style="width: 200;">
            </td>
	        <td>
                <input class="form-control" type="number" step="0.01" name="total[]" readonly placeholder="Total" value="0" style="width: 200;">
            </td>
	        <td class="text-center">
                <a style="padding: 20px;" class="btn btn-danger btn-sm" onclick="removeRow('`+id+`')"><i style="font-size: x-large;" class="las la-trash"></i></a>
            </td>
       </tr>`;
		$('#quote_section').append(string);
		$("."+id+" input[name='service[]']").autocomplete({
            source: function(request, response) {
              var words = request.term.split(" ");
              var pattern = $.map(words, function(word) {
                return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
              }).join("");
              var matcher = new RegExp(pattern, "i");
              var filteredTags = $.grep(availableTags, function(value) {
                value = value.label || value.value || value;
                return matcher.test(value.toLowerCase());
              });
              response(filteredTags);
            }
          }).autocomplete("widget").addClass("scrollable-autocomplete");
    }
    
    function removeRow(id){
        $('#'+id).remove();
        $("input[name='subtotal']").val("0");
        $("input[name='final_total']").val("0");
        calculateFinalTotal();
    }
    
    $(document).ready(function() {
        $("#teamSelect").select2({
            dropdownParent: $("#add_team")
        });
    });
        var idToInfo=<?echo json_encode($idToInfo);?>;
        var properties=<?echo json_encode($properties);?>;
        
		    
	    $(document).ready(function(){
	        calculateFinalTotal();
	        $("input[name='service[]']").autocomplete({
              source: function(request, response) {
              var words = request.term.split(" ");
              var pattern = $.map(words, function(word) {
                return "(?=.*" + $.ui.autocomplete.escapeRegex(word) + ")";
              }).join("");
              var matcher = new RegExp(pattern, "i");
              var filteredTags = $.grep(availableTags, function(value) {
                value = value.label || value.value || value;
                return matcher.test(value.toLowerCase());
              });
              response(filteredTags);
            },});
	    });
	    function makeid(length) {
            var result           = '';
            var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            var charactersLength = characters.length;
            for ( var i = 0; i < length; i++ ) {
              result += characters.charAt(Math.floor(Math.random() * 
         charactersLength));
           }
           return result;
        }
		</script>
		<?include("./notes/notes_js.php");?>
		
		
		<!--visits modal-->
		<div class="modal fade" id="add_visit" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px">
				<div class="modal-content rounded">
					<div class="modal-header pb-0 border-0 justify-content-end">
						<div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<span class="svg-icon svg-icon-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
									<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
								</svg>
							</span>
						</div>
					</div>
					
					<div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
						<form action="" method="post" enctype="multipart/form-data">
						    <div class="mb-13 text-left">
							    <h1 class="mb-3" id="modelTitleVisit"></h1>
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Title</span>
								</label>
								<input type="text" name="title" class="form-control" placeholder="Enter Title">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Description</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Description"></textarea>
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Select Team</label>
								<select id="visitTeamSelect" name="team[]" class="form-select form-select-solid" data-control="select2" 
								data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                    <?foreach($users as $row){if($row['role']!='Client'){?>
					                <option value="<?echo $row['id']?>"><?echo $row['name']?></option>
					                <?}}?>
					            </select>
							</div>
        							            
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Type</span>
								</label>
								<select onchange="manageSchedule()" class="form-control" name="type">
								    <option selected value="Schedule Now">Schedule Now</option>
								    <option value="Schedule Later">Schedule Later</option>
								</select>
							</div>
							
							<div id="scheduleSection">
    							<div class="d-flex flex-column mb-8 fv-row">
    							    <div class="row">
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">Start Date</span>
            								</label>
            								<input type="date" name="start_date" class="form-control">
    							        </div>
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">Start Time</span>
            								</label>
            								<input type="time" name="start_time" class="form-control">
    							        </div>
    							    </div>
    							</div>
    							<div class="d-flex flex-column mb-8 fv-row">
    							    <div class="row">
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">End Date</span>
            								</label>
            								<input type="date" name="end_date" class="form-control" >
    							        </div>
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">End Time</span>
            								</label>
            								<input type="time" name="end_time" class="form-control" >
    							        </div>
    							    </div>
    							</div>
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input type="submit" value="Save" name="addVisit" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!--visits modal ending-->
		
		
		<!--shop order modal starting-->
		<div class="modal fade" id="add_shop_order" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px">
				<div class="modal-content rounded">
					<div class="modal-header pb-0 border-0 justify-content-end">
						<div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<span class="svg-icon svg-icon-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
									<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
								</svg>
							</span>
						</div>
					</div>
					
					<div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
						<form action="" method="post" enctype="multipart/form-data">
						    <div class="mb-13 text-left">
							    <h1 class="mb-3" id="modelTitleShop">Shop Order</h1>
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Title</span>
								</label>
								<input type="text" name="title" class="form-control" placeholder="Enter Title">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Description</span>
								</label>
								<textarea class="form-control" name="description" placeholder="Enter Description"></textarea>
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Attachments</label>
							    <input type="file" name="fileToUpload[]" class="form-control" multiple>
        					</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Select Team</label>
								<select id="shopTeamSelect" name="team[]" class="form-select form-select-solid" data-control="select2" 
								data-placeholder="Select an option" data-allow-clear="true" multiple="multiple">
                                    <?foreach($users as $row){if($row['role']!='Client'){?>
					                <option value="<?echo $row['id']?>"><?echo $row['name']?></option>
					                <?}}?>
					            </select>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Type</span>
								</label>
								<select id="shopOrderType" onchange="manageSchedule()" class="form-control" name="type">
								    <option selected value="Schedule Now">Schedule Now</option>
								    <option value="Schedule Later">Schedule Later</option>
								</select>
							</div>
							<div id="scheduleSectionShop">
    							<div class="d-flex flex-column mb-8 fv-row">
    							    <div class="row">
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">Start Date</span>
            								</label>
            								<input type="date" name="start_date" class="form-control">
    							        </div>
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">Start Time</span>
            								</label>
            								<input type="time" name="start_time" class="form-control">
    							        </div>
    							    </div>
    							</div>
    							<div class="d-flex flex-column mb-8 fv-row">
    							    <div class="row">
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">End Date</span>
            								</label>
            								<input type="date" name="end_date" class="form-control" >
    							        </div>
    							        <div class="col-6">
    							            <label class="d-flex align-items-center fs-6 fw-bold mb-2">
            									<span class="required">End Time</span>
            								</label>
            								<input type="time" name="end_time" class="form-control" >
    							        </div>
    							    </div>
    							</div>
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input id="submitShopOrder" type="submit" name="addShopOrder" hidden>
								<a onclick="checkForVisit()" class="btn btn-primary">Save</a>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<!--shop order modal ending-->
		
		
		
	<script>
	        //If a shop order finish date is moved to after the first visit on the job then a error message should pop up confirming they understand this. 
            function checkForVisit(){
	            if(<?echo $displayAlertShop?>){
	                var scheduleOption=$("#shopOrderType").val();
	                if(scheduleOption=="Schedule Now"){
    	                var firstVisitStart="<?echo date("Y-m-d",$firstVisitDeets['start_date']) ;?>";
    	                var shopOrderFinish=$("input[name='end_date']").val();
    	                var userConfirmation =0;
    	               
    	                if(shopOrderFinish > firstVisitStart){
    	                    userConfirmation=confirm("Do you confirm this . The finish date of this shop order is greater than the start date of the first visit ? ");
    	                    if(userConfirmation)
    	                        $("#submitShopOrder")[0].click();
    	                }
    	                else
    	                    $("#submitShopOrder")[0].click();
	                }
	                else
	                    $("#submitShopOrder")[0].click();
	            }
	            else
	                $("#submitShopOrder")[0].click();
            }
	
	
		    function manageSchedule(){
		        var selectedOption=$("select[name='type']").val();
		        var selectedOptionShop=$("#shopOrderType").val();
		        if(selectedOption=="Schedule Now")
		            $("#scheduleSection").show();
		        else
		            $("#scheduleSection").hide();
		        if(selectedOptionShop=="Schedule Now")    
		            $("#scheduleSectionShop").show();
	            else
		            $("#scheduleSectionShop").hide();
		    }
		    $(document).ready(function(){
		        
		        /*visits jquery*/
		        $("#add_visit").on('show.bs.modal', function (e) {
                    var mydata = $(e.relatedTarget).data('mydata');
                    console.log(mydata);
                    $('#visitTeamSelect option').prop('selected', false);
            
                    if(mydata!= null){
                        $("#modelTitleVisit").html("Update Visit Details");
                    	$("#visitTitle").val(mydata['title']);
                    	$("textarea[name='description']").val(mydata['description']);
                    	$("select[name='type']").val(mydata['type']);
                    	$("input[name='start_date']").val(mydata['start_date']);
                    	$("input[name='title']").val(mydata['title']);
                    	$("input[name='end_date']").val(mydata['end_date']);
                    	$("input[name='start_time']").val(mydata['start_time']);
                    	$("input[name='end_time']").val(mydata['end_time']);
                    	$("input[name='actionId']").val(mydata['id']);
                    	$("select[name='type']").val(mydata['type']);
                    	var assignedTeam=mydata['assignedTeam'];
                        console.log(assignedTeam);
                        
                        for (var i = 0; i < assignedTeam.length; i++) {
                          $('#visitTeamSelect option[value="' + assignedTeam[i] + '"]').prop('selected', true);
                        }
                    }else{
                    	$("#modelTitleVisit").html("Add Visit");
                		$("#visitTitle").val("");
                    	$("textarea[name='description']").val("");
                    	$("select[name='type']").val("Schedule Now");
                        var today_date = moment().format('YYYY-MM-DD');
                    	$("input[name='start_date']").val(today_date);
                    	$("input[name='end_date']").val(today_date);
                    	$("input[name='title']").val("<?echo $userDeets['first_name']." ".$userDeets['last_name']?>");
                    	$("input[name='start_time']").val("13:30");
                    	$("input[name='end_time']").val("14:30");
                    	$("input[name='actionId']").val("");
                    }
                    $('#visitTeamSelect').trigger('change');
                });
	            /*visits jquery ending*/
	            
	            
	            /*shop order jquery*/
	            
		        $("#add_shop_order").on('show.bs.modal', function (e) {
                    var mydata = $(e.relatedTarget).data('mydata');
                    $('#shopTeamSelect option').prop('selected', false);
                    
                    if(mydata!= null){
                        $("#modelTitleShop").html("Update Shop Order Details");
                    	$("textarea[name='description']").val(mydata['description']);
                    	$("select[name='type']").val(mydata['type']);
                    	$("input[name='start_date']").val(mydata['start_date']);
                    	$("input[name='title']").val(mydata['title']);
                    	$("input[name='end_date']").val(mydata['end_date']);
                    	$("input[name='start_time']").val(mydata['start_time']);
                    	$("input[name='end_time']").val(mydata['end_time']);
                    	$("input[name='actionId']").val(mydata['id']);
                    	$("select[name='type']").val(mydata['scheduleType']);
                    	var assignedTeam=mydata['assignedTeam'];
                        console.log(assignedTeam);
                        
                        for (var i = 0; i < assignedTeam.length; i++) {
                          $('#shopTeamSelect option[value="' + assignedTeam[i] + '"]').prop('selected', true);
                        }
                    }else{
                    	$("#modelTitleShop").html("Add Shop Order");
                		$("textarea[name='description']").val("");
                    	$("select[name='type']").val("Schedule Now");
                        var today_date = moment().format('YYYY-MM-DD');
                    	$("input[name='start_date']").val(today_date);
                    	$("input[name='end_date']").val(today_date);
                    	$("input[name='title']").val("");
                    	$("input[name='start_time']").val("13:30");
                    	$("input[name='end_time']").val("14:30");
                    	$("input[name='actionId']").val("");
                    }
                    $('#shopTeamSelect').trigger('change');
                
                });
                /*shop order jquery ending*/
                
                <?if(isset($_GET['openShopOrder'])){?>
                    $("#add_shop_order_btn")[0].click();
                <?}?>
		    }); 
		
		</script>
		
		
		
		<!--installation image section-->
		<script>
		$('option').mousedown(function(e) {
                e.preventDefault();
                var originalScrollTop = $(this).parent().scrollTop();
                $(this).prop('selected', $(this).prop('selected') ? false : true);
                var self = this;
                $(this).parent().focus();
                setTimeout(function() {
                    $(self).parent().scrollTop(originalScrollTop);
                }, 0);
            return false;
        });
	    $(document).ready(function(){
        
        $('form').submit(function(event) {
          $(this).find(':submit').css('pointer-events', 'none');
        });
	    });
	    </script>
</html>
