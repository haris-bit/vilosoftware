<?
require("./global.php");
if($logged==0)
    header("Location:./index.php");
    
$installationView = ($session_role=="Installation Crew") ? 1 : 0;

$ticketId=clear($_GET['ticketId']);
if(!isset($_GET['ticketId']))
    $ticketId="Nothing";
else
    $ticketDeets=getRow($con,"select * from darlelJobber_tickets where id='$ticketId'");

$view=0;
if(isset($_GET['view']))
    $view=1;
    
$new = (isset($_GET['new'])) ? 1 : 0;
    
$clients=getAll($con,"select *,CONCAT(first_name, ' ',last_name) AS 'clientName' from darlelJobber_users where role='Client'");
$idToInfo=[];
$properties=getAll($con,"select * from darlelJobber_properties");
$users=getAll($con,"select * from darlelJobber_users ");
foreach($users as $row){
    $idToInfo[$row['id']]=$row;    
}
$jobs=getAll($con,"SELECT j.id,j.customerId,j.job_number,j.title,j.quoteId,
concat(p.street1,' ',p.street2,' ',p.city,' ',p.state,' ',p.country) as propertyAddress
from darlelJobber_jobs j inner join darlelJobber_properties p on j.propertyId=p.id");

$quoteIdToEstimator=[];
$quotes=getAll($con,"select * from darlelJobber_quotes where convertStatus='Converted'");
foreach($quotes as $row)
    $quoteIdToEstimator[$row['id']]=$row['estimatorId'];

$jobIdToEstimator=[];
foreach($jobs as $row)
    $jobIdToEstimator[$row['id']]=$quoteIdToEstimator[$row['quoteId']];

if(isset($_POST['addTicket'])){
    $title=clear($_POST['title']);
    $customerId=clear($_POST['customerId']);
    $jobId=clear($_POST['jobId']);
    $materialsUsed=clear($_POST['materialsUsed']);
    $propertyId=clear($_POST['propertyId']);
    $phoneNumber=clear($_POST['phoneNumber']);
    $installationDate=strtotime(clear($_POST['installationDate']));
    $complaintDate=strtotime(clear($_POST['complaintDate']));
    $paidInFull=clear($_POST['paidInFull']);
    $photos=clear($_POST['photos']);
    $description=clear($_POST['description']);
    $start_date=strtotime(clear($_POST['start_date']));
    $end_date=strtotime(clear($_POST['end_date']));
    $start_time=clear($_POST['start_time']);
    $end_time=clear($_POST['end_time']);
    $scheduleType=clear($_POST['scheduleType']);
    $estimatorId=clear($_POST['estimatorId']);
    $ticketId=clear($_POST['ticketId']);
    $timeAdded=time();
    
    if($ticketId==""){
        $ticketId=generateRandomString();
        $ticket_number=getRow($con,"select max(ticket_number) as ticket_number from darlelJobber_tickets")['ticket_number']+1;
        
        $query="insert into darlelJobber_tickets set id='$ticketId',ticket_number='$ticket_number',estimatorId='$estimatorId',scheduleType='$scheduleType',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',
        materialsUsed='$materialsUsed',title='$title',jobId='$jobId',customerId='$customerId',propertyId='$propertyId',phoneNumber='$phoneNumber',
        installationDate='$installationDate',complaintDate='$complaintDate',paidInFull='$paidInFull',photos='$photos',description='$description',timeAdded='$timeAdded',addedBy='$session_id'";
        runQuery($query);
    }
    else{
        $query="update darlelJobber_tickets set customerId='$customerId',estimatorId='$estimatorId',scheduleType='$scheduleType',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',
        materialsUsed='$materialsUsed',jobId='$jobId',title='$title',propertyId='$propertyId',phoneNumber='$phoneNumber',
        installationDate='$installationDate',complaintDate='$complaintDate',paidInFull='$paidInFull',photos='$photos',description='$description' where id='$ticketId'";
        runQuery($query);
    }

    $newteam=$_POST['team'];
    $oldteamfetch=getAll($con,"select userId from darlelJobber_teams where ticketId='$ticketId'");
    $oldteam=[];
    foreach($oldteamfetch as $row){
        $oldteam[]=$row['userId'];
    }
    
    foreach($users as $row){
        $userId=$row['id'];
        if((in_array($userId,$newteam)) && (!in_array($userId,$oldteam))){
            $id=generateRandomString();
            $query="insert into darlelJobber_teams set id='$id',ticketId='$ticketId',userId='$userId',timeAdded='$timeAdded'";
            runQuery($query);
            
            $title="Assigned To a Ticket";
            $description="You have been added as a member in a ticket . Click To View";
            $url=$projectUrl."create_ticket.php?entryId=$ticketId";
            setNotification($title,$description,$userId,$url);
        }
        else if((!in_array($userId,$newteam)) && (in_array($userId,$oldteam))){
            $query="delete from darlelJobber_teams where userId='$userId' && ticketId='$ticketId'";
            runQuery($query);
        }
    }
    
    
    //once the ticket is created we will update the time remaining in the invoice that has this job
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where jobId='$jobId'");
    $timeRemaining=$invoiceDeets['finishTime']-time();
    $invoiceId=$invoiceDeets['id'];
    $query="update darlelJobber_invoices set timeRemaining='$timeRemaining',timePeriodStatus='stop' where id='$invoiceId'";
    runQuery($query);
    
    header("Location:./create_ticket.php?ticketId=$ticketId");
}

//installation entry
if(isset($_GET['delete-crew-entry'])){
    $id=$_GET['delete-crew-entry'];
    $query="delete from darlelJobber_installation where id='$id'";
    runQuery($query);
    
    header("Location:?entryId=$entryId");
}

//installation ended
$jobId=$ticketDeets['jobId'];
$query="select * from darlelJobber_quotes where id=(select quoteId from darlelJobber_jobs where id='$jobId')";
$quoteDeets=getRow($con,$query);
$query="select * from darlelJobber_visits where jobId='$jobId' && start_date = (select min(start_date) from darlelJobber_visits where jobId='$jobId')";
$firstVisit=getRow($con,$query);

if(isset($_GET['completed'])){
    
    $query="update darlelJobber_tickets set completionStatus='Completed' where id='$ticketId'";
    runQuery($query);
    
    $jobId=getRow($con,"select * from darlelJobber_tickets where id='$ticketId'")['jobId'];
    $invoiceDeets=getRow($con,"select * from darlelJobber_invoices where jobId='$jobId'");
    $invoiceId=$invoiceDeets['id'];
    
    $finishTime=time()+$invoiceDeets['timeRemaining'];
    $query="update darlelJobber_invoices set finishTime='$finishTime',timePeriodStatus='start' where id='$invoiceId'";
    runQuery($query);
    header("Location:?ticketId=$ticketId&m=Ticket has been completed successfully ");
}

require("./notes/notes.php");
?>
<html lang="en">
	<!--begin::Head-->
	<head>
		<?require("./includes/views/head.php");?>
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
					
					<form method="post" action="" enctype="multipart/form-data" id="ticketForm"> 
                    <div class="content d-flex flex-column flex-column-fluid" style="margin-top: 40px;">
					    <div class="post d-flex flex-column-fluid">
					        <div class="container-xxl" id="kt_content_container">
					            <div class="row g-5 g-xl-10">
    					            <div class="card card-flush py-4" style="margin-bottom: 30px;">
    									<div class="card-header">
    										<div class="card-title">
    											<h2><?if(!$new){echo "#".$ticketDeets['ticket_number'];}?> Ticket For
    						                        <a <?if(!$view){?>href="#" data-bs-toggle="modal" data-bs-target="#client_name"<?}?>>
    						                            <p style="display: inline;" id="clientName">
    						                                Client Name	
						                                </p>
    					                            </a>
    					                            <?
    									            if(isset($_GET['new']))
    									                $receivedBy=$idToInfo[$session_id]['name'];
    									            else
    									                $receivedBy=$idToInfo[$ticketDeets['addedBy']]['name'];
    									            ?>
    									            (Received By : <?echo $receivedBy?>)
    						                    </h2>
    						                    <input type="text" name="customerId" value="" hidden="">
    						                    <input type="text" name="ticketId" value="<?echo $_GET['ticketId']?>" hidden="">
    										</div>
    										<div class="card-toolbar">
										    <?if($view && $session_role!="Installation Crew"){?>
										    <a href="?ticketId=<?echo $ticketId?>" class="btn btn-warning btn-sm me-3">Edit Ticket</a>
										    <?}
										    if(($view || (!$new)) && ($ticketDeets['completionStatus']!="Completed")){?>
										    <a href="?ticketId=<?echo $ticketId?>&completed=1" class="btn btn-success btn-sm">Mark As Completed</a>
										    <?}?>
										    
										    
										    </div>
    									</div>
    									<div class="card-body pt-0">
    									    <div class="row">
    									        <div class="col-md-6 col-12" style="margin-bottom:10px;">
    									            <h5>Title</h5>
    									            <input type="text" class="form-control" name="title" placeholder="Enter Title" value="<?echo $ticketDeets['title']?>">
    									        </div>
    									        <?if(!$installationView){?>
    									        <div class="col-md-6 col-12" style="margin-bottom:10px;">
    									            <h5>Select Job</h5>
    									            <select class="form-control" name="jobId" onchange="updateEstimator()"></select>
    									        </div>
    									        <div class="col-md-6 col-12" style="margin-bottom:10px;">
    									            <h5>Materials Used</h5>
    									            <input type="text" class="form-control" name="materialsUsed" placeholder="Enter Materials Used" value="<?echo $ticketDeets['materialsUsed']?>">
    									        </div>
    									        <div class="col-md-6 col-12" style="margin-bottom:10px;">
    									            <h5>Schedule Type</h5>
    									            <select class="form-control" name="scheduleType" onchange="changeScheduler()">
    									                <option value="Schedule Later" <?if($ticketDeets['scheduleType']=="Schedule Later"){echo "selected";}?>>Schedule Later</option>
    									                <option value="Schedule Now" <?if($ticketDeets['scheduleType']=="Schedule Now" || ($new)){echo "selected";}?>>Schedule Now</option>
    									            </select>
    									        </div>
    									        <div id="scheduler">
    									            <div class="row">
            									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
            									            <?
            									            if((isset($_GET['new'])) && (!isset($_GET['start']))){
            									                $start_date = time();
        								                        $end_date = time();
        							                            $start_time = "08:00";
        								                        $end_time = "10:00";
            									            }
            									            else if((isset($_GET['new'])) && (isset($_GET['start']))){
            									                $start_date = strtotime($_GET['start']);
        								                        $end_date = strtotime($_GET['start']);
        							                            $start_time = date("H:i",strtotime($_GET['start']));
        								                        $end_time = date("H:i",strtotime($_GET['end']));
            									            }
            									            else if(isset($_GET['ticketId'])){
        									                    $start_date = $ticketDeets['start_date'];
        									                    $end_date = $ticketDeets['end_date'];
        								                        $start_time = date("H:i",strtotime($ticketDeets['start_time']));
        								                        $end_time = date("H:i",strtotime($ticketDeets['end_time']));
            									            }
            									            ?>
            									            <h5>Start Date</h5>
            									            <input type="date" class="form-control" name="start_date" value="<?echo date("Y-m-d",$start_date)?>">
            									        </div>
            									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
            									            <h5>Start Time</h5>
            									            <input type="time" class="form-control" name="start_time" value="<?echo $start_time?>">
            									        </div>
            									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
            									            <h5>End Date</h5>
            									            <input type="date" class="form-control" name="end_date" value="<?echo date("Y-m-d",$end_date)?>">
            									        </div>
            									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
            									            <h5>End Time</h5>
            									            <input type="time" class="form-control" name="end_time" value="<?echo $end_time?>">
            									        </div>
        									        </div>
    									        </div>
    									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
    									            <h5>Select Phone Number</h5>
    									            <select class="form-control" name="phoneNumber"></select>
    									        </div>
    									        <?}?>
    									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
    									            <h5>
    									                Select Property
									                    <a style="margin-left: 10px;" class="btn btn-sm btn-success ml-2" id="copyBtn" onclick="copyAddress()"><i style="font-size: x-large;" class="las la-copy mt-2 mb-2"></i></a>
    									            </h5>
    									            <select class="form-control" name="propertyId"></select>
    									        </div>
    									        <?if(isset($_GET['ticketId'])){
    									            //$installationDate=$ticketDeets['installationDate'];
    									            $installationDate=$firstVisit['start_date'];
    									            $complaintDate=$ticketDeets['complaintDate'];
    									        }else if(isset($_GET['new'])){
    									            $installationDate=time();
    									            $complaintDate=time();
    									        }
    									        
    									        if(!$installationView){?>
    									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
    									            <h5>Installation Date</h5>
    									            <input class="form-control" type="date" name="installationDate" value="<?echo date("Y-m-d",$installationDate)?>">
    									        </div>
    									        <div class="col-xs-12 col-sm-12 col-md-6" style="margin-bottom:10px;">
    									            <h5>Complaint Date</h5>
    									            <input class="form-control" type="date" name="complaintDate" value="<?echo date("Y-m-d",$complaintDate)?>">
    									        </div>
    									        <div class="col-xs-4 col-sm-4 col-md-2 col-4">
    									            <div class="form-group" style="margin-bottom: 10px;margin-top: 20px;">
														<label style="margin-bottom: 15px;">Paid In Full ?</label>
    									                	<div style="margin-bottom: 10px;" class="form-check">
    										                    <input class="form-check-input" type="radio" name="paidInFull" value="Yes" 
    										                    <?if($ticketDeets['paidInFull']=="Yes" || $quoteDeets['complete_payment']=="Yes"){echo "checked";}?>>
                                                                 <label class="form-check-label">
                                                                    Yes
                                                                 </label>
                                                            </div>                                                         
                                                            <div style="margin-bottom: 10px;" class="form-check">
    										                    <input class="form-check-input" type="radio" name="paidInFull" value="No" <?if($ticketDeets['paidInFull']=="No"){echo "checked";}?>>
                                                                 <label class="form-check-label">
                                                                    No
                                                                 </label>
                                                            </div>
                                                    </div>
    									        </div>
    									        <div class="col-xs-4 col-sm-4 col-md-2 col-4">
    									             <div class="form-group" style="margin-bottom: 10px;margin-top: 20px;">
														<label style="margin-bottom: 15px;">Photos ?</label>
    									                	<div style="margin-bottom: 10px;" class="form-check">
    										                    <input class="form-check-input" type="radio" name="photos" value="Yes"  <?if($ticketDeets['photos']=="Yes"){echo "checked";}?>> 
                                                                 <label class="form-check-label">
                                                                    Yes
                                                                 </label>
                                                            </div>                                                         
                                                            <div style="margin-bottom: 10px;" class="form-check">
    										                    <input class="form-check-input" type="radio" name="photos" value="No" <?if($ticketDeets['photos']=="No"){echo "checked";}?>>
                                                                 <label class="form-check-label">
                                                                    No
                                                                 </label>
                                                            </div>
                                                    </div>
    									        </div>
    									        <div class="col-xs-4 col-sm-4 col-md-2 col-4">
    									            <div class="form-group" style="margin-bottom: 10px;margin-top: 20px;">
														<label style="margin-bottom: 15px;">Select Estimator</label><br>
    									                <select class="form-control" name="estimatorId">
        						                            <option <?if($new){echo "selected";}?> disabled>--Select Estimator--</option>
        						                            <?foreach($users as $row){if(($row['role']=="Estimator" || $row['role']=="Admin")){?>
        						                            <option <?if($ticketDeets['estimatorId']==$row['id']){echo "selected";}?> value="<?echo $row['id']?>">
        						                                <?echo $row['role']." :  ".$row['name']?>
        					                                </option>
        						                            <?}}?>
        						                        </select>
    						                        </div>
    									        </div>
    									        <div class="col-xs-12 col-sm-12 col-md-6">
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
								                            $teams=getAll($con,"select * from darlelJobber_teams where ticketId='$ticketId'");
								                            $random=generateRandomString();
								                            foreach($teams as $row){?>
								                            <p id="<?echo $random?>" class="btn btn-light-success btn-sm" style="margin-right:3px;">
								                                <?echo $idToInfo[$row['userId']]['name']." (".$idToInfo[$row['userId']]['role'].")"?>
                                    					        <a onclick="removeMember('<?echo $random?>')" style="margin-left: 10px;color: red;">X</a>
                                    					        <input type="text" name="team[]" value="<?echo $row['userId']?>" hidden>
                                    					    </p>
                                    					    <?}?>
								                        </div>
								                    </div>
								                </div>
								                <?}?>
								                <div class="col-12" style="margin-top:20px;margin-bottom: 30px;">
								                    <label>Description</label>
								                    <textarea class="form-control" name="description" placeholder="Enter Description" rows="5"><?echo $ticketDeets['description']?></textarea>
								                </div>
								                <?if(!isset($_GET['new'])){?>
								                <div class="col-12">
								                    <?include("./notes/notes_table.php");?>
    										    </div>
    										    <?}?>
    										    
    										    <?if(isset($_GET['ticketId'])){?>
    										    <div class="col-12">
										            <div class="card shadow-sm card-body pt-0 mb-10">
                    									<div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    										<div class="card-title">
                    										    Installation Section
                    										</div>
                    										<div class="card-toolbar">
                    										    <?if(!$view || $session_role=="Installation Crew"){?>
                                                                <a target="_blank" href="addInstallation.php?ticketId=<?echo $ticketId?>&new=<?echo random();?>" class="btn btn-primary btn-sm">Add Installation Images</a>
                                    							<?}?>
                										    </div>
                    									</div>
                    									<div class="">
                    									    <div class="table-responsive">
                                                                <table class="table table-rounded table-striped border gy-7 gs-7">
                        										    <thead>
                        										        <tr>
                        										            <th style="text-align: center;">Title</th>
                        										            <th>Attachment</th>
                        										            <th>Added By</th>
                        										            <?if(!$view){?>
                                                                            <th>Actions</th>
                                                                            <?}?>
                        										        </tr>
                        										    </thead>
                        										    <tbody>
                    										        <?
                    										        $crewImages=getAll($con,"select * from darlelJobber_installation where ticketId='$ticketId'");
                    										        foreach($crewImages as $row){$installationId=$row['id'];?>
                    										        <tr>
                    										            <td style="text-align: center;">
                    										                <?echo $row['title']."<br>"?>
                    										                <a class="badge badge-<?if($row['timeline']=="Before Installation"){echo "warning";}else{echo "success";}?> btn-sm"><?echo $row['timeline']?></a>
                										                </td>
                    										            <td>
                    										                <?$installationImages=getAll($con,"select * from darlelJobber_installation_images where installationId='$installationId'");
                    										                foreach($installationImages as $nrow){?>
                    										                <a class="badge badge-success btn-sm gallery" href="uploads/<?echo $nrow['image']?>">
                										                        <img class="example-image" style="max-height: 2.5rem;" src="uploads/<?echo $nrow['image']?>"/>    
                                                                            </a>
                                                	                        <?}?>
                										                </td>
                    										            <td><?echo $idToInfo[$row['addedBy']]['name']?></td>
                    										            <td><?echo date("d M Y",$row['timeAdded'])?></td>
                    										            <td>
                                                                            <div class="btn-group">
                                                                			<a href="addInstallation.php?edit=<?echo $row['id']?>&ticketId=<?echo $ticketId?>" class="btn btn-warning btn-sm" >Edit</a>
                    									                    <?if($isAdmin){?>
                										                    <a class="btn btn-danger btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#delete_record"  data-url="?delete-crew-entry=<?echo $row['id']?>&entryId=<?echo $entryId?>">Delete</a>
                    									                    <?}?>
                    									                    </td>
                    									                </td>
                    									            </tr>
                    										        <?}?>
                    										        </tbody>
                        										</table>
                        									</div>
                    									</div>
                    								</div>
    										    </div>
    										    <?}?>
								                <?if(!$view){?>
								                <div class="col-12" style="text-align:center;margin-top:20px;">
								                    <input type="submit" name="addTicket" class="btn btn-primary w-50" value="Save Changes">
								                </div>
								                <?}?>
    									    </div>
    									</div>
    							    </div>
					            </div>
					            
					        </div>
					    </div>
					</div>
					
					
		            </form>		
					<?require("./includes/views/footer.php");?>
					
					<!--end::Footer-->
				</div>
				<!--end::Wrapper-->
			</div>
			
	<?require("./includes/views/footerjs.php");?>
		<script src="assets/plugins/custom/fslightbox/fslightbox.bundle.js"></script>
	
		</div>
	</body>
	
	
	
		
		<div class="modal fade" id="add_crew_image" tabindex="-1" aria-hidden="true">
			
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
							    <div class="row"><div class="col-9"><h1 class="mb-3" id="installationTitle"></h1></div></div>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Title</span>
								</label>
								<input type="text" name="title" class="form-control" placeholder="Enter Title">
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Image Timeline</span>
								</label>
								<select class="form-control" name="timeline">
								    <option selected value="Before Repair">Before Repair</option>
								    <option value="After Repair">After Repair</option>
								</select>
							</div>
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Attachment</span>
									<small id="image_display_installation">
									    <a id="a_tag" target="_blank" href="./uploads/">View Previously Uploaded Image</a>
									</small>
								</label>
								<input type="file" name="fileToUpload" class="form-control" placeholder="Enter Price">
							</div>
							<input type="text" name="actionId" hidden>
							<div class="text-center">
								<input type="submit" value="Save" name="addInstallation" class="btn btn-primary">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script>
	    $(document).ready(function(){
        $("#add_crew_image").on('show.bs.modal', function (e) {
            //get data-id attribute of the clicked element
            var mydata = $(e.relatedTarget).data('mydata');
            console.log(mydata);
            //console.log("mydata", mydata)
            if(mydata!= null){
            	$("#installationTitle").html("Update Installation Image");
                $("input[name='title']").val(mydata['title'])
                $("select[name='timeline']").val(mydata['timeline'])
                $("input[name='actionId']").val(mydata['id'])
                if(mydata['image']!=""){
                    $("#image_display_installation").show();
                    $("#a_tag").attr("href", "./uploads/"+mydata['image'])
                }
                else
                    $("#image_display_installation").hide();
            }else{
                
            	$("#installationTitle").html("Add Installation Image");
                $("input[name='title']").val("")
                $("select[name='timeline']").val("")
                $("input[name='actionId']").val("")
                $("#image_display_installation").hide();
            }
        });
	    })
	    </script>
		
	
	
	
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
							            <a  class="btn btn-primary btn-sm" 
							            href="./addClient.php?add_client=1&new=1&page=create_ticket<?if(isset($_GET['start'])){ echo "&start=".$_GET['start']."&end=".$_GET['end'];}?>">Create New Client</a>
							        </div>
							    </div>
							</div>
							
							<div class="d-flex flex-column mb-8 fv-row">
								<label class="d-flex align-items-center fs-6 fw-bold mb-2">
									<span class="required">Select Client</span>
								</label>
								<select id="select2insidemodal" class="form-select" data-control="select2" data-placeholder="Select an option" onchange="updateInfo()" name="client">
								    <option selected disabled>---Select Client---</option>
								    <?foreach($clients as $row){?>
								        <option <?if(($row['id']==$ticketDeets['customerId']) || ($_GET['customerId']==$row['id'])){echo "selected";}?> value="<?echo $row['id']?>">
								            <?echo $row['clientName']?>
							            </option>
                                    <?}?>
								</select>
							</div>
							<a  id="closeModal" data-bs-dismiss="modal" hidden></a>
						</form>
					</div>
				</div>
			</div>
		</div>
	<script>
	
	    function changeScheduler(){
	        var scheduleType=$("select[name='scheduleType']").val();
	        if(scheduleType=="Schedule Now")
	            $('#scheduler').show();
	        else
	            $('#scheduler').hide();
	    }
	    
	
	    var properties=<?echo json_encode($properties);?>;
	    var idToInfo=<?echo json_encode($idToInfo);?>;
	    var ticketDeets=<?echo json_encode($ticketDeets);?>;
	    var jobs=<?echo json_encode($jobs);?>;
	    var jobIdToEstimator=<?echo json_encode($jobIdToEstimator);?>;
        
        function updateEstimator(){
	        var jobId = $("select[name='jobId']").val();
	        var estimatorId= jobIdToEstimator[jobId];
	        $("select[name='estimatorId']").val(estimatorId);
	    } 
		    
		function updateInfo(){
	        var clientId=$("select[name='client']").val();
	        $("#closeModal")[0].click();
		    $("input[name='customerId']").val(clientId);
	        $("select[name='propertyId']").empty();
	        $("select[name='phoneNumber']").empty();
	        $("select[name='jobId']").empty();
	        
	        for(var i=0;i<jobs.length;i++){
	            if(jobs[i]['customerId']!=clientId)
	                continue;
	            var selected="";
                <?if(isset($_GET['ticketId'])){?>
                    if(ticketDeets['jobId']==jobs[i]['id'])
                        selected="selected";
                <?}?>
                
	            var option = `<option `+selected+` value="`+jobs[i]['id']+`">#`+jobs[i]['job_number']+" "+jobs[i]['title']+"("+jobs[i]['propertyAddress']+")"+`</option>`; 
                $("select[name='jobId']").append(option);
	        }
	        var userInfo=idToInfo[clientId];
		    
		    var phone = userInfo['phone'].split("*");
		    var contact_type = userInfo['contact_type'].split("*");
            for(var i=0;i<contact_type.length;i++){
                
                var selected="";
                <?if(isset($_GET['ticketId'])){?>
                    if(ticketDeets['phoneNumber']==phone[i])
                        selected="selected";
                <?}?>
                    
                var option=`<option `+selected+` value="`+phone[i]+`">`+contact_type[i]+` :  `+phone[i]+`</p>`
                $("select[name='phoneNumber']").append(option);
	        }
	        
		    var clientName="";
		    clientName=clientName.concat(userInfo['first_name']," ",userInfo['last_name']);
	        $('#clientName').text(clientName);
            
            for(var i=0;i<properties.length;i++){
                if(properties[i]['userId']==clientId){
                    var address="";
                    var selected="";
                    
                    <?if(isset($_GET['ticketId'])){?>
                    if(ticketDeets['propertyId']==properties[i]['id'])
                        selected="selected";
                    <?}?>
                    address=address.concat(properties[i]['street1']," ",properties[i]['street2']," ," , properties[i]['city'],",",properties[i]['state'],",",properties[i]['country'],"( Zip : ",properties[i]['zip_code']," )");
                    var option=`<option `+selected+` value="`+properties[i]['id']+`"> `+address+` </option>`;
                    $("select[name='propertyId']").append(option);
                }
            }
            updateEstimator();
	    }
	    <?if((isset($_GET['ticketId'])) || (isset($_GET['customerId']))){?>
	    updateInfo();
	    <?}?>
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
								    <select id="select2team" data-control="select2" data-placeholder="Select A User" onchange="addTeamMember()" class="form-control" name="members">
								        <option disabled selected>--Select User--</option>
								        <?
								        foreach($users as $row){
								            if($row['role']=="Client")
								                continue;?>
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
		    var idToInfo=<?echo json_encode($idToInfo);?>;
		    function addTeamMember(){
		        var random=makeid(5);
		        var userId=$("select[name='members']").val();
		        var html=`<p id="`+random+`" class="btn btn-light-success btn-sm" style="margin-right:3px;">
    					        `+idToInfo[userId]['name']+` (`+idToInfo[userId]['role']+`) 
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
            
            
            $(document).ready(function() {
            changeScheduler();
            
            $("#select2insidemodal").select2({
                dropdownParent: $("#client_name")
            });
            $("#select2team").select2({
                dropdownParent: $("#add_team")
            });
	        <?if($view){?>
                $("#ticketForm :input").prop("disabled", true);
	        <?}?>
	        
	        <?if(isset($_GET['jobId'])){
	            $jobId=clear($_GET['jobId']);
	            $jobDeets=getRow($con,"select * from darlelJobber_jobs where id='$jobId'");
	            $customerId=$jobDeets['customerId'];?>
	            
	            $("select[name='client']").val("<?echo $customerId?>");
	            var clientId=$("select[name='client']").val();
	            updateInfo();
	            $("select[name='jobId']").val("<?echo $jobId?>");
	        <?}?>
            });
		
		
        		
            document.addEventListener('DOMContentLoaded', function() {
                const gallery = new SimpleLightbox('.gallery', {});
            });
            
            
            function copyAddress(){
                var selectedText = $('select[name="propertyId"] option:selected').text();

                // Create a temporary input element
                var tempInput = $("<input>");
        
                // Append it to the body
                $("body").append(tempInput);
        
                // Set the input value to the selected text
                tempInput.val(selectedText).select();
        
                // Copy the selected text to the clipboard
                document.execCommand("copy");
        
                // Remove the temporary input element
                tempInput.remove();
            }
		</script>
</html>