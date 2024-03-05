<?
require("global.php");
if($logged==0 || (!isset($_GET['id'])) || (!$permission['view_client']))
    header("Location:./index.php");

$customerId=clear($_GET['id']);

$idToUserDeets=[];
$allUsers=getAll($con,"select * from darlelJobber_users where role!='Client'");
foreach($allUsers as $row)
    $idToUserDeets[$row['id']]=$row;

$idToContactDetails=[];
$idToContactName=[];
$allContacts=getAll($con,"select * from darlelJobber_contacts where customerId='$customerId'");
foreach($allContacts as $row)
    $idToContactName[$row['id']]=$row['name'];
    
$allContactDetails=getAll($con,"select * from darlelJobber_contact_details cd inner join darlelJobber_contacts c on cd.contactId=c.id where c.customerId='$customerId'");
foreach($allContactDetails as $row){
    if (isset($idToContactDetails[$row['contactId']])) 
        $idToContactDetails[$row['contactId']][] = $row['value'];
    else 
        $idToContactDetails[$row['contactId']] = [$row['value']];
}

$labelNameToColor=[];
$labels=getAll($con,"select * from darlelJobber_labels order by timeAdded desc");
foreach($labels as $row){
    $labelNameToColor[$row['title']]=$row['colorCode'];
}


$timeAdded=time();
$userId=clear($_GET['id']);
$clientDeets=getRow($con,"select * from darlelJobber_users where id='$userId'");
$loginUrl="index.php?userId=$userId";
$propertyDeets=array("");
$properties=getAll($con,"select * from darlelJobber_properties");
foreach($properties as $row){$propertyDeets[$row['id']]=$row;}

if(isset($_POST['addProperty'])){
    $street1=clear($_POST['street1']);
    $street2=clear($_POST['street2']);
    $type=clear($_POST['type']);
    $city=clear($_POST['city']);
    $state=clear($_POST['state']);
    $zip_code=clear($_POST['zip_code']);
    $country=clear($_POST['country']);
    $actionId=clear($_POST['actionIdProperty']);
    $random=random();
    $action=clear($_GET['action']);
    
    
    if($actionId=="" || $action=="addPropertyFromQuote" || $action=="editPropertyFromQuote" || $action=="addPropertyFromRequest" || $action=="editPropertyFromRequest"){
        $propertyId=$random;
        $query="insert into darlelJobber_properties set id='$random',userId='$userId',street1='$street1',street2='$street2',
        city='$city',state='$state',zip_code='$zip',country='$country',type='secondary',timeAdded='$timeAdded'";
        runQuery($query);
    }
    else{
        $propertyId=$actionId;
        $query="update darlelJobber_properties set street1='$street1',street2='$street2',
        city='$city',state='$state',zip_code='$zip',country='$country' where id='$actionId'";
        runQuery($query);
        
        if($type=="primary"){//if the type is primary then edit in the users table as well
            $query="update darlelJobber_users set street1='$street1',street2='$street2',
            city='$city',state='$state',zip_code='$zip',country='$country' where id='$userId'";
            runQuery($query);
        }
    }
    
    
    $startEndString="";
    if((isset($_GET['start']))&& (isset($_GET['end']))){
        $start=clear($_GET['start']);
        $end=clear($_GET['end']);
        $startEndString="&start=$start&end=$end";
    }
    
    
    if($action=="addPropertyFromQuote" || $action=="editPropertyFromQuote"){
        $customerId=clear($_GET['id']);
        $quoteId=clear($_GET['quoteId']);
        if($quoteId=="newQuote")
            header("Location:./createQuote.php?new=1&customerId=$customerId&propertyId=$propertyId");
        else
            header("Location:./createQuote.php?entryId=$quoteId&customerId=$customerId&propertyId=$propertyId");
    }
    else if($action=="addPropertyFromRequest" || $action=="editPropertyFromRequest"){
        $customerId=clear($_GET['id']);
        $requestId=clear($_GET['requestId']);
        if($requestId=="newRequest")
            header("Location:./createRequest.php?new=1&customerId=$customerId&propertyId=$propertyId$startEndString");
        else
            header("Location:./createRequest.php?entryId=$requestId&customerId=$customerId&propertyId=$propertyId");
    }
    else
        header("Location:?id=$userId&m=Property information updated successfully");
}
if(isset($_GET['delete-record-property'])){
    $id=$_GET['delete-record-property'];
    $query="delete from darlelJobber_properties where id='$id'";
    runQuery($query);
    header("Location:?id=$userId&m=Property details has been deleted");
}
if(isset($_GET['delete-notes'])){
    $id=$_GET['delete-notes'];
    $query="delete from darlelJobber_notes where id='$id'";
    runQuery($query);
    header("Location:?id=$userId&m=Notes has been deleted");
}
if(isset($_GET['login-email'])){
    $title="Login Email";
    $description="Kindly use the following link to access the system
    <br>
    <a href='$projectUrl$loginUrl'>Access Your Portal</a>
    ";
    $userEmail=getRow($con,"select email from darlelJobber_users where id='$userId'")['email'];
    $userEmail=explode("*",$userEmail);
    $userEmail=$userEmail[0];
    sendEmailNotification_mailjet($title, $description, $userEmail);
    header("Location:?id=$userId&m=Login email sent successfully");
}
if(isset($_GET['login-as'])){
    session_destroy();
    header("Location:./index.php?userId=$userId");
}

/*payment module php*/
if(isset($_POST['collectPayment'])){
    $paymentId=clear($_POST['paymentId']);
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $amountPaid=$_POST['amountPaid'];
    $discountAvailed=$_POST['discountAvailed'];
    $method=clear($_POST['method']);
    $transactionDate=strtotime(clear($_POST['transactionDate']));
    $id=generateRandomString();
    if($paymentId=="")
        $query="insert into darlelJobber_payments set id='$id',title='$title',description='$description',discountAvailed='$discountAvailed',amountPaid='$amountPaid',method='$method',transactionDate='$transactionDate',
        addedBy='$session_id',customerId='$customerId'";
    else
        $query="update darlelJobber_payments set title='$title',description='$description',receipt='',discountAvailed='$discountAvailed',amountPaid='$amountPaid',method='$method',transactionDate='$transactionDate' where id='$paymentId'";
    runQuery($query);
    
    //after editing we are checking if the quote/invoice is paid now
    $paymentDeets=getRow($con,"select * from darlelJobber_payments where id='$paymentId'");
    if($paymentDeets['quoteId']!=""){
        //means the payment updated is related to a quote
        $quoteId=$paymentDeets['quoteId'];
        $quoteDeets=getRow($con,"select * from darlelJobber_quotes where id='$quoteId'");
        $requiredAmount=$quoteDeets['requiredDepositAmount'];
        $paidAmount=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where quoteId='$quoteId'")['paidAmount'];
        if($paidAmount >= $requiredAmount)
            $query="update darlelJobber_quotes set paidStatus='Paid',paidDate='$timeAdded' where id='$quoteId'";
        else
            $query="update darlelJobber_quotes set paidStatus='Pending' where id='$quoteId'";
        runQuery($query);
    }
    else if($paymentDeets['invoiceId']!=""){
        //means the payment updated is related to an invoice
        $invoiceId=$paymentDeets['invoiceId'];
        $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where id='$invoiceId'");
        $requiredAmount=$invoiceDeets['total'];
        $paidAmount=getRow($con,"SELECT sum(amountPaid+discountAvailed) as paidAmount from darlelJobber_payments where invoiceId='$invoiceId'")['paidAmount'];
        if($paidAmount >= $requiredAmount)
            $query="update darlelJobber_invoices set paidStatus='Paid',paidDate='$timeAdded' where id='$invoiceId'";
        else
            $query="update darlelJobber_invoices set paidStatus='Pending' where id='$invoiceId'";
        runQuery($query);
    }
    header("Location:?id=$userId&m=Payment Noted successfully");
}
if(isset($_GET['delete-payment'])){
    $id=clear($_GET['delete-payment']);
    $query="delete from darlelJobber_payments where id='$id'";
    runQuery($query);
    header("Location:?id=$userId&m=Payment has been deleted successfully");
}
/*payment module php*/


//used in billing section
$invoiceIdToDeets=[];
$quoteIdToDeets=[];
$invoices=getAll($con,"select * from darlelJobber_invoices");
$quotes=getAll($con,"select * from darlelJobber_quotes");
foreach($invoices as $row){$invoiceIdToDeets[$row['id']]=$row;}
foreach($quotes as $row){$quoteIdToDeets[$row['id']]=$row;}



//sending email with and without attachments module
if(isset($_POST['sendEmail'])){
    $title=clear($_POST['title']);
    $description=clear($_POST['description']);
    $email=explode("*",$clientDeets['email']);
    $email=$email[0];
    
    $receiptId=clear($_POST['receiptId']);
    $quoteId=clear($_POST['quoteId']);
    $invoiceId=clear($_POST['invoiceId']);
    
    $fileSend=1;
    if($receiptId=="" && $quoteId=="" && $invoiceId==""){//means just sending a normal email
        sendEmailNotification_mailjet($title,$description,$email);
        $fileSend=0;
    }
    else if($receiptId!=""){
        $sendingFile=getRow($con,"select receipt from darlelJobber_payments where id='$receiptId'")['receipt'];
    }
    else if($quoteId!=""){
        $sendingFile="$quoteId.pdf";
        $query="update darlelJobber_quotes set pdfSnapshot='$sendingFile' where id='$quoteId'";
        runQuery($query);
        
        $url=urlencode($g_website.'/printQuoteInvoice.php?quoteId='.$quoteId);
        printPage(urldecode($url),$quoteId);
    }
    else if($invoiceId!=""){
        $sendingFile="$invoiceId.pdf";
        $query="update darlelJobber_invoices set pdfSnapshot='$sendingFile' where id='$invoiceId'";
        runQuery($query);
        
        $url=urlencode($g_website.'/printQuoteInvoice.php?invoiceId='.$invoiceId);
        printPage(urldecode($url),$invoiceId);
    }
    if($fileSend){
        $file_name_link=trim("./uploads/".$sendingFile);
        $the_content_type = $file_name_link;
    	$get_file = file_get_contents($file_name_link);
    	$content = base64_encode($get_file);
	    $data = [
    		"ContentType" => $the_content_type,
    		"Filename" => $file_name_link,
    		"Base64Content" => $content,
    	];
    	sendEmailNotification_mailjet($title,$description,$email,1,$data);
    }
    
    header("Location:?m=Email has been sent successfully&id=$userId");
}

//taking snapshot of the quote/invoice reuse this 
if(isset($_GET['saveSnapShot'])){
    $quoteId=clear($_GET['quoteId']);
    $invoiceId=clear($_GET['invoiceId']);
    $pdfSnapshot=random();
    if($quoteId!=""){//if quote has been sent for snapshot
        $query="update darlelJobber_quotes set pdfSnapshot='$pdfSnapshot.pdf' where id='$quoteId'";
        runQuery($query);
        $url=urlencode($g_website.'/printQuoteInvoice.php?quoteId='.$quoteId);
    }
    else if($invoiceId!=""){//if invoice has been sent for snapshot
        $query="update darlelJobber_invoices set pdfSnapshot='$pdfSnapshot.pdf' where id='$invoiceId'";
        runQuery($query);
        $url=urlencode($g_website.'/printQuoteInvoice.php?invoiceId='.$invoiceId);
    }
    printPage(urldecode($url),$pdfSnapshot);
    header("Location:?m=PDF has been generated successfully&id=$userId");
}

$callLogs=getAll($con,"select * from darlelJobber_call_logs where customerId='$userId' order by timeAdded desc");
$reminders=getAll($con,"select * from darlelJobber_tasks where customerId='$userId' order by timeAdded desc");

require("./emailsAndSms/sendingSms.php");
require("./emailsAndSms/sendingEmail.php");
require("./notes/notes.php");
?>
<html lang="en">
	<head>
	    <script src="https://maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyCdk2mdBvjhJmrFA9BWswlJlOz7WoU75-k"></script>
		<?require("./includes/views/head.php");?>
		<style>
	    .modal{
            z-index: 1000;   
        }
        .modal-backdrop{
            z-index: 900;        
        }
	</style>
	</head>
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					<div class="content d-flex flex-column flex-column-fluid">
					    <div class="post d-flex flex-column-fluid">
					        <div class="container-xxl" id="kt_content_container" style="max-width: 100%;">
					            <?if(isset($_GET['m'])){?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo clear($_GET['m'])?></h4>
                                    </div>
                                </div>
                                <?}?>
					            <div class="row mb-10">
					                <div class="col-12">
					                    <div style="text-align: right;">
					                        <div class="btn btn-group">
    					                        <a href="tasks.php?customerId=<?echo $userId?>&userId=<?echo $session_id?>" class="btn btn-success">REMINDER</a>
    					                        <a href="callLogs.php?userId=<?echo $session_id?>&customerId=<?echo $userId?>" class="btn btn-primary">CALL LOG</a>
    					                        <a href="#" data-bs-toggle="modal" data-bs-target="#emailModal" data-mydata='simpleMultipleMail' class="btn btn-warning">EMAIL</a>
    					                        <a href="#" data-bs-toggle="modal" data-bs-target="#smsModal" class="btn btn-primary">SMS</a>
    					                        
    					                        <?if($permission['edit_client']){?>
    					                        <a href="addClient.php?customerId=<?echo $userId?>" class="btn btn-warning">EDIT</a>
    					                        <?}?>
    					                        
    					                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    New
                                                </button>
                                                  <ul class="dropdown-menu">
                                                    <?if($permission['add_requests']){?>
    					                            <li><a class="dropdown-item" href="./createRequest.php?new=1&customerId=<?echo $userId?>">REQUEST</a></li>
                                                    <?}if($permission['add_quotes']){?>
    					                            <li><a class="dropdown-item" href="./createQuote.php?new=1&customerId=<?echo $userId?>">QUOTE</a></li>
                                                    <?}?>
                                                    <li><a class="dropdown-item" href="?id=<?echo $userId?>&login-email=1">SEND LOGIN EMAIL</a></li>
                                                    <li><a class="dropdown-item" href="?id=<?echo $userId?>&login-as=1">LOGIN AS CLIENT</a></li>
                                                  </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!--left section started-->
                                    <div class="col-sm-12 col-md-2 col-12">
                                        <div class="row text-center">
                                            <div class="col-12">
                                                <!--account information started-->
                                                <div class="card card-flush">
                                                    <div class="card-body pt-0">
                                                        <h3 class="mt-5">Account Information</h3>
                                                        <p>Account Name : <?echo $clientDeets['first_name']." ".$clientDeets['last_name']?></p>
                                                        <p>Company Name : <?echo $clientDeets['company_name']?></p>
                                                        <ul class="mt-5">
                                                        <?$contact_type=explode("*",$clientDeets['contact_type']);
            										    if($clientDeets['phone']!=""){
            										        $userPhones=explode("*",$clientDeets['phone']);
                										    for($i=0;$i<count($userPhones);$i++){
                										        echo "<li>".$contact_type[$i]." : ".$userPhones[$i]."</li>"; 
                										    }
            										    }
            					                        if($clientDeets['email']!=""){
                										    $email_type=explode("*",$clientDeets['email_type']);
                										    $userEmails=explode("*",$clientDeets['email']);
                										    for($i=0;$i<count($userEmails);$i++){
                										        echo "<li>".$email_type[$i]." : ".$userEmails[$i]."</li>"; 
                										    }
            										    }?>
            					                        </ul>
        					                        </div>
    					                        </div>
    					                        <!--account information finished-->
                                            </div>
                                            <?foreach($allContacts as $row){?>
                                            <div class="col-12 mt-3">
                                                <!--contact information started-->
                                                <div class="card card-flush">
                                                    <div class="card-body pt-0">
                                                        <h3 class="mt-5">Contact Information</h3>
                                                        <p>Contact Name : <?echo $row['name']?></p>
                                                        <ul class="mt-5">
                                                        <?$contacts=$idToContactDetails[$row['id']];
                                                        foreach($contacts as $row){
            										        echo "<li>".$row."</li>"; 
            										    }
        										        ?>
            					                        </ul>
        					                        </div>
    					                        </div>
    					                        <!--account information finished-->
                                            </div>
                                            <?}?>
					                    </div>
                                    </div>
					                <!--left section finished-->
					                
                                       
                                    <!--middle section started-->
                                    <div class="col-sm-12 col-md-7 col-12">
                                        <div class="col-12">
                                            <div class="card card-flush">
                                                <div class="card-body pt-0">
                                                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6 mt-10">
                                                        <li class="nav-item col-4 me-5" style="width: 45%;">
                                                            <a class="nav-link text-center active" data-bs-toggle="tab" href="#callLogs" style="color: #73141d;" >Call Log</a>
                                                        </li>
                                                        <li class="nav-item col-4 me-5" style="width: 50%;">
                                                            <a class="nav-link text-center" data-bs-toggle="tab" href="#reminders" style="color: #73141d;" >Reminders</a>
                                                        </li>
                                                    </ul>
                                                    
                                                    <div class="mt-8">
                                                        <div class="tab-content" id="myTabContent">
                                                            <div class="tab-pane fade active show" id="callLogs" role="tabpanel">
                                                                <div class="table-responsive">
                                                                    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                										                <thead>
                    										                <tr>
                    										                    <th>Description</th>
                    										                    <th>Time Added</th>
                    										                </tr>
                										                </thead>
                										                <tbody>
                										                    <?foreach($callLogs as $row){?>
                										                    <tr>
                										                        <td><?echo $row['description']?></td>
                										                        <td><?echo date("d M Y",$row['timeAdded'])?></td>
                										                    </tr>
                										                    <?}?>
                										                </tbody>
                										            </table>
                        										</div>
                                                            </div>
                                                            <div class="tab-pane fade" id="reminders" role="tabpanel">
                                                                <div class="table-responsive">
                                                                    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                										                <thead>
                    										                <tr>
                    										                    <td>Subject</td>
                    										                    <td>Comments</td>
                    										                    <td>Actions</td>
                    										                </tr>
                										                </thead>
                										                <tbody>
                										                    <?foreach($reminders as $row){?>
                										                    <tr>
                										                        <td><?echo $row['title']?></td>
                										                        <td><?echo $row['description']?></td>
                										                        <td>
                										                            <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="text-white badge badge-primary btn-sm me-1">
                										                                <i class="text-white bi bi-eye fs-2x"></i>
            										                                </a>
                										                        </td>
                										                    </tr>
                										                    <?}?>
                										                </tbody>
                										            </table>
                        										</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12 mt-5">
                                        <div class="card card-flush">
                                            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        										<div class="card-title">
        											<div class="d-flex align-items-center position-relative my-1">
        											    Properties
        											</div>
        										</div>
        										<div class="card-toolbar">
        										    <?if($permission['edit_client']){?>
                									<a href="#" data-bs-toggle="modal" data-bs-target="#add_property" class="btn btn-primary btn-sm" id="addPropertyBtn">Add Property</a>
        										    <?}?>
        										</div>
        									</div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
        										    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
            										    <thead>
            										        <tr>
            										            <th>Street 1</th>
            										            <th>Street 2</th>
            										            <th>City</th>
            										            <th>State</th>
            										            <th>Zip Code</th>
            										            <th>Country</th>
            										            <th>Type</th>
            										            <th>Actions</th>
            										        </tr>
            										    </thead>
            										    <tbody>
            										        <?$properties=getAll($con,"select * from darlelJobber_properties where userId='$userId' order by timeAdded desc");
            										        foreach($properties as $row){
            										        $googleMapAddress=$row['street1']." ".$row['street2']." ".$row['city']." ".$row['state']." ".$row['country'];?>
                										        <a id="editProperty_<?echo $row['id']?>" href="#" data-bs-toggle="modal" data-bs-target="#add_property" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="d-none">Edit</a>
                                                                <tr>
                										           <td><?echo $row['street1']?></td>
                										           <td><?echo $row['street2']?></td>
                										           <td><?echo $row['city']?></td>
                										           <td><?echo $row['state']?></td>
                										           <td><?echo $row['zip_code']?></td>
                										           <td><?echo $row['country']?></td> 
                										           <td><?echo $row['type']?></td> 
                										           <td>
                										               <div class="btn-group">
                										                   <a target="_blank" class="btn btn-primary btn-sm" href="https://www.google.com/maps/search/?api=1&query=<?echo $googleMapAddress?>">View</a>
            											                   <?if($permission['edit_client']){?>
                                                                                <a href="#" data-bs-toggle="modal" data-bs-target="#add_property" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">Edit</a>
                                                                                <?if($row['type']!="primary"){?>
                                                                                <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-record-property=<?echo $row['id']?>&id=<?echo $userId?>" class="btn btn-danger btn-sm">Delete</a>
                                                                            <?}}?>
                                                                        </div>
                										           </td>
                												</tr>
            										        <?}?>
            										    </tbody>
            										</table>
        										</div>
    										</div>
    										</div>
                                        </div>
                                    </div>
					                <!--middle section finished-->
					                
					                
                                    <!--right section started-->
                                    <div class="col-sm-12 col-md-3 col-12">
                                        <div class="row text-center">
                                            <!--account information started-->
                                            <div class="col-12 mb-3">
                                                <div class="card card-flush">
                                                    <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#requestsCollapsible">
                                                        <h3 class="card-title">Requests</h3>
                                                        <div class="card-toolbar rotate-180">
                                                            <i style="font-size: xx-large;" class="las la-angle-down"></i>
                                                        </div>
                                                    </div>
                                                    <div id="requestsCollapsible" class="collapse">
                                                    <div class="card-body pt-0">
                                                        <div class="table-responsive">
                										    <table class="table table-rounded table-striped border gs-7 dataTable text-center">
                										        <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Address</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?$requests=getAll($con,"select * from darlelJobber_requests where request_for='$userId' order by timeAdded desc");
                                                                    foreach($requests as $row){?>
                                                                    <tr>
                                                                        <td><a href="createRequest.php?entryId=<?echo $row['id']?>&view=1"><?echo date("d M Y",$row['start_date']);?></a></td>
                    											        <td>
                                                                            <?$property=$propertyDeets[$row['propertyId']];
                                                                            echo $property['street1']." ".$property['street2']." ".$property['city']." ".$property['state']." ".$property['country'];?>
                                                                        </td>
                                                                        <td>
                                                                            <?$nrow=["userDeetsId"=>$userId,"redirection"=>"request","entryId"=>$row['id'],"hidePDF"=>1];?>
                                                                            <a href="#" data-bs-toggle="modal" 
                                                                            data-bs-target="#emailModal"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' 
                                                                            class="btn btn-success btn-sm">Send By Email</a>
                                                                        </td>
                                                                    </tr>
                                                                    <?}?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    </div>
    					                        </div>
    					                    </div>
					                        <!--account information finished-->
					                        
					                        <!--account information started-->
                                            <div class="col-12 mb-3">
                                                <div class="card card-flush">
                                                    <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#quotesCollapsible">
                                                        <h3 class="card-title">Quotes</h3>
                                                        <div class="card-toolbar rotate-180">
                                                            <i style="font-size: xx-large;" class="las la-angle-down"></i>
                                                        </div>
                                                    </div>
                                                    <div id="quotesCollapsible" class="collapse">
                                                    <div class="card-body pt-0">
                                                        <div class="table-responsive">
                										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Contact</th>
                                                                        <th>Address</th>
                                                                        <th>Total</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?$quotes=getAll($con,"select * from darlelJobber_quotes where customerId='$userId' order by timeAdded desc");
                                                                    foreach($quotes as $row){?>
                                                                    <tr>
                                                                        <td><a href="viewQuote.php?entryId=<?echo $row['id']?>"><?echo "#".$row['quote_number']." ".$idToContactName[$row['contactId']]?></a></td>
                                                                        <td>
                                                                            <?$property=$propertyDeets[$row['propertyId']];
                                                                            echo $property['street1']." ".$property['street2'].",".$property['city']."<br>".$property['state'].",".$property['country'];?>
                                                                        </td>
                                                                        <td><?echo $row['total']?></td>
                                                                        <td>
                                                                            <div class="btn-group">
                                                                                <?if($row['pdfSnapshot']!="None"){?>
                                                                                <a target="_blank" class="btn btn-warning btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>">View PDF</a>
                                                                                <a class="btn btn-primary btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>" download>Download PDF</a>
                                                                                <?}
                                                                                $nrow=[
                                                                                    "userDeetsId"=>$userId,
                                                                                    "redirection"=>"quote",
                                                                                    "entryId"=>$row['id'],
                                                                                ];
                                                                                ?>
                                                                                <a href="#" data-bs-toggle="modal" data-bs-target="#emailModal"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' class="btn btn-success btn-sm">Send PDF</a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    <?}?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    </div>
    					                        </div>
    					                    </div>
					                        <!--account information finished-->
					                        
					                        <!--account information started-->
                                            <div class="col-12 mb-3">
                                                <div class="card card-flush">
                                                    <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#jobsCollapsible">
                                                        <h3 class="card-title">Jobs</h3>
                                                        <div class="card-toolbar rotate-180">
                                                            <i style="font-size: xx-large;" class="las la-angle-down"></i>
                                                        </div>
                                                    </div>
                                                    <div id="jobsCollapsible" class="collapse">
                                                    <div class="card-body pt-0">
                                                        <div class="table-responsive">
                										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Contact</th>
                                                                        <th>Address</th>
                                                                        <th>Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?
                                                                    $jobs=getAll($con,"select * from darlelJobber_jobs where customerId='$userId' order by timeAdded desc");
                                                                    foreach($jobs as $row){
                                                                    $contactId=$quoteIdToDeets[$row['quoteId']]['contactId'];
                                                                    ?>
                                                                    <tr>
                                                                        <td><a href="createJob.php?entryId=<?echo $row['id']?>&view=1"><?echo "#".$row['job_number']." ".$idToContactName[$contactId]?></a></td>
                                                                        <td>
                                                                            <?$property=$propertyDeets[$row['propertyId']];
                                                                            echo $property['street1']." ".$property['street2'].",".$property['city']."<br>".$property['state'].",".$property['country'];?>
                                                                        </td>
                                                                        <td><?echo $row['total']?></td>
                                                                    </tr>
                                                                    <?}?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    </div>
    					                        </div>
    					                    </div>
					                        <!--account information finished-->
					                        
					                        <!--invoices started-->
                                            <div class="col-12 mb-3">
                                                <div class="card card-flush">
                                                    <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#invoicesCollapsible">
                                                        <h3 class="card-title">Invoices</h3>
                                                        <div class="card-toolbar rotate-180">
                                                            <i style="font-size: xx-large;" class="las la-angle-down"></i>
                                                        </div>
                                                    </div>
                                                    <div id="invoicesCollapsible" class="collapse">
                                                    <div class="card-body pt-0">
                                                        <div class="table-responsive">
                										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Contact</th>
                                                                        <th>Address</th>
                                                                        <th>Total</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?
                                                                    $invoices=getAll($con,"select * from darlelJobber_invoices where customerId='$userId' order by timeAdded desc");
                                                                    foreach($invoices as $row){
                                                                    $contactId=$quoteIdToDeets[$row['quoteId']]['contactId'];
                                                                    $propertyId=$quoteIdToDeets[$row['quoteId']]['propertyId'];
                                                                    ?>
                                                                    <tr>
                                                                        <td><a href="viewInvoice.php?entryId=<?echo $row['id']?>"><?echo "#".$row['invoice_number']." ".$idToContactName[$contactId]?></a></td>
                                                                        <td>
                                                                            <?$property=$propertyDeets[$propertyId];
                                                                            echo $property['street1']." ".$property['street2'].",".$property['city']."<br>".$property['state'].",".$property['country'];?>
                                                                        </td>
                                                                        <td><?echo $row['total']?></td>
                                                                        <td>
                                                                            <div class="btn-group">
                                                                                <?if($row['pdfSnapshot']!="None"){?>
                                                                                <a target="_blank" class="btn btn-warning btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>">View PDF</a>
                                                                                <a class="btn btn-primary btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>" download>Download PDF</a>
                                                                                <?}
                                                                                $nrow=["userDeetsId"=>$userId,"redirection"=>"invoice","entryId"=>$row['id'],];
                                                                                ?>
                                                                                <a href="#" data-bs-toggle="modal" data-bs-target="#emailModal"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' 
                                                                                class="btn btn-success btn-sm">Send PDF</a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    <?}?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    </div>
    					                        </div>
    					                    </div>
					                        <!--invoices finished-->
                                            
                                            
                                            
                                            
                                            <!--deposits started-->
                                            <div class="col-12 mb-3">
                                                <div class="card card-flush">
                                                    <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#depositsCollapsible">
                                                        <h3 class="card-title">Deposits</h3>
                                                        <div class="card-toolbar rotate-180">
                                                            <i style="font-size: xx-large;" class="las la-angle-down"></i>
                                                        </div>
                                                    </div>
                                                    <div id="depositsCollapsible" class="collapse">
                                                    <div class="card-body pt-0">
                                                        <div class="table-responsive">
                                                            <table class="table table-rounded table-striped border  gs-7 dataTable text-center" >
                    										    <thead>
                    										        <tr >
                    										            <th>Title</th>
                    										            <th>Date</th>
                    										            <th>Amount Paid</th>
                    										            <th>Discount Availed</th>
                    										            <th>Method</th>
                    										            <th>Added By</th>
                    										            <th>Actions</th>
                    										        </tr>
                    										    </thead>
                    										    <tbody>
                    										        <?
                    										        $payments=getAll($con,"select * from darlelJobber_payments where customerId='$customerId' order by transactionDate desc");
                    										        foreach($payments as $row){
                    										        $quoteDeets=$quoteIdToDeets[$row['quoteId']];
                    										        $invoiceDeets=$invoiceIdToDeets[$row['invoiceId']];
                    										        $withTitleText= ($row['quoteId']=="") ? " Invoice Payment " : " Quote Deposit ";
                    										        ?>
                    										        <tr>
                    										            <td>
                										                    <?if($row['quoteId']!=""){?><a href="createQuote.php?entryId=<?echo $row['quoteId']?>&view=1"><?echo "#".$quoteDeets['quote_number']." ".$quoteDeets['title']?></a><?}?>    
                										                    <?if($row['invoiceId']!=""){?><a href="createInvoice.php?entryId=<?echo $row['invoiceId']?>&view=1"><?echo "#".$invoiceDeets['invoice_number']." ".$invoiceDeets['subject']?></a><?}?>    
                										                    <?echo $withTitleText.$row['title']?>
                    										            </td>
                    										            <td ><?echo date("d M y",$row['transactionDate'])?></td>
                    										            <td ><?echo "$".$row['amountPaid']?></td>
                    										            <td ><?echo "$".$row['discountAvailed']?></td>
                    										            <td ><?echo $row['method']?></td>
                    										            <td >
                    										                <?echo ($row['addedBy']!="Machine") ? $idToUserDeets[$row['addedBy']]['name'] : "Machine";?>
                										                </td>
                    										            <td>
                    										                <div class="btn-group">
                        										                <?$row['transactionDate']=date("Y-m-d",$row['transactionDate']);
                        										                $nrow=[];
                										                        $nrow['receiptId']=$row['id'];
                                                                                $nrow['title']="Vilo Fence Deposit Receipt";
                                                                                $nrow['description']="Thank You for choosing Vilo Fence . Kindly find the attached receipt of your deposit ";
                                                                                $nrow['quoteId']="";
                                                                                $nrow['invoiceId']="";
                                                                                
                                                                                if($row['receipt']=="" || $row['receipt']==null){//which means that the receipt for this payment is still not generated
                                                                                    $receiptId=$row['id'];
                                                                                    $paymentUrl=urlencode($g_website.'/printReceipt.php?paymentId='.$receiptId);
                                                                                    runQuery("update darlelJobber_payments set receipt='$receiptId.pdf' where id='$receiptId'");
                                                                                    printPage(urldecode($paymentUrl),$receiptId);//storing the receipt in the uploads folder?>
                                                                                    <script>
                                                                                        window.location.href = "<?echo $g_website.'/view_client.php?id='.$customerId?>";
                                                                                    </script>
                                                                                    <?}?>
                                                                                <a href="#" data-bs-toggle="modal" data-bs-target="#send_email"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' class="btn btn-success btn-sm">Send PDF</a>
                                                                                <a target="_blank" class="btn btn-warning btn-sm" href="uploads/<?echo $row['receipt']?>">View PDF</a>
                                                                                <a href="./uploads/<?echo $row['receipt']?>" class="btn btn-primary btn-sm" download>Download Receipt</a>
                                                                                <a href="#" data-bs-toggle="modal" data-bs-target="#collect_payment" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">Edit</a>
                        										                <?if($session_role=="Admin"){?>
                        										                <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-payment=<?echo $row['id']?>&id=<?echo $userId?>" class="btn btn-danger btn-sm">Delete</a>
                        												        <?}?>
                        												    </div>
                        												</td>
                    										        </tr>
                    										        <?}?>
                    										    </tbody>
                    										</table>
                										
                										</div>
                                                    </div>
                                                    </div>
    					                        </div>
    					                    </div>
					                        <!--deposits finished-->
                                            
                                            
                                            
                                            
                                        </div>
                                    </div>
					                <!--left section finished-->
					                
					                
					                
					                
					                <!--<div class="col-xs-12 col-md-8">
				                    
				                    <div class="card card-flush" style="margin-top: 20px;">
									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
										<div class="card-title">
											<div class="d-flex align-items-center position-relative my-1">
											    Properties
											</div>
										</div>
										<div class="card-toolbar">
										    <?if($permission['edit_client']){?>
        									<a href="#" data-bs-toggle="modal" data-bs-target="#add_property" class="btn btn-primary btn-sm" id="addPropertyBtn">Add Property</a>
										    <?}?>
										</div>
									</div>
									<div class="card-body pt-0">
									    
									    <div class="table-responsive">
										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
										    <thead>
										        <tr>
										            <th>Street 1</th>
										            <th>Street 2</th>
										            <th>City</th>
										            <th>State</th>
										            <th>Zip Code</th>
										            <th>Country</th>
										            <th>Type</th>
										            <th>Actions</th>
										        </tr>
										    </thead>
										    <tbody>
										        <?$properties=getAll($con,"select * from darlelJobber_properties where userId='$userId' order by timeAdded desc");
										        foreach($properties as $row){
										        $googleMapAddress=$row['street1']." ".$row['street2']." ".$row['city']." ".$row['state']." ".$row['country'];?>
    										        <a id="editProperty_<?echo $row['id']?>" href="#" data-bs-toggle="modal" data-bs-target="#add_property" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="d-none">Edit</a>
                                                    <tr>
    										           <td><?echo $row['street1']?></td>
    										           <td><?echo $row['street2']?></td>
    										           <td><?echo $row['city']?></td>
    										           <td><?echo $row['state']?></td>
    										           <td><?echo $row['zip_code']?></td>
    										           <td><?echo $row['country']?></td> 
    										           <td><?echo $row['type']?></td> 
    										           <td>
    										               <div class="btn-group">
    										                   <a target="_blank" class="btn btn-primary btn-sm" href="https://www.google.com/maps/search/?api=1&query=<?echo $googleMapAddress?>">View</a>
											                   <?if($permission['edit_client']){?>
                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_property" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">Edit</a>
                                                                    <?if($row['type']!="primary"){?>
                                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-record-property=<?echo $row['id']?>&id=<?echo $userId?>" class="btn btn-danger btn-sm">Delete</a>
                                                                <?}}?>
                                                            </div>
    										           </td>
    												</tr>
										        <?}?>
										    </tbody>
										</table>
										</div>
									</div>
								    </div>
								    
								    
								    <div class="card card-flush" style="margin-top: 20px;">
    									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
    										<div class="card-title">
    											OVERVIEW
    										</div>
    										<div class="card-toolbar">
                                                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                                                    <li class="nav-item">
                                                        <a class="nav-link active" data-bs-toggle="tab" href="#requests" style="color: #73141d;">Requests</a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link " data-bs-toggle="tab" href="#quotes" style="color: #73141d;">Quotes</a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link " data-bs-toggle="tab" href="#jobs" style="color: #73141d;">Jobs</a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link " data-bs-toggle="tab" href="#invoices" style="color: #73141d;">Invoices</a>
                                                    </li>
                                                </ul>
                                            </div>
                                            </div>
                                            
                                            <div class="card-body pt-0">
                                                <div class="tab-content" id="myTabContent">
                                                    <div class="tab-pane fade active show" id="requests" role="tabpanel">
                                                    <div class="table-responsive">
            										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
            										        <thead>
                                                                <tr>
                                                                    <th>Title</th>
                                                                    <th>Assessment</th>
                													<th>Timing</th>
                                                                    <th>Address</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?$requests=getAll($con,"select * from darlelJobber_requests where request_for='$userId' order by timeAdded desc");
                                                                foreach($requests as $row){?>
                                                                <tr>
                                                                    <td><a href="createRequest.php?entryId=<?echo $row['id']?>&view=1"><?echo $row['title']?></a></td>
                                                                    <td><?echo date("d M Y",$row['start_date'])."-".date("d M Y",$row['end_date']);?></td>
                											        <td><?echo date("h:i A",strtotime($row['start_time']))."-".date("h:i A",strtotime($row['end_time']))?></td>
                											        <td>
                                                                        <?
                                                                        $property=$propertyDeets[$row['propertyId']];
                                                                        echo $property['street1']." ".$property['street2'].",".$property['city']."<br>".$property['state'].",".$property['country'];
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?$nrow=[
                                                                            "userDeetsId"=>$userId,
                                                                            "redirection"=>"request",
                                                                            "entryId"=>$row['id'],
                                                                            "hidePDF"=>1
                                                                        ];
                                                                        ?>
                                                                        <a href="#" data-bs-toggle="modal" 
                                                                        data-bs-target="#emailModal"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' 
                                                                        class="btn btn-success btn-sm">Send Via Email</a>
                                                                    </td>
                                                                </tr>
                                                                <?}?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="quotes" role="tabpanel">
                                                        <div class="table-responsive">
            										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th>Title</th>
                                                                    <th>Date</th>
                                                                    <th>Address</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?$quotes=getAll($con,"select * from darlelJobber_quotes where customerId='$userId' order by timeAdded desc");
                                                                foreach($quotes as $row){?>
                                                                <tr>
                                                                    <td><a href="createQuote.php?entryId=<?echo $row['id']?>&view=1"><?echo "#".$row['quote_number']." ".$row['title']?></a></td>
                                                                    <td><?echo date("d M y",$row['timeAdded'])?></td>
                                                                    <td>
                                                                        <?
                                                                        $property=$propertyDeets[$row['propertyId']];
                                                                        echo $property['street1']." ".$property['street2'].",".$property['city']."<br>".$property['state'].",".$property['country'];
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="btn-group">
                                                                            <?if($row['pdfSnapshot']!="None"){?>
                                                                            <a target="_blank" class="btn btn-warning btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>">View PDF</a>
                                                                            <a class="btn btn-primary btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>" download>Download PDF</a>
                                                                            <?}
                                                                            $nrow=[
                                                                                "userDeetsId"=>$userId,
                                                                                "redirection"=>"quote",
                                                                                "entryId"=>$row['id'],
                                                                            ];
                                                                            ?>
                                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#emailModal"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' class="btn btn-success btn-sm">Send PDF</a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?}?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="jobs" role="tabpanel">
                                                        <div class="table-responsive">
            										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th>Title</th>
                                                                    <th>Address</th>
                                                                    <th>Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?
                                                                $jobs=getAll($con,"select * from darlelJobber_jobs where customerId='$userId' order by timeAdded desc");
                                                                foreach($jobs as $row){?>
                                                                <tr>
                                                                    <td><a href="createJob.php?entryId=<?echo $row['id']?>&view=1"><?echo "#".$row['job_number']." ".$row['title']?></a></td>
                                                                    <td>
                                                                        <?
                                                                        $property=$propertyDeets[$row['propertyId']];
                                                                        echo $property['street1']." ".$property['street2'].",".$property['city']."<br>".$property['state'].",".$property['country'];
                                                                        ?>
                                                                    </td>
                                                                    <td><?echo $row['total']?></td>
                                                                </tr>
                                                                <?}?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="invoices" role="tabpanel">
                                                        <div class="table-responsive">
            										        <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Title</th>
                                                                        <th>Date</th>
                                                                        <th>Total</th>
                                                                        <th>Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?
                                                                    $invoices=getAll($con,"select * from darlelJobber_invoices where customerId='$userId' order by timeAdded desc");
                                                                    foreach($invoices as $row){?>
                                                                    <tr>
                                                                        <td><a href="createInvoice.php?entryId=<?echo $row['id']?>&view=1"><?echo "#".$row['invoice_number']." ".$row['subject']?></a></td>
                                                                        <td><?echo date("d M y",$row['payment_due'])?></td>
                                                                        <td><?echo $row['total']?></td>
                                                                        <td>
                                                                            <div class="btn-group">
                                                                            <?if($row['pdfSnapshot']!="None"){?>
                                                                            <a target="_blank" class="btn btn-warning btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>">View PDF</a>
                                                                            <a class="btn btn-primary btn-sm" href="uploads/<?echo $row['pdfSnapshot']?>" download>Download PDF</a>
                                                                            <?}
                                                                            $nrow=[
                                                                                "userDeetsId"=>$userId,
                                                                                "redirection"=>"invoice",
                                                                                "entryId"=>$row['id'],
                                                                            ];
                                                                            ?>
                                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#emailModal"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' 
                                                                            class="btn btn-success btn-sm">Send PDF</a>
                                                                        </div>
                                                                        </td>
                                                                    </tr>
                                                                    <?}?>
                                                                </tbody>
                                                                
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
    									</div>
    								
    								<div class="row">
					                        <div class="col-12">
        					                    <div class="card card-flush mt-20 mb-20">
            									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
            										<div class="card-title">
            											BILLING HISTORY
            										</div>
            										<div class="card-toolbar"></div>
            									</div>
            									<div class="card-body pt-0">
            									    <div class="table-responsive">
            										    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
            										    <thead>
            										        <tr>
            										            <th>Title</th>
            										            <th>Date</th>
            										            <th>Amount Paid</th>
            										            <th>Discount Availed</th>
            										            <th>Method</th>
            										            <th>Actions</th>
            										        </tr>
            										    </thead>
            										    <tbody>
            										        <?
            										        $payments=getAll($con,"select * from darlelJobber_payments where customerId='$customerId' order by transactionDate desc");
            										        foreach($payments as $row){
            										        $quoteDeets=$quoteIdToDeets[$row['quoteId']];
            										        $invoiceDeets=$invoiceIdToDeets[$row['invoiceId']];
            										        $withTitleText= ($row['quoteId']=="") ? " Invoice Payment " : " Quote Deposit ";
            										        ?>
            										        <tr>
            										            <td>
        										                    <?if($row['quoteId']!=""){?><a href="createQuote.php?entryId=<?echo $row['quoteId']?>&view=1"><?echo "#".$quoteDeets['quote_number']." ".$quoteDeets['title']?></a><?}?>    
        										                    <?if($row['invoiceId']!=""){?><a href="createInvoice.php?entryId=<?echo $row['invoiceId']?>&view=1"><?echo "#".$invoiceDeets['invoice_number']." ".$invoiceDeets['subject']?></a><?}?>    
        										                    <?echo $withTitleText.$row['title']?>
            										            </td>
            										            <td><?echo date("d M y",$row['transactionDate'])?></td>
            										            <td><?echo "$".$row['amountPaid']?></td>
            										            <td><?echo "$".$row['discountAvailed']?></td>
            										            <td><?echo $row['method']?></td>
            										            <td>
            										                <div class="btn-group">
                										                <?$row['transactionDate']=date("Y-m-d",$row['transactionDate']);
                										                $nrow=[];
        										                        $nrow['receiptId']=$row['id'];
                                                                        $nrow['title']="Vilo Fence Deposit Receipt";
                                                                        $nrow['description']="Thank You for choosing Vilo Fence . Kindly find the attached receipt of your deposit ";
                                                                        $nrow['quoteId']="";
                                                                        $nrow['invoiceId']="";
                                                                        
                                                                        if($row['receipt']=="" || $row['receipt']==null){//which means that the receipt for this payment is still not generated
                                                                            $receiptId=$row['id'];
                                                                            $paymentUrl=urlencode($g_website.'/printReceipt.php?paymentId='.$receiptId);
                                                                            runQuery("update darlelJobber_payments set receipt='$receiptId.pdf' where id='$receiptId'");
                                                                            printPage(urldecode($paymentUrl),$receiptId);//storing the receipt in the uploads folder?>
                                                                            <script>
                                                                                window.location.href = "<?echo $g_website.'/view_client.php?id='.$customerId?>";
                                                                            </script>
                                                                            <?}?>
                                                                        <a href="#" data-bs-toggle="modal" data-bs-target="#send_email"  data-mydata='<?echo htmlspecialchars(json_encode($nrow), ENT_QUOTES, 'UTF-8');?>' class="btn btn-success btn-sm">Send PDF</a>
                                                                        <a target="_blank" class="btn btn-warning btn-sm" href="uploads/<?echo $row['receipt']?>">View PDF</a>
                                                                        <a href="./uploads/<?echo $row['receipt']?>" class="btn btn-primary btn-sm" download>Download Receipt</a>
                                                                        <a href="#" data-bs-toggle="modal" data-bs-target="#collect_payment" data-mydata='<?echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');?>' class="btn btn-warning btn-sm">Edit</a>
                										                <?if($session_role=="Admin"){?>
                										                <a href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-payment=<?echo $row['id']?>&id=<?echo $userId?>" class="btn btn-danger btn-sm">Delete</a>
                												        <?}?>
                												    </div>
                												</td>
            										        </tr>
            										        <?}?>
            										    </tbody>
            										</table>
            									    </div>
            									</div>
            								    </div>
            					                            
					                        </div>
					                    </div>
					                	
    								</div>
									
									<div class="col-xs-12 col-md-4" >
					                    <div class="row">
					                        <div class="col-12">
        					                        <?//require("./notes/notes_table.php");?>
											</div>
											
											
											<div class="col-12">
											    <div class="card card-flush mb-20">
											        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                										<div class="card-title">
                											<div class="d-flex align-items-center position-relative my-1">
                											    Tasks
                											</div>
                										</div>
                										<div class="card-toolbar"></div>
                									</div>
                									<div class="card-body pt-0">
                									    <table class="table table-rounded table-striped border  gs-7 dataTable text-center">
        											        <thead>
        											            <tr>
        											                <th>Title</th>
                										            <th>Completion Date</th>
                										            <th>Actions</th>
                										        </tr>
        											        </thead>
        											        <tbody>
        											            <?
                										        $query="select * from darlelJobber_tasks where customerId='$customerId' 
                										        or quoteId in (select q.id from darlelJobber_quotes q where q.customerId='$customerId')";
                                                                $tasks=getAll($con,$query);
                											    foreach($tasks as $row){
                											    ?>
        											            <tr>
        											                <td style="text-align: center;">
                										                <?$colorStatus= ($row['status']=="Completed") ? "success" : "warning";
                										                echo $row['title']."<br>";?>
            										                    <a class="badge badge-<?echo $colorStatus?> btn-sm" style="margin-left: 5px;"><?echo $row['status']?></a>
            										                    <a class="badge badge-primary btn-sm" style="margin-left: 5px;background-color:<?echo $labelNameToColor[$row['label']]?>!important"><?echo $row['label']?></a>
        										                    </td>
        										                    <td><?echo date("d M y",$row['completionDate'])?></td>
        										                    <td>
        										                        <a href="detailedTaskView.php?taskId=<?echo $row['id']?>" class="btn btn-success btn-sm">View</a>
        									                        </td>
        											            </tr>
        											            <?}?>
        											        </tbody>
        											    </table>
											        </div>
											    </div>
											</div>
					                    </div>
					                </div>
					                -->
					                
					                
				                </div>
					            </div>
					            
					        </div>
					    </div>
					</div>
					
					
					
					
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
		</div>
	</body>
	
	
	
	<!--property modal-->
	
	<div class="modal fade" id="add_property" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered mw-650px" style="max-width:1000px !important;">
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
							<div class="row">
							    
    							<div class="col-xs-12 col-sm-12 col-lg-12">
        							<div class="mb-13 text-left">
        							    <h1 class="mb-3" id="modelTitle"></h1>
        							</div>
    							</div>
							</div>
							<div class="row g-9 mb-8">
								<div class="col-md-12 fv-row">
									<input onchange="fillPropertyDetails()"  type="text" class="form-control form-control-solid"  placeholder="Street 1" name="street1" id="from" />
								</div>
								<div class="col-md-12 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Street 2" name="street2" />
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="City" name="city" />
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="State" name="state" />
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Zip Code" name="zip_code" id="zip_code"/>
								</div>
								<div class="col-md-6 fv-row">
									<input  type="text" class="form-control form-control-solid" placeholder="Country" name="country" />
								</div>
							</div>
						    <input type="text" name="actionIdProperty" hidden>
						    <input type="text" name="type" hidden>
							<div class="text-center">
								<input  type="submit" value="Save" name="addProperty" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		<script>
	    $(document).ready(function(){
	        $(".dataTable").DataTable({
              "ordering": false,
              "info": false
            });

        $("#add_property").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            
            if(mydata!= null){
            	$("#modelTitle").html("Update Property Details");
            	
                $("input[name='company_name']").val(mydata['company_name'])  
                $("input[name='street1']").val(mydata['street1'])
                $("input[name='type']").val(mydata['type'])
                $("input[name='street2']").val(mydata['street2'])
                $("input[name='city']").val(mydata['city'])
                $("input[name='state']").val(mydata['state'])
                $("input[name='zip']").val(mydata['zip_code'])
                $("input[name='country']").val(mydata['country'])
                $("input[name='actionIdProperty']").val(mydata['id'])
           
            }else{
            	$("#modelTitle").html("Add Property");
            	$("input[name='street1']").val("")
            	$("input[name='type']").val("")
                $("input[name='street2']").val("")
                $("input[name='city']").val("")
                $("input[name='state']").val("")
                $("input[name='zip']").val("")
                $("input[name='country']").val("")
                $("input[name='actionIdProperty']").val("")
            }
        });
	    });
        
        function fillPropertyDetails(){
        
            var address = $("input[name='street1']").val();
            var addressPattern = /^(.*?)(?:, (.*?))?(?:, (.*?))?(?:, (.*?))?$/;
            var matches = address.match(addressPattern);
            var street1 = matches[1].trim();
            var street2 = matches[2] ? matches[2].trim() : "";
            var city = matches[3] ? matches[3].trim() : "";
            var country = matches[4] ? matches[4].trim() : "";
            
            $("input[name='street1']").val(street1);
            $("input[name='street2']").val(street2);
            $("input[name='city']").val(city);
            $("input[name='country']").val(country);
        
        }

	</script>
	
	
	<!--tag modal-->
	
	<!--tag modal ending-->
	
	
	<!--collect payment modal-->
	<div class="modal fade" id="collect_payment" tabindex="-1" aria-hidden="true">
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
                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-lg-12">
                                    <div class="mb-13 text-left">
                                        <h1 class="mb-3" id="modelTitlePayment"></h1>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-9 mb-8">
                                <div class="col-md-12 fv-row">
                                    <label>Title</label>
                                    <input type="text" class="form-control form-control-solid" placeholder="Enter Title" name="title" />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Method</label>
                                    <select class="form-control" name="method">
                                        <?$options=array("Cash","E Check","Credit Card","Bank Transfer","Money Order","Check","Other");
                                        foreach($options as $row){?>
                                            <option value="<?echo $row?>"><?echo $row?></option>
                                        <?}?>
                                    </select>
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Amount</label>
                                    <input type="number" class="form-control form-control-solid" placeholder="Enter Amount" name="amountPaid" step="0.01" >
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Discount Availed</label>
                                    <input type="number" step="0.01" class="form-control form-control-solid" placeholder="Enter Discount Availed" name="discountAvailed" min="0" />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Transaction Date</label>
                                    <input type="date" class="form-control form-control-solid" name="transactionDate" />
                                </div>
                                <div class="col-md-12 fv-row">
                                    <label>Description</label>
                                    <textarea class="form-control" name="description" rows="4"></textarea>
                                </div>
                            </div>
                            <input type="text" name="paymentId" hidden>
                            <div class="text-center">
                                <input  type="submit" value="Save Changes" name="collectPayment" class="btn btn-primary">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <script>

        $(document).ready(function(){
            
        $("#collect_payment").on('show.bs.modal', function (e) {
            var mydata = $(e.relatedTarget).data('mydata');
            
            if(mydata!= null){
                $("#modelTitlePayment").html("View Payment Details");
                $("input[name='title']").val(mydata['title'])  
                $("select[name='method']").val(mydata['method'])  
                $("input[name='amountPaid']").val(mydata['amountPaid'])  
                $("input[name='discountAvailed']").val(mydata['discountAvailed'])  
                $("input[name='transactionDate']").val(mydata['transactionDate'])  
                $("textarea[name='description']").val(mydata['description'])
                $("input[name='paymentId']").val(mydata['id'])
            }else{
                $("#modelTitlePayment").html("Add Payment");
                $("input[name='title']").val("")  
                $("select[name='method']").val("")  
                $("input[name='amountPaid']").val("0")
                $("input[name='discountAvailed']").val("0")
                
                var today_date = moment().format('YYYY-MM-DD');
                $("input[name='transactionDate']").val(today_date)  
                $("textarea[name='description']").val("")
                $("input[name='paymentId']").val("")
            }
        });
        })
    </script>
    <!--collect payment modal-->
	

    <!--send email notification modal-->
    
    <div class="modal fade" id="send_email" tabindex="-1" aria-hidden="true">
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
                                <div class="row">
                                    <div class="col-9">
                                        <h1 class="mb-3">Email Client</h1>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row">
                                <label class="d-flex align-items-center fs-6 fw-bold mb-2">
                                    <span class="required">Title</span>
                                </label>
                                <input type="text" name="title" class="form-control" placeholder="Enter Title" required>
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row">
                                <label class="d-flex align-items-center fs-6 fw-bold mb-2">
                                    <span class="required">Description</span>
                                </label>
                                <textarea class="form-control" name="description" rows="10" placeholder="Enter Description" required></textarea>
                            </div>
                            <input type="text" name="receiptId" hidden>
                            <input type="text" name="quoteId" hidden>
                            <input type="text" name="invoiceId" hidden>
                            <div class="text-center">
                                <input type="submit" value="Send Email" name="sendEmail" class="btn btn-primary">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <!--send email jquery-->
    <script>
        $(document).ready(function(){
            $("#send_email").on('show.bs.modal', function (e) {
                var data = $(e.relatedTarget).data('mydata');
                var title=data['title'];
                var description=data['description'];
                
                console.log(data);
                $("input[name='title']").val(title);
                $("textarea[name='description']").val(description);
                $("input[name='receiptId']").val(data['receiptId']);
                $("input[name='quoteId']").val(data['quoteId']);
                $("input[name='invoiceId']").val(data['invoiceId']);
            });
            
            /*means that the client wanted to add property from quotes page*/
            <?if($_GET['action']=="addPropertyFromQuote" || $_GET['action']=="addPropertyFromRequest"){?>
                $("#addPropertyBtn")[0].click();
            <?}?>
            
            
            <?if($_GET['action']=="editPropertyFromQuote" || $_GET['action']=="editPropertyFromRequest"){?>
                $("#editProperty_<?echo clear($_GET['propertyId'])?>")[0].click();
            <?}?>
            
	    });
    </script>


    <script>
        var geocoder;
        var map;
        
        function initialize() {
          
          var input = document.getElementById('from');
          if(input=="")
            return false;
          var options = {
            types: ['address'],
          };
          autocomplete = new google.maps.places.Autocomplete(input, options);
          google.maps.event.addListener(autocomplete, 'place_changed', function() {
            var place = autocomplete.getPlace();
            for (var i = 0; i < place.address_components.length; i++) {
              for (var j = 0; j < place.address_components[i].types.length; j++) {
                if (place.address_components[i].types[j] == "postal_code") {
                  var zip=place.address_components[i].long_name;
                  $('#zip_code').val(zip)
                  //document.getElementById('postal_code').innerHTML = place.address_components[i].long_name;
        
                }
              }
            }
          })
        }
        google.maps.event.addDomListener(window, "load", initialize);

    </script>
	<?
	require("./notes/notes_js.php");
	require("./emailsAndSms/multipleSmsModal.php");
	require("./emailsAndSms/multipleEmailModal.php");
	?>
</html>