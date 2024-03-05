<?
require("./global.php");
if($logged==0 || (!$permission['view_requests']))
    header("Location:./index.php");
    
if(!$permission['edit_requests'] && (!isset($_GET['view'])) && (isset($_GET['entryId']))){
    $requestId=clear($_GET['entryId']);
    header("Location:?m=Request Data Has Been Updated Successfully&entryId=$requestId&view=1");
    exit();
}

$client=0;
$edit=0;
$view=0;
$new=0;
$issetEntryId=0;

$idToContactDetails=[];

$allContacts=getAll($con,"select * from darlelJobber_contacts");
$allContactDetails=getAll($con,"select * from darlelJobber_contact_details cd inner join darlelJobber_contacts c on cd.contactId=c.id ");
foreach($allContactDetails as $row){
    $contactId = $row["contactId"];
    $value = $row["value"];
    
    if (isset($idToContactDetails[$contactId])) {
        $idToContactDetails[$contactId][] = $value;
    } else {
        $idToContactDetails[$contactId] = [$value];
    }
}

if($session_role=="Client")
    $client=1;
if(isset($_GET['view']))
    $view=1;
if((isset($_GET['entryId'])) && (!isset($_GET['view'])))
    $edit=1;
if(isset($_GET['entryId']))
    $issetEntryId=1;
if(!isset($_GET['entryId']))
    $new=1;

$checkNew = (isset($_GET['new'])) ? 1 : 0;

$leadSources=getAll($con,"select * from darlelJobber_lead_source order by timeAdded desc");
$users=getAll($con,"select * from darlelJobber_users where role!='Client'");
$requestId=clear($_GET['entryId']);

$clients=getAll($con,"select * from darlelJobber_users where role='Client'");
foreach($clients as $row)
{$idToInfo[$row['id']]=$row;}

$idToInfoUsers=array("");
$allusers=getAll($con,"select * from darlelJobber_users");
foreach($allusers as $row)
{$idToInfoUsers[$row['id']]=$row;}

$properties=getAll($con,"select * from darlelJobber_properties");
$timeAdded=time();

if((isset($_POST['addRequest'])) || (isset($_POST['convertQuote'])) || (isset($_POST['convertJob'])) || (isset($_POST['sendEmail'])) || 
(isset($_POST['sendSms'])) || (isset($_POST['quickConvert']))){
    
    $request_for=clear($_POST['request_for']);
    $contactId=clear($_POST['contactId']);
    $title=clear($_POST['title']);
    $propertyId=clear($_POST['propertyId']);
    $service_details=clear($_POST['service_details']);
    $requested_on=clear($_POST['requested_on']);
    $start_date=clear($_POST['start_date']);$start_date=strtotime($start_date);
    $end_date=clear($_POST['end_date']);$end_date=strtotime($end_date);
    $start_time=clear($_POST['start_time']);
    $end_time=clear($_POST['end_time']);
    $actionId=clear($_POST['actionId']);
    $leadSourceId=clear($_POST['leadSourceId']);
    
    $id=generateRandomString();
    $scheduleStatus="Schedule Now";
    
    if((!isset($_POST['quickConvert']))){//meaning that either the request is created or edited but not converted
        
        if($actionId==""){
            $query="insert into darlelJobber_requests set id='$id',contactId='$contactId',leadSourceId='$leadSourceId',request_for='$request_for',title='$title',propertyId='$propertyId',service_details='$service_details',requested_on='$requested_on',
            start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',scheduleStatus='$scheduleStatus',timeAdded='$timeAdded',addedBy='$session_id'";
            $actionId=$id;
            $requestId=$id;
        }
        else{
            $requestId=$actionId;
            $query="update darlelJobber_requests set request_for='$request_for',contactId='$contactId',leadSourceId='$leadSourceId',title='$title',propertyId='$propertyId',service_details='$service_details',requested_on='$requested_on',
            scheduleStatus='$scheduleStatus',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time' where id='$actionId'";
        }
        runQuery($query);
    
        //adding team members for this request
        $newteam=$_POST['team'];
        $oldteamfetch=getAll($con,"select userId from darlelJobber_teams where requestId='$requestId'");
        $oldteam=[];
        foreach($oldteamfetch as $row){
            $oldteam[]=$row['userId'];
        }
        
        foreach($users as $row){
            $userId=$row['id'];
            if((in_array($userId,$newteam)) && (!in_array($userId,$oldteam))){
                $id=generateRandomString();
                $query="insert into darlelJobber_teams set id='$id',requestId='$actionId',userId='$userId',timeAdded='$timeAdded'";
                runQuery($query);
        
                $title="Assigned To a Request";
                $description="You have been added as a member in a request . Click To View";
                $url=$projectUrl."createRequest.php?entryId=$actionId";
                //setNotification($title,$description,$userId,$url);
                //echo "adding".$idToInfoUsers[$userId]['name']."<br>"; 
            }
            else if((!in_array($userId,$newteam)) && (in_array($userId,$oldteam))){
                $query="delete from darlelJobber_teams where userId='$userId' && requestId='$actionId'";
                runQuery($query);
                //echo "removing".$idToInfoUsers[$userId]['name']."<br>"; 
            }
        }
        //adding team members finished
    }
    
   if((isset($_POST['convertQuote'])) || (isset($_POST['quickConvert']))){
        $quoteId=generateRandomString();
        $quote_number=getRow($con,"select quote_number from darlelJobber_quotes order by timeAdded desc")['quote_number']+1;
        
        $requestId=$actionId;
        $query="select userId from darlelJobber_teams where requestId='$requestId' && userId in (select id from darlelJobber_users where role='Estimator')";
        $estimatorId=getRow($con,$query)['userId'];

        /*if($session_role=="Estimator")
            $estimatorId=$session_id;
        */
        $query="insert into darlelJobber_quotes set id='$quoteId',viewedByEstimator='No',contactId='$contactId',startTimer='$timeAdded',estimatorId='$estimatorId',quote_number='$quote_number',title='$title',customerId='$request_for',propertyId='$propertyId',
        timeAdded='$timeAdded',addedBy='$session_id',requestId='$actionId'";
        runQuery($query);
        
        //update notes section means that ke jo request ke notes the woh ab quotes ke notes bhi hoonge
        $query="update darlelJobber_notes set quoteId='$quoteId' where requestId='$actionId'";
        runQuery($query);
        
        $random=generateRandomString();
        $query="insert into darlelJobber_quote_details set id='$random',quoteId='$quoteId',service='Labor Fees',qty='1',unit_price='250',total='250',type='Labor Fees'";
        runQuery($query);
        
        updateQuote($quoteId);
        
        //inserting this quoteId in requests to make the chain
        $query="update darlelJobber_requests set convertStatus='Converted',quoteId='$quoteId' where id='$actionId'";
        runQuery($query);
        
        //creating the forms for that quote as soon as the quote is created 
        $forms=["A","B","C","D"];
        foreach($forms as $row){
            $random=random();
            $query="insert into darlelJobber_form$row set id='$random',quoteId='$quoteId',clientId='$request_for',timeAdded='$timeAdded'";
            runQuery($query);
        }
        
        header("Location:./createQuote.php?entryId=$quoteId");
        exit();
    }
    else if(isset($_POST['sendEmail'])){
        header("Location:?entryId=$actionId&triggerModal=email");
    }
    else if(isset($_POST['sendSms'])){
        header("Location:?entryId=$actionId&triggerModal=sms");
    }
    else
        header("Location:?m=Request Data Has Been Updated Successfully&entryId=$actionId");
}




$entryId=clear($_GET['entryId']);
$requestDeets=getRow($con,"select * from darlelJobber_requests where id='$entryId'");

$userDeetsId=$requestDeets['request_for'];
$propertyDeetsId=$requestDeets['propertyId'];

if(isset($_GET['customerId'])){
    $userDeetsId=clear($_GET['customerId']);
    $propertyDeetsId=getRow($con,"select * from darlelJobber_properties where userId='$userDeetsId'");
    $propertyDeetsId=$propertyDeetsId['id'];
}

$userDeets=getRow($con,"select * from darlelJobber_users where id='$userDeetsId'");
$userPhones=explode("*",$userDeets['phone']);
$userEmails=explode("*",$userDeets['email']);
$propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");


//for client request addition
if(isset($_POST['addRequestClient'])){
    
    $request_for=clear($_POST['request_for']);
    $propertyId=clear($_POST['propertyId']);
    $service_details=clear($_POST['service_details']);
    $requested_on=clear($_POST['requested_on']);
    $start_date=time();
    $end_date=time();
    $start_time=date("H:i",time());
    $end_time=date("H:i",time());
    $actionId=clear($_POST['actionId']);
    $id=generateRandomString();
    $timeAdded=time();
    if($actionId==""){
        $query="insert into darlelJobber_requests set id='$id',sendStatus='Pending',request_for='$request_for',propertyId='$propertyId',service_details='$service_details',requested_on='$requested_on',
        start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',timeAdded='$timeAdded',addedBy='$session_id'";
        $actionId=$id;
    }
    else{
        $query="update darlelJobber_requests set request_for='$request_for',propertyId='$propertyId',service_details='$service_details',requested_on='$requested_on',
        start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time' where id='$actionId'";
    }
    runQuery($query);
    header("Location:./createRequest.php?m=Request Data Has Been Updated Successfully&entryId=$actionId");
}


//activating deactivating appointment on request
if(isset($_GET['activateAppointment'])){
    runQuery("update darlelJobber_requests set appointmentStatus='Active' where id='$requestId'");
    header("Location:?entryId=$requestId&m=Appointment has been activated successfully");
}
if(isset($_GET['disableAppointment'])){
    runQuery("update darlelJobber_requests set appointmentStatus='Not Active' where id='$requestId'");
    header("Location:?entryId=$requestId&m=Appointment has been disabled successfully");
}

require("./emailsAndSms/sendingSms.php");
require("./emailsAndSms/sendingEmail.php");
require("./notes/notes.php");
?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
		
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body <?if($shrinkMenu){echo $shrinkMenuAttr;}?> id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	    <div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<?require("./includes/views/leftmenu.php");?>
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<?require("./includes/views/topmenu.php");?>
					
					
					<div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;" id="kt_content">
						
						<div class="post d-flex flex-column-fluid" id="kt_post">
							<div id="kt_content_container" class="container-xxl">
								<?if(isset($_GET['m'])){ $m=clear($_GET['m']);?>
                                <div class="alert alert-dismissible bg-success d-flex flex-column flex-sm-row w-100 p-5 mb-10">
                                    <div class="d-flex flex-column text-light pe-0 pe-sm-10">
                                        <h4 class="mb-2 light" style="color: white;margin-top: 5px;"><?echo $m?></h4>
                                    </div>
                                </div>
                                <?}?>
                                <form action="" method="post" enctype="multipart/form-data" id="requestForm">
									
									<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
										<div class="row">
										    <div class="col-12">
        										<ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-bold mb-n2">
        											<?if(!$new){?>
        											<li class="nav-item">
        												<a href="createRequest.php?entryId=<?echo $requestId?>&view=1" class="nav-link text-active-primary pb-4 active">Request</a>
        											</li>
        											<?}?>
        											<?if((!$new && ($requestDeets['quoteId']!="None")) && ($permission['view_quotes'])){?>
        											<li class="nav-item">
        												<a href="createQuote.php?entryId=<?echo $requestDeets['quoteId']?>&view=1" class="nav-link text-active-primary pb-4 ">Quote</a>
        											</li>
        											<?}?>
        											<?if((!$new && ($requestDeets['jobId']!="None")) && ($permission['view_jobs'])){?>
        											<li class="nav-item">
        												<a href="createJob.php?entryId=<?echo $requestDeets['jobId']?>&view=1" class="nav-link text-active-primary pb-4 ">Job</a>
        											</li>
        											<?}?>
        											<?if((!$new && ($requestDeets['invoiceId']!="None")) && ($permission['view_invoices'])){?>
        											<li class="nav-item">
        												<a href="createInvoice.php?entryId=<?echo $requestDeets['invoiceId']?>&view=1" class="nav-link text-active-primary pb-4 ">Invoice</a>
        											</li>
        											<?}?>
        											<?if(!$new && $permission['view_client']){?>
        											<li class="nav-item">
        												<a href="view_client.php?id=<?echo $requestDeets['request_for']?>" class="nav-link text-active-primary pb-4 ">View Client</a>
        											</li>
        											<?}?>
        										</ul>
    										</div>
    									</div>
										<div class="tab-content">
											<div class="tab-pane fade active show" role="tab-panel">
												<div class="d-flex flex-column gap-7 gap-lg-10">
													<div class="card card-flush py-4">
														<div class="card-header">
															<div class="card-title">
																<h2>Request For
											                        <?if($session_role!="Client"){?>
											                        <a href="#" <?if(!$view){?> data-bs-toggle="modal" data-bs-target="#client_name" <?}?> >
											                            <p style="display: inline;color: #73141d;" id="clientName">
											                                <?
											                                if(($issetEntryId) || (isset($_GET['customerId']))){
											                                    if($userDeets['showCompanyName']=="Yes")
                                    								                echo $userDeets['company_name']." (".$userDeets['first_name']." ".$userDeets['last_name'].")";
                                                                                else
											                                        echo $userDeets['first_name']." ".$userDeets['last_name'];
                                    									    } 
											                                else
											                                    echo "Client Name";?>
											                            </p>
											                        </a>
										                            <?}else if($client)
										                                echo $session_data['first_name']." ".$session_data['last_name'];
										                            ?>
										                        </h2>
											                    <?
											                    if($client)
											                        $request_for=$session_id;
											                    else
											                        $request_for=$requestDeets['request_for'];
											                    ?>
											                    <input type="text" name="request_for" value="<?echo $request_for?>" hidden>
															    <input type="text" name="contactId" value="<?echo $requestDeets['contactId']?>" hidden>
															</div>
															<div class="card-toolbar d-flex align-items-center">
										                        
										                        
									                            <?if($session_role!="Client" && $requestDeets['convertStatus']!="Converted" && $view){?>
									                                <input type="submit" class="btn btn-primary  me-2"  name="quickConvert" value="Quick Convert">
    														    <?}?>
										                        
										                        <?if($permission['edit_requests'] && (!$new) && $view){?>
										                            <a class="btn btn-warning  me-2" href="./createRequest.php?entryId=<?echo $entryId?>">Edit Request</a>
									                            <?}?>
									                            
										                        <?if($session_role!="Client"){?>
										                        
										                        <?if($requestDeets['appointmentStatus']=="Not Active" && ($edit)){?>
										                        <a class="btn btn-success  me-2"  href="?entryId=<?echo $requestId?>&activateAppointment=1">Activate Appointment</a>
										                        <?}else if($requestDeets['appointmentStatus']=="Active" && ($edit)){?>
										                        <a class="btn btn-warning  me-2"  href="?entryId=<?echo $requestId?>&disableAppointment=1">Disable Appointment</a>
										                        <?}?>
										                        <div class="d-inline-flex">
										                        <select name="leadSourceId" class="form-select" style="margin-right:10px;" data-control="select2" data-placeholder="Select Lead Source">
                                                                    <?foreach($leadSources as $row){?>
                                                                    <option <?if($requestDeets['leadSourceId']==$row['id'] || ($new && $row['title']=="None")){echo "selected";}?>
                                                                    value="<?echo $row['id']?>"><?echo "Lead Source : ".$row['title']?></option>
                                                                    <?}?>
                                                                </select>
                                                                </div>
										                        <?}?>
										                        
									                            
										                    </div>
														</div>
														<div class="card-body pt-0">
															<?if(!$client){?>
															<div class="mb-10 fv-row fv-plugins-icon-container">
																<label class="required form-label">Request Title</label>
																<input type="text" name="title" class="form-control mb-2" value="<?echo ($new) ? "Request" : $requestDeets['title'] ; ?>" placeholder="Enter Request Title" required >
														    </div>
														    <?}?>
														    <div class="row text-center">
														        <div class="col-md-3 col-6">
														            <h4>Property Address</h4>
														            <p id="street1"></p>
														            <p id="street2"></p>
														            <p id="city"></p>
														            <p id="state"></p>
														            <p id="zip_code"></p>
														            <p id="country"></p>
														            <input type="text" name="propertyId" value="<?echo $requestDeets['propertyId']?>" hidden>
														            <div id="change_property">
														                <?if((($issetEntryId) || (isset($_GET['customerId']))) && (!$view) ){?>
													                        <a style="margin-right: 10px;" onclick="editProperty()" href="#">Edit</a>
														                    <a id="changePropertyBtn" href="#" data-bs-toggle="modal" data-bs-target="#changeProperty">Change</a>
														                <?}?>
														            </div>
														            
														            <?//google map address button when request is viewed
														            if($session_role!="Client" && $view){
														                $propertyDeetsId=$requestDeets['propertyId'];
														                $propertyDeets=getRow($con,"select * from darlelJobber_properties where id='$propertyDeetsId'");
                                                                        $zipCode = ($propertyDeets['zip_code']!="") ? " (Zip Code : ".$propertyDeets['zip_code'].")" : "";
                                                                        $googleMapAddress=$propertyDeets['street1']." ".$propertyDeets['street2']." ".$propertyDeets['city']." ".$propertyDeets['state']." ".$propertyDeets['country'];?>
										                            <a target="_blank" class="btn btn-warning " href="https://www.google.com/maps/search/?api=1&query=<?echo $googleMapAddress?>">View Address</a>
										                            <?}?>
														        </div>
														        <div class="col-md-3 col-6">
														            <h4>Contact Details</h4>
														            <p id="accountContactDetails"></p>
														        </div>
														        <div class="col-md-3 col-6">
														            <h4>
														                Account Details
														                <?if($edit){?>
													                    <a onclick="editContactDetails()" href="#">Edit</a>
													                    <?}?>
														            </h4>
														            <div id="contactDetails"></div>
														        </div>
														        <div class="col-md-3 col-6">
														            <h4>Requested On</h4>
														            <p><?$requestedOn = ($new) ? time() : $requestDeets['requested_on'];echo date("d M Y",$requestedOn);
													                if($view)
													                    echo " ".date("h:i A",strtotime($requestDeets['start_time']))." - ".date("h:i A",strtotime($requestDeets['end_time']));
														            ?></p>
														            <input type="text" value="<?echo $requestedOn;?>" name="requested_on" hidden>
														        </div>
														    </div>
														    <div class="row">
														        <div class="col-12">
														            <h3 style="margin-bottom: 10px;margin-top: 20px;">Service Details</h3>
														            <div class="form-group">
														                <label style="margin-bottom: 10px;margin-top: 20px;">Please provide as much information as you can</label>
														                <textarea class="form-control" rows="5" name="service_details" placeholder="Enter Service Details"><?echo $requestDeets['service_details']?></textarea>
														            </div>
														        </div>
													        </div>
													        <?if(!$view){?>
												                <div class="row">
														        
														        <?if(!$client){?>
														        <div>
														            <div class="row">
														                    <?if($edit){
														                        $start_date = $requestDeets['start_date'];
														                        $start_time = $requestDeets['start_time'];
														                        $end_date = $requestDeets['end_date'];
														                        $end_time = $requestDeets['end_time'];
														                    }else if(isset($_GET['start'])){
														                        $start_date = strtotime($_GET['start']);
														                        $end_date = strtotime($_GET['start']);
													                            $start_time = date("H:i",strtotime($_GET['start']));
														                        $end_time = date("H:i",strtotime($_GET['end']));
														                    }else{
														                        $time=time();
														                        $start_date = $time;
														                        $end_date = $time;
													                            $start_time = "08:00";
														                        $end_time = "10:00";
														                    }
													                        ?>
    														                <div class="col-md-6 col-12">
    														                    <label style="margin-bottom: 10px;margin-top: 20px;">Start Date</label>  
        														                <input type="date" name="start_date" value ="<?php echo date('Y-m-d',$start_date)?>" class="form-control" required>
    														                </div>
    														                <div class="col-md-6 col-12">
                                                                                <label style="margin-bottom: 10px;margin-top: 20px;">End Date</label>  
        														                <input type="date" name="end_date" value ="<?php echo date('Y-m-d',$end_date)?>" class="form-control" required>
    														                </div>
														                
														                    <div class="col-md-6 col-12">
    														                    <label style="margin-bottom: 10px;margin-top: 20px;">Start Time</label>  
        														                <input type="time" value="<?echo $start_time?>" name="start_time" class="form-control" required>
    														                </div>
    														                <div class="col-md-6 col-12">
    														                    <label style="margin-bottom: 10px;margin-top: 20px;">End Time</label>  
        														                <input type="time" value="<?echo $end_time?>" name="end_time" class="form-control" required>
    														                </div>
    														                <div class="col-12">
    														                    <div class="row" style="margin-bottom: 10px;margin-top: 20px;">
    														                        <div class="col-3">
    														                            <h3>Team</h3>
    														                        </div>
    														                        <div class="col-9" style="text-align:right;">
    														                            <?if(!$view){?>
    														                                <a href="#" data-bs-toggle="modal" data-bs-target="#add_team" class="btn btn-primary btn-sm">Assign</a>
														                                <?}?>
                                                                                    </div>
    														                    </div>
    														                    <div class="row">
    														                        <div id="team_member_area" class="col-12">
    														                            <?
    														                            if(!$issetEntryId)
    														                                $entryId="Nones";
    														                            $query="select * from darlelJobber_teams where requestId='$entryId'";
    														                            $teams=getAll($con,$query);
    														                            $random=generateRandomString();
    														                            foreach($teams as $row){?>
    														                            <p id="<?echo $random?>" class="btn btn-light-success btn-sm" style="margin-right:3px;">
    														                                <?echo $idToInfoUsers[$row['userId']]['name']." (".$idToInfoUsers[$row['userId']]['role'].")"?>
                                                                					        <a onclick="removeMember('<?echo $random?>')" style="margin-left: 10px;color: red;">X</a>
                                                                					        <input type="text" name="team[]" value="<?echo $row['userId']?>" hidden>
                                                                					    </p>
                                                                					    <?}?>
    														                        </div>
    														                    </div>
    														                </div>
    														        </div>
														        </div>
													            <?}?>
													            
													            <!--notes section-->
													            <?if(($issetEntryId && (!$client))){?>
													                <?include("./notes/notes_table.php");?>
												                <?}?>
													        
													        </div>
														    <?}else{?>
														        <div class="row text-center">
														            <h3 class="mt-4">Appointment Details</h3>
														            <div class="col-md-3 col-sm-6 col-12"><h4 class="mt-4">Start Date : <?echo date("d-M-y",$requestDeets['start_date'])?></h4></div>
														            <div class="col-md-3 col-sm-6 col-12"><h4 class="mt-4">End Date : <?echo date("d-M-y",$requestDeets['end_date'])?></h4></div>
														            <div class="col-md-3 col-sm-6 col-12"><h4 class="mt-4">Start Time : <?echo date("h:i A",strtotime($requestDeets['start_time']))?></h4></div>
														            <div class="col-md-3 col-sm-6 col-12"><h4 class="mt-4">End Time : <?echo date("h:i A",strtotime($requestDeets['end_time']))?></h4></div>
														        </div>
														        
														        <?if($session_role=="Admin"){?>
														            <div class="row">
    														            <h3 class="mt-10 mb-6">Employees Assigned</h3>
    														            <div class="row">
    														                <div class="col-12">
    														                    <div id="team_member_area" class="col-12">
														                            <?
														                            $query="select * from darlelJobber_teams where requestId='$entryId'";
														                            $teams=getAll($con,$query);
														                            $random=generateRandomString();
														                            foreach($teams as $row){?>
														                            <p class="btn btn-light-success btn-sm" style="margin-right:3px;">
														                                <?echo $idToInfoUsers[$row['userId']]['name']." (".$idToInfoUsers[$row['userId']]['role'].")"?>
                                                            					    </p>
                                                            					    <?}?>
														                        </div>    
    														                </div>
    														            </div>
    														        </div>
														        <?}?>
														    <?}?>
														    
														    <input type="text" name="actionId" value="<?echo clear($_GET['entryId'])?>" hidden>
    														<?if(!$view){?>
    														<div class="row" style="margin-bottom: 10px;margin-top: 20px;">
    														    <div class="col-12" style="text-align: right;">
    														        <input type="submit" class="btn btn-primary " name="<?if($client){echo "addRequestClient";}else{echo "addRequest";}?>" value="Save Request">
    														        <?if($requestDeets['convertStatus']!="Converted"){?>
                                                                    <input class="btn btn-primary " type="submit" name="convertQuote" value="Convert To Quote">
    													            <?}?>
    														        <?if(!$client && $session_role!="Estimator"){?>
														            <button type="button" class="btn btn-secondary  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Save And</button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><input type="submit" class="btn btn-warning btn-sm w-100" name="sendEmail" value="Send Email"></li>    
												                        <li><input type="submit" class="btn btn-warning btn-sm w-100" name="sendSms" value="Send Sms"></li>
                                                                    </ul>
                                                                    <a id="sendEmailBtn" data-bs-toggle="modal" data-bs-target="#emailModal" hidden>Send Email</a>
                                                                    <a id="sendSmsBtn" data-bs-toggle="modal" data-bs-target="#smsModal" hidden>Send SMS</a>
                                                                    <?}?>
    														    </div>
    														</div>
    														<?}?>
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
			
	<?//require("./includes/views/footerjs.php");?>
	
    
			<script>var hostUrl = "assets/";</script>
    		<script src="assets/js/scripts.bundle.js"></script>
	
		</div>
	</body>
	
	<div class="modal fade" id="client_name" aria-hidden="true">
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
							        <div class="col-7">
							            <h1>Select OR Create A Client</h1>
							        </div>
							        <div class="col-5" style="text-align: right;">
							            <a class="btn btn-primary btn-sm" href="./addClient.php?add_client=1&new=1&page=createRequest<?if(isset($_GET['start'])){ echo "&start=".$_GET['start']."&end=".$_GET['end'];}?>">Create New Client</a>
							        </div>
							    </div>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Select Client</label>
								<select id="select2insidemodal" class="form-select" data-control="select2" data-placeholder="Select an option" onchange="updateInfo()" name="client">
								    <option selected disabled>---Select Client---</option>
								    <?foreach($clients as $row){?>
								        <option <?if(($requestDeets['request_for']==$row['id']) || ($_GET['customerId']==$row['id']) || ($client && $session_id==$row['id'])){echo "selected";}?> value="<?echo $row['id']?>">
								            <?
									        if($row['showCompanyName']=="Yes")
								                echo  $row['company_name']." (".$row['first_name']." ".$row['last_name'].")";
                                            else
									            echo $row['first_name']." ".$row['last_name'];
									        ?>
                                        </option>
								    <?}?>
								</select>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">Select Contact</label>
								<select class="form-control" name="contactId" onchange="updateContactInfo()">
								    <option selected>-- Select Contact --</option>
								    <?foreach($allContacts as $row){?>
								    <option style="display:none;" <?if($row['id']==$requestDeets['contactId']){echo "selected";}?> class="<?echo $row['customerId']?>" value="<?echo $row['id']?>"><?echo $row['name']?></option>
								    <?}?>
								</select>
							</div>
							
							
							<a id="closeModal" data-bs-dismiss="modal" hidden></a>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		
		<script>
		    var idToInfo=<?echo json_encode($idToInfo);?>;
		    var properties=<?echo json_encode($properties);?>;
	        var idToContactDetails=<?echo json_encode($idToContactDetails);?>;
	        
		    function updateContactInfo(){
		        $("#closeModal")[0].click();
		        $('#accountContactDetails').empty();
		        
		        var contactId=$("select[name='contactId']").val();
		        var contactName = $("select[name='contactId']").find(":selected").text();
                $("input[name='contactId']").val(contactId);
                $('#accountContactDetails').append(contactName+"<br>");
		        
		        var contactDetails=idToContactDetails[contactId];
		        if(contactDetails!=null){
    		        for(var i=0;i<contactDetails.length;i++)
    		            $('#accountContactDetails').append(contactDetails[i]+"<br>");
		        }
		    }
		    
		    function updateInfo(){
		        $('#change_property').empty();
		        $('#contactDetails').empty();
		        var userId=$("select[name='client']").val();
		        
		        $("select[name='contactId'] option").hide();
		        $("select[name='contactId'] option."+userId).show();
		        
		        $("input[name='request_for']").val(userId);
		        var userInfo=idToInfo[userId];
		        var allProperties=properties;
		        
		        //client name appending
		        var title="";
		        if(userInfo['title']=="No Title"){title="";}else{title=userInfo['title'];}
	            
	            var clientName = title.concat(" ", userInfo['first_name']," ", userInfo['last_name']);
		        var companyName=userInfo['company_name'];
                companyName = companyName.concat(" (", userInfo['first_name']," ", userInfo['last_name'],")");
                
                if(userInfo['showCompanyName']=="Yes")
                    $('#clientName').text(companyName);
                else
                    $('#clientName').text(clientName);
                    
                //client property appending
                for(var i=0;i<allProperties.length;i++)
                {
                    if(allProperties[i]['userId']==userId && <?if($checkNew){?> allProperties[i]['type']=="primary" <?} else {?>  allProperties[i]['id']=="<?echo $requestDeets['propertyId']?>" <?}?>)
                    {
                        $('#street1').text(allProperties[i]['street1']);
                        $('#street2').text(allProperties[i]['street2']);
                        $('#country').text(allProperties[i]['country']);
                        $('#city').text(allProperties[i]['city']);
                        $('#state').text(allProperties[i]['state']);
                        $('#zip_code').text(allProperties[i]['zip_code']);
                        $("input[name='propertyId']").val(allProperties[i]['id']);
                        break;
                    }
                }
                <?if(!$view){?>
                $('#change_property').append(`<a style="margin-right: 10px;" onclick="editProperty()" href="#" >Edit</a>`);
                $('#change_property').append(`<a id="changePropertyBtn" href="#" data-bs-toggle="modal" data-bs-target="#changeProperty">Change</a>`);
                <?}?>
                
                //contact details appending
                var contact_type = userInfo['contact_type'].split("*");
                var phone = userInfo['phone'].split("*");
                for(var i=0;i<contact_type.length;i++){
                    var phoneNumber = phone[i] ? phone[i] : "N/A";
                    if(phoneNumber=="N/A")
                        continue;
                    string=`<p>`+contact_type[i]+` :  `+phone[i]+`</p>`
                    $('#contactDetails').append(string);
                }
                
                var email_type = userInfo['email_type'].split("*");
                var email = userInfo['email'].split("*");
                for(var i=0;i<email_type.length;i++){
                    var fullEmail = email[i] ? email[i] : "N/A";
                    if(fullEmail=="N/A")
                        continue;
                    string=`<p>`+email_type[i]+` :  `+email[i]+`</p>`
                    $('#contactDetails').append(string);
                }
            }
            
            <?if($client && (!$issetEntryId))
                echo "updateInfo();";?>
		</script>
		
		
	
	<div class="modal fade" id="changeProperty" tabindex="-1" aria-hidden="true">
			
			<div class="modal-dialog modal-dialog-centered mw-650px">
				<div class="modal-content rounded">
					<div class="modal-header pb-0 border-0 justify-content-end">
						<a id="closeChangePropertyModal" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<span class="svg-icon svg-icon-1">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
									<rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
								</svg>
							</span>
						</a>
					</div>
					
					<div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
						<form action="" method="post" enctype="multipart/form-data">
						    <div class="mb-13 text-left">
							    <div class="row">
							        <div class="col-9">
							            <h1>Change Property</h1>
							        </div>
							        <div class="col-3">
							            <a onclick="addProperty()" class="btn btn-primary btn-sm">Add Property</a>
							        </div>
							    </div>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Select Property</span>
								</label>
								
								<select id="propertyOptions" onchange="updateProperty()" class="form-control" name="property_id">
								</select>
							</div>
							<a id="closeModalProperty" data-bs-dismiss="modal" hidden></a>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		    
	    $(document).ready(function(){
	    //hiding all contacts
	    $("select[name='contactId'] option").hide();
		
		//if client name is not selected
	    $( "#requestForm" ).submit(function( event ) {
          var requestFor=$("input[name='request_for']").val()
          if(requestFor==""){
              alert("Client name is required before form submission");
              event.preventDefault();
          }
          else{
            $(this).find(':submit').css('pointer-events', 'none');
          }
        });
	    
	    <?if(isset($_GET['customerId']) || $issetEntryId ){?>
	        updateInfo();
	        updateContactInfo();
	    <?}?>
    	$("#changeProperty").on('show.bs.modal', function (e) {
            $('#propertyOptions').empty();
            var userId=$("select[name='client']").val();
            console.log(userId);
            var allProperties=properties;
            
            var string=`<option selected disabled>---Select Property---</option>`;
            $('#propertyOptions').append(string);
            
            for(var i=0;i<allProperties.length;i++)
            {
                if(allProperties[i]['userId']==userId)
                {
                    var option=`<option value="`+allProperties[i]['id']+`">`+allProperties[i]['street1']+`,`+allProperties[i]['street2']+` `+allProperties[i]['city']+`,
                    `+allProperties[i]['state']+`,`+allProperties[i]['country']+`
                    </option>`;
                    $('#propertyOptions').append(option);
                }
            }
        });
	    });
	    function updateProperty()
	    {
	        var allProperties=properties;
	        var property_id=$("select[name='property_id']").val();
	        for(var i=0;i<allProperties.length;i++)
            {
                if(allProperties[i]['id']==property_id)
                {
                    $('#street1').text(allProperties[i]['street1']);
                    $('#street2').text(allProperties[i]['street2']);
                    $('#country').text(allProperties[i]['country']);
                    $('#city').text(allProperties[i]['city']);
                    $('#state').text(allProperties[i]['state']);
                    $('#zip_code').text(allProperties[i]['zip_code']);
                    $("#closeModalProperty")[0].click();
                    $("input[name='propertyId']").val(property_id)
                    break;
                }
            }
	        
	    }
	    $(document).ready(function() {
	        <?if($view){?>
	        
                $('#requestForm :input').prop('readonly', true);
                $("input[name='quickConvert']").prop('readonly', false);/*
                $("#requestForm :input").prop("disabled", true);
                $("input[name='quickConvert']").prop("disabled", false);*/
	        <?}?>
	        
          $("#select2insidemodal").select2({
            dropdownParent: $("#client_name")
          });
          $("#teamSelect").select2({
            dropdownParent: $("#add_team")
          });
        });
		</script>
		
		
		
		
	<div class="modal fade" id="add_team" tabindex="-1" aria-hidden="true">
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
        							    <h1 class="mb-3">Add Team Members</h1>
        							</div>
    							</div>
							</div>
							<div class="row g-9 mb-8">
								<div class="col-md-12 fv-row">
								    <label>Team Member</label>
								    <select id="teamSelect" data-control="select2" data-placeholder="Select A User" onchange="addTeamMember()" class="form-control" name="members">
								        <option disabled selected>--Select User--</option>
								        <?
								        foreach($users as $row){?>
								            <option value="<?echo $row['id']?>"><?echo $row['name'] ."  (".$row['role'].")";?></option>
								        <?}?>
								    </select>
								</div>
							</div>
						    <div class="text-center">
						        <button type="button" class="btn btn-primary btn-sm " data-bs-dismiss="modal">Close</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
		    var idToInfoUsers=<?echo json_encode($idToInfoUsers);?>;
		    function addTeamMember(){
		        var random=makeid(5);
		        var userId=$("select[name='members']").val();
		        var html=`<p id="`+random+`" class="btn btn-light-success btn-sm" style="margin-right:3px;">
    					        `+idToInfoUsers[userId]['name']+` (`+idToInfoUsers[userId]['role']+`) 
    					        <a onclick="removeMember('`+random+`')" style="margin-left: 10px;color: red;">X</a>
    					        <input type="text" name="team[]" value="`+userId+`" hidden>
    					    </p>`;
		        $('#team_member_area').append(html);
		    }
		    function removeMember(id){
		        $('#'+id).remove();
		    }
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
            
            function addProperty(){
    		    var customerId=$("input[name='request_for']").val();
    		    var requestId="<?echo ($new) ?  "newRequest" : $requestId ?>";
    		    var start="<?echo clear($_GET['start'])?>";
    		    var end="<?echo clear($_GET['end'])?>";
    		    if(start=="" || end=="")
    	            window.location.href ='view_client.php?id='+customerId+'&requestId='+requestId+'&action=addPropertyFromRequest';
                else if(start!="" && end!="")
    	            window.location.href ='view_client.php?id='+customerId+'&requestId='+requestId+'&action=addPropertyFromRequest&start='+start+'&end='+end;
            }
            
            function editProperty(){
                var customerId=$("input[name='request_for']").val();
                var propertyId=$("input[name='propertyId']").val();
                var requestId="<?echo ($new) ?  "newRequest" : $requestId ?>";
    	        window.location.href ='view_client.php?id='+customerId+'&requestId='+requestId+'&action=editPropertyFromRequest'+'&propertyId='+propertyId;
            }
            
            function editContactDetails(){
                var customerId=$("input[name='request_for']").val();
                var requestId="<?echo $requestId ?>";
                window.location.href ='addClient.php?customerId='+customerId+'&action=editFromRequest&requestId='+requestId;
            }
            
            $(document).ready(function() {
                
                <?if(isset($_GET['propertyId'])){?>
                $("#changePropertyBtn")[0].click();
                $("select[name='property_id']").val("<?echo clear($_GET['propertyId'])?>");
                $("input[name='propertyId']").val("<?echo clear($_GET['propertyId'])?>");
                updateProperty();
                $("#closeChangePropertyModal")[0].click();
                setTimeout(function() {
                    $("#closeChangePropertyModal")[0].click();
                }, 500); // 3000 milliseconds (3 seconds) delay
                <?}?>
            });
		</script>
	
	
		<?
		include("./notes/notes_js.php");
		include("./emailsAndSms/multipleSmsModal.php");
    	include("./emailsAndSms/multipleEmailModal.php");
        ?>
        
        <script>
        $(document).ready(function() {
            /*when request is submitted and sending options is selected started*/
            <?if($_GET['triggerModal']=="email"){?>
    	        $("#sendEmailBtn")[0].click();
            <?}else if($_GET['triggerModal']=="sms"){?>
    	        $("#sendSmsBtn")[0].click();
            <?}?>
            /*when request is submitted and sending options is selected finished*/
        });    
        </script>
	
</html>
